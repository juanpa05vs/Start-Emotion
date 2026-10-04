<?php

namespace App\Http\Controllers;

use App\Enums\AlcanceConsentimiento;
use App\Models\Consentimiento;
use App\Models\InvitacionPsicologo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Pantalla de control de datos del estudiante.
 *
 * Responde a las tres preguntas que cualquier persona se hace al leer "tus
 * datos de salud mental se comparten con un profesional": qué comparto, con
 * quién, y cómo lo retiro. La última tiene que ser tan fácil como la primera.
 */
class PrivacidadController extends Controller
{
    /** Política de privacidad, pública y sin necesidad de iniciar sesión. */
    public function politica()
    {
        return view('privacidad.politica');
    }

    /** Panel principal: qué comparto, con quién, y qué puedo retirar. */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $compartidos = $user->consentimientosOtorgados()
            ->with('profesional')
            ->orderByDesc('otorgado_en')
            ->get();

        $historico = Consentimiento::query()
            ->where('estudiante_id', $user->id)
            ->whereNotNull('revocado_en')
            ->with('profesional')
            ->orderByDesc('revocado_en')
            ->get();

        return view('privacidad.index', [
            'compartidos' => $compartidos,
            'historico' => $historico,
            'opciones' => AlcanceConsentimiento::cases(),
        ]);
    }

    /**
     * Canjea el código que entregó el profesional y registra el consentimiento.
     *
     * El estudiante elige el alcance; nada se comparte hasta que lo confirma.
     */
    public function conectar(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'codigo' => 'required|string|max:16',
            'alcance' => 'required|array|min:1',
            'alcance.*' => ['string', 'in:'.implode(',', AlcanceConsentimiento::valores())],
        ], [
            'alcance.required' => 'Elige al menos una cosa que quieras compartir.',
            'alcance.*.in' => 'Esa opción no es válida.',
        ]);

        $codigo = Str::upper(trim($request->string('codigo')->toString()));

        /** @var InvitacionPsicologo|null $invitacion */
        $invitacion = InvitacionPsicologo::buscarVigente($codigo);

        if (! $invitacion) {
            return back()->withErrors([
                'codigo' => 'Ese código no existe o ya venció. Pídele uno nuevo a tu profesional.',
            ]);
        }

        if ($invitacion->profesional_id === $user->id) {
            return back()->withErrors(['codigo' => 'Este código es tuyo.']);
        }

        // El destinatario del consentimiento debe ser alguien con rol profesional.
        // Si el rol de la cuenta se cambiara por la base de datos sin pasar por
        // la interfaz, esta comprobación corta el intercambio.
        if (! $invitacion->profesional?->esProfesional()) {
            return back()->withErrors(['codigo' => 'Ese código no corresponde a un profesional.']);
        }

        // Si reconecta con el mismo profesional, se actualiza el alcance en vez
        // de acumular filas: "lo que comparto" es una respuesta, no un registro.
        $existente = Consentimiento::vigenteEntre($user->id, $invitacion->profesional_id);

        $alcance = array_map(fn ($c) => $c->value, AlcanceConsentimiento::desdeEntrada($request->alcance));

        if ($existente) {
            $existente->alcance = $alcance;
            $existente->save();
        } else {
            $nuevo = new Consentimiento;
            $nuevo->alcance = $alcance;
            $nuevo->estudiante_id = $user->id;
            $nuevo->profesional_id = $invitacion->profesional_id;
            $nuevo->otorgado_en = now();
            $nuevo->save();
        }

        $invitacion->usada_en = now();
        $invitacion->usada_por = $user->id;
        $invitacion->save();

        return redirect()->route('privacidad.index')
            ->with('success', 'Listo. Tu profesional ya puede ver lo que elegiste compartir.');
    }

    /**
     * Retira el consentimiento. Es la acción más importante de todo el módulo,
     * así que no exige una segunda confirmación más allá del propio envío:
     * poner otra barrera sería una forma sutil de impedir que el estudiante
     * ejerza su derecho.
     */
    public function revocar(Request $request, Consentimiento $consentimiento)
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($consentimiento->estudiante_id === $user->id, 403);

        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        $consentimiento->revocar($request->string('motivo')->toString() ?: null);

        return redirect()->route('privacidad.index')
            ->with('success', 'Retiraste tu consentimiento. Tu profesional ya no puede ver tus datos.');
    }
}
