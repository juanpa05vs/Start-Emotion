<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroEmocion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EmocionController extends Controller
{
    /**
     * CAPA DE LÓGICA: Captura de datos con Motor de Análisis Neural Humano.
     * [REPARACIÓN]: Se actualizó 'usuario_id' a 'user_id' para sincronizar con la BD.
     */
    public function store(Request $request)
    {
        $request->validate([
            'emocion'       => 'required|string',
            'energia'       => 'required|integer|min:1|max:100',
            'observaciones' => 'nullable|string|max:1000',
            'contexto'      => 'nullable|string',
        ]);

        $analisis = $this->motorAnalisisNeural(
            $request->emocion,
            $request->energia,
            $request->observaciones,
            $request->contexto
        );

        // --- EL PUNTO CRÍTICO DE REPARACIÓN ---
        RegistroEmocion::create([
            'user_id'               => auth()->id(), // ✅ Corregido: user_id
            'emocion'               => $request->emocion,
            'energia'               => $request->energia,
            'nivel_estres_estimado' => $analisis['estres'],
            'recomendacion'         => $analisis['recomendacion'],
            'observaciones'         => $request->observaciones,
            'contexto'              => $request->contexto,
            'alerta_burnout'        => $analisis['burnout'],
        ]);

        return redirect()->route('dashboard')->with('status', 'BIO-SYNC: Análisis Neural Completado');
    }

    /**
     * MOTOR DE ANÁLISIS NEURAL
     */
    private function motorAnalisisNeural($emocion, $energia, $obs, $ctx)
    {
        // NIVEL 1: PREDICCIÓN DE FATIGA
        // Usamos la relación definida en User.php que ya está corregida
        $ultimosRegistros = auth()->user()->emociones()->latest()->take(3)->pluck('energia');
        $promedioEnergia = $ultimosRegistros->count() > 0 ? $ultimosRegistros->avg() : $energia;
        $alertaBurnout = ($promedioEnergia < 40 && $energia < 40);

        // NIVEL 2: NLP UNIVERSAL (Diccionario TESVB)
        $estresPorTexto = 0;
        $lexicoEstudiantil = [
            'examen'       => 15,
            'entregar'     => 10,
            'calificación' => 10,
            'reprobar'     => 20,
            'presión'      => 15,
            'difícil'      => 10,
            'tesvb'        => 5
        ];

        if ($obs) {
            $obsLower = strtolower($obs);
            foreach ($lexicoEstudiantil as $palabra => $peso) {
                if (str_contains($obsLower, $palabra)) $estresPorTexto += $peso;
            }
        }

        // NIVEL 3: CORRELACIÓN CONTEXTUAL
        $ajusteContexto = match($ctx) {
            'Exámenes' => 15,
            'Proyecto' => 10,
            'Clases'   => 5,
            default    => 0,
        };

        $estresBase = $this->calcularEstresSimulado($emocion, $energia);
        $estresFinal = min(100, $estresBase + $estresPorTexto + $ajusteContexto);

        $recomendacion = $this->ensamblarRecomendacionNeural($emocion, $energia, $estresFinal, $ctx, $alertaBurnout);

        return [
            'estres'        => $estresFinal,
            'recomendacion' => $recomendacion,
            'burnout'       => $alertaBurnout
        ];
    }

    private function ensamblarRecomendacionNeural($emocion, $energia, $estres, $ctx, $burnout)
    {
        $prefijo = match(true) {
            $burnout     => "⚠️ AVISO DE FATIGA: Tu cuerpo está pidiendo una pausa necesaria. ",
            $estres > 80 => "🔥 NIVEL DE TENSIÓN ALTO: Estás bajo mucha presión en este momento. ",
            $energia < 25 => "🪫 BATERÍA BAJA: Tus niveles de energía están al mínimo. ",
            default      => "✨ ESTADO ESTABLE: Tu rendimiento actual es equilibrado. "
        };

        $nucleo = match($emocion) {
            'Ansioso'    => "Intenta dividir tus pendientes en pasos muy pequeños para no abrumarte. ",
            'Agotado'    => "Es momento de desconectarte de todo. Un descanso de 15 minutos te ayudará a reiniciar. ",
            'Entusiasta' => "¡Estás en un gran momento! Aprovecha para avanzar en tus tareas más pesadas. ",
            'Productivo' => "Vas por muy buen camino. Sigue manteniendo ese ritmo constante. ",
            'Relajado'   => "Disfruta este momento de calma para organizar lo que sigue en tu día. ",
            default      => "Sigue monitoreando cómo te sientes para cuidar tu bienestar. "
        };

        $cierre = match($ctx) {
            'Exámenes' => "Respira profundo; recuerda que un examen no define todo tu potencial.",
            'Proyecto' => "Paso a paso se llega a la meta. ¡No olvides hidratarte mientras trabajas!",
            'Clases'   => "Trata de mantener la atención y toma descansos breves entre materias.",
            default    => "¡Mucho éxito en tus actividades de hoy!"
        };

        return $prefijo . $nucleo . $cierre;
    }

    /**
     * 📅 CALENDARIO DE ESTABILIDAD (Heatmap)
     */
    public function verCalendario()
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $rangoDias = CarbonPeriod::create($inicioMes, $finMes);

        $datosHeatmap = auth()->user()->emociones()
            ->selectRaw('DATE(created_at) as fecha, AVG(energia) as promedio')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->groupBy('fecha')
            ->get()
            ->pluck('promedio', 'fecha');

        return view('perfil.calendario', compact('datosHeatmap', 'rangoDias'));
    }

    public function index()
    {
        $historial = auth()->user()->emociones()->latest()->get();
        return view('historial', compact('historial'));
    }

    public function generarPDF()
    {
        $historial = auth()->user()->emociones()->latest()->get();
        $user = auth()->user();
        $pdf = Pdf::loadView('reportes.emociones', compact('historial', 'user'));
        return $pdf->download("Reporte_Neural_S-Emotion_{$user->nombre}.pdf");
    }

    public function destroy($id)
    {
        auth()->user()->emociones()->findOrFail($id)->delete();
        return back()->with('status', 'Registro eliminado del sector de memoria.');
    }

    public function reiniciarHistorial()
    {
        auth()->user()->emociones()->delete();
        return back()->with('status', 'MEMORIA PURGADA: El historial ha sido reiniciado.');
    }

    public function eliminarSeleccionados(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        auth()->user()->emociones()->whereIn('id', $request->ids)->delete();
        return back()->with('status', 'SISTEMA ACTUALIZADO: Registros purgados.');
    }

    private function calcularEstresSimulado($emocion, $energia)
    {
        $base = match($emocion) {
            'Ansioso'    => 75,
            'Agotado'    => 55,
            'Entusiasta' => 15,
            'Productivo' => 25,
            default      => 35,
        };
        $ajusteEnergia = (100 - $energia) / 4;
        return round($base + $ajusteEnergia);
    }
}
