<?php

namespace App\Http\Controllers;

use App\Enums\AlcanceConsentimiento;
use App\Models\Consentimiento;
use App\Models\InvitacionPsicologo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Espacio de trabajo del profesional de psicología.
 *
 * Principio innegociable: todo lo que este controlador muestra está filtrado
 * por consentimiento vigente. No hay forma de pasar un id de estudiante por la
 * URL y ver sus registros: si no hay consentimiento, no hay datos.
 */
class PsicologiaController extends Controller
{
    private function profesional(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->esProfesional() ? $user : null;
    }

    /**
     * Los estudiantes que han autorizado a este profesional ver parte de sus
     * datos. Sin fila vigente, el estudiante no aparece en absoluto.
     */
    public function pacientes()
    {
        $profesional = $this->profesional();

        if (! $profesional) {
            abort(403);
        }

        $consentimientos = Consentimiento::query()
            ->vigentes()
            ->deProfesional($profesional->id)
            ->with('estudiante')
            ->latest('otorgado_en')
            ->get();

        return view('psicologia.pacientes', [
            'consentimientos' => $consentimientos,
            'total' => $consentimientos->count(),
        ]);
    }

    /**
     * Ficha de un estudiante. Antes de devolver nada se comprueba el
     * consentimiento vigente; si falta, la respuesta es 403 y no un 404 con
     * datos filtrados por el nombre.
     */
    public function paciente(Consentimiento $consentimiento)
    {
        $profesional = $this->profesional();

        abort_unless($profesional, 403);

        abort_unless($consentimiento->profesional_id === $profesional->id, 403);
        abort_unless($consentimiento->estaVigente(), 403);

        $estudiante = $consentimiento->estudiante;

        // Cada bloque se carga SOLO si el estudiante lo autorizó. Compartir el
        // registro emocional no debe arrastrar la evaluación psicométrica.
        $registros = $consentimiento->incluye(AlcanceConsentimiento::Registros)
            ? $estudiante->emociones()->latest()->limit(60)->get()
            : collect();

        $evaluaciones = $consentimiento->incluye(AlcanceConsentimiento::Evaluaciones)
            ? $estudiante->evaluacionesPsicometricas()->latest()->limit(20)->get()
            : collect();

        $telemetrias = $consentimiento->incluye(AlcanceConsentimiento::Juego)
            ? $estudiante->telemetriasGameplay()->latest()->limit(40)->get()
            : collect();

        return view('psicologia.paciente', [
            'consentimiento' => $consentimiento,
            'estudiante' => $estudiante,
            'registros' => $registros,
            'evaluaciones' => $evaluaciones,
            'telemetrias' => $telemetrias,
        ]);
    }

    /**
     * Genera un código de invitación. Es el único mecanismo por el que un
     * estudiante puede ser conectado a un profesional: no hay forma de que el
     * profesional "vincule" a alguien sin que esa persona lo acepte.
     */
    public function crearInvitacion()
    {
        $profesional = $this->profesional();

        abort_unless($profesional, 403);

        $invitacion = new InvitacionPsicologo;
        $invitacion->codigo = $this->generarCodigoUnico();
        $invitacion->profesional_id = $profesional->id;
        $invitacion->expira_en = now()->addDays(14);
        $invitacion->save();

        return redirect()->route('psicologia.invitaciones')
            ->with('success', 'Código generado. Entrégaselo a la persona estudiante para que lo use.');
    }

    public function revocarInvitacion(InvitacionPsicologo $invitacion)
    {
        $profesional = $this->profesional();

        abort_unless($profesional, 403);
        abort_unless($invitacion->profesional_id === $profesional->id, 403);

        if ($invitacion->usada_en === null) {
            $invitacion->delete();
        }

        return redirect()->route('psicologia.invitaciones')
            ->with('success', 'Código anulado.');
    }

    public function invitaciones()
    {
        $profesional = $this->profesional();

        abort_unless($profesional, 403);

        // Orden por `created_at`, que es la columna que crea $table->timestamps().
        // Las columnas con sufijo _en (otorgado_en, expira_en, usada_en) son
        // marcas del dominio; la de creación de la fila es la estándar de
        // Laravel y se llama created_at en las 11 migraciones del proyecto.
        $invitaciones = $profesional->invitaciones()
            ->with('usadaPor')
            ->orderByDesc('created_at')
            ->get();

        return view('psicologia.invitaciones', compact('invitaciones'));
    }

    /**
     * Alfabeto sin caracteres ambiguos (sin I, O, 0, 1, L, S): el código se
     * lee en voz alta o se teclea mirando un papel, y un 8 por un B arruinaría
     * la experiencia sin avisar de nada.
     */
    private function generarCodigoUnico(): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRTUVWXY2346789';

        do {
            $codigo = '';
            for ($i = 0; $i < 8; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while (InvitacionPsicologo::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
