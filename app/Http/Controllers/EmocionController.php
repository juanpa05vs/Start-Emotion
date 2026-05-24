<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroEmocion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EmocionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'emocion'       => 'required|string|in:felicidad,entusiasta,productivo,relajado,tristeza,melancolia,agotado,ansioso,ira',
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

        RegistroEmocion::create([
            'user_id'               => auth()->id(),
            'emocion'               => $request->emocion,
            'energia'               => $request->energia,
            'nivel_estres_estimado' => $analisis['estres'],
            'recomendacion'         => $analisis['recomendacion'],
            'observaciones'         => $request->observaciones,
            'contexto'              => $request->contexto,
            'alerta_burnout'        => $analisis['burnout'],
        ]);

        return redirect()->route('dashboard')->with('status', 'BIO-SYNC: Análisis de Red Completado');
    }

    private function motorAnalisisNeural($emocion, $energia, $obs, $ctx)
    {
        $ultimosRegistros = auth()->user()->emociones()->latest()->take(3)->pluck('energia');
        $promedioEnergia = $ultimosRegistros->count() > 0 ? $ultimosRegistros->avg() : $energia;

        // Alerta de colapso si los estados de baja energía se cronifican por debajo del 35%
        $alertaBurnout = (in_array($emocion, ['tristeza', 'melancolia', 'agotado']) && $promedioEnergia < 35 && $energia < 35);

        $estresPorTexto = 0;
        $lexicoEstudiantil = [
            'examen' => 15, 'entregar' => 10, 'calificación' => 10,
            'reprobar' => 20, 'presión' => 15, 'difícil' => 10, 'tesvb' => 5
        ];

        if ($obs) {
            $obsLower = strtolower($obs);
            foreach ($lexicoEstudiantil as $palabra => $peso) {
                if (str_contains($obsLower, $palabra)) $estresPorTexto += $peso;
            }
        }

        $ajusteContexto = match($ctx) {
            'Exámenes' => 15, 'Proyecto' => 10, 'Clases' => 5, default => 0,
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
            $burnout     => "⚠️ ALERTA BURNOUT: Tu procesador mental está al límite. Pausa obligatoria. ",
            $estres > 80 => "🔥 SOBRECARGA DE TENSIÓN: Atenúa las tareas críticas de inmediato. ",
            $energia < 30 && in_array($emocion, ['tristeza', 'melancolia', 'agotado']) => "🪫 ENERGÍA EN RESERVA: Reduce el consumo de recursos cognitivos. ",
            default      => "✨ SEÑAL ESTABLE: Núcleo operando bajo parámetros equilibrados. "
        };

        $nucleo = match($emocion) {
            'felicidad'  => "Disfruta de este estado de balance; es un excelente momento para documentar avances.",
            'entusiasta' => "Alta motivación detectada. Aprovecha este pico para liquidar los pendientes más complejos.",
            'productivo' => "Estás en estado de flujo (Flow). Mantén el enfoque y evita las distracciones externas.",
            'relajado'   => "Fase de enfriamiento óptima. Ideal para planificar la arquitectura de tus próximos días.",
            'tristeza'   => "Señal de baja frecuencia. No te presiones por producir; el sistema requiere procesar datos afectivos.",
            'melancolia' => "Reflexión profunda. Un espacio de desconexión analógica te vendrá excelente.",
            'agotado'    => "Fatiga física acumulada. Cierra el IDE de programación y recupera horas de sueño.",
            'ansioso'    => "Ciclo síncrono acelerado. Fragmenta tus entregas en micro-tareas para reducir la incertidumbre.",
            'ira'        => "Pico de sobrevoltaje. Aléjate de la consola, respira en bloques de 4 segundos y disipa la tensión.",
            default      => "Sigue monitoreando los logs de tu comportamiento diario."
        };

        $cierre = match($ctx) {
            'Exámenes' => " Un examen mide memoria temporal, no tus capacidades como ingeniero.",
            'Proyecto' => " Haz commits pequeños. ¡Y no olvides hidratar el sistema operativo de tu cuerpo!",
            'Clases'   => " Toma apuntes estructurados y estira los músculos entre módulos de clase.",
            default    => " ¡Optimiza tus bloques de tiempo hoy!"
        };

        return $prefijo . $nucleo . $cierre;
    }

    private function calcularEstresSimulado($emocion, $energia)
    {
        // La Ira y la Ansiedad disparan el estrés independientemente de la energía;
        // En la tristeza o el agotamiento, a MENOR energía, MAYOR es el estrés por desgana.
        $base = match($emocion) {
            'ira'        => 85,
            'ansioso'    => 75,
            'agotado'    => 60,
            'tristeza'   => 50,
            'melancolia' => 45,
            'productivo' => 20,
            'relajado'   => 10,
            'felicidad', 'entusiasta' => 5,
            default      => 30,
        };

        if (in_array($emocion, ['ira', 'ansioso', 'felicidad', 'entusiasta'])) {
            $ajuste = $energia / 5; // A mayor intensidad en estas, se acentúa su naturaleza
            return round(min(100, $base + $ajuste));
        } else {
            $ajuste = (100 - $energia) / 3; // En las pasivas, menos energía implica más estrés interno
            return round($base + $ajuste);
        }
    }

    public function verCalendario() {
        $inicioMes = now()->startOfMonth(); $finMes = now()->endOfMonth();
        $rangoDias = CarbonPeriod::create($inicioMes, $finMes);
        $datosHeatmap = auth()->user()->emociones()->selectRaw('DATE(created_at) as fecha, AVG(energia) as promedio')->whereBetween('created_at', [$inicioMes, $finMes])->groupBy('fecha')->get()->pluck('promedio', 'fecha');
        return view('perfil.calendario', compact('datosHeatmap', 'rangoDias'));
    }
    public function index() { $historial = auth()->user()->emociones()->latest()->get(); return view('historial', compact('historial')); }
    public function generarPDF() { $historial = auth()->user()->emociones()->latest()->get(); $user = auth()->user(); $pdf = Pdf::loadView('reportes.emociones', compact('historial', 'user')); return $pdf->download("Reporte_Neural_S-Emotion_{$user->nombre}.pdf"); }
    public function destroy($id) { auth()->user()->emociones()->findOrFail($id)->delete(); return back()->with('status', 'Registro eliminado del sector de memoria.'); }
    public function reiniciarHistorial() { auth()->user()->emociones()->delete(); return back()->with('status', 'MEMORIA PURGADA: El historial ha sido reiniciado.'); }
    public function eliminarSeleccionados(Request $request) { $request->validate(['ids' => 'required|array']); auth()->user()->emociones()->whereIn('id', $request->ids)->delete(); return back()->with('status', 'SISTEMA ACTUALIZADO: Registros purgados.'); }
}
