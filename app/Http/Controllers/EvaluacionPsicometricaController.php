<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EvaluacionPsicometrica;
use Illuminate\Support\Facades\Auth;

class EvaluacionPsicometricaController extends Controller
{
    /**
     * Muestra la interfaz de evaluación psicométrica estandarizada.
     */
    public function create()
    {
      //  /** @var \App\Models\Usuario|null $user */
        $user = Auth::user();
        $ultimaEvaluacion = EvaluacionPsicometrica::where('user_id', Auth::id())->latest()->first();

        return view('evaluaciones.sisco', compact('ultimaEvaluacion', 'user'));
    }

    /**
     * Procesa los reactivos Likert y genera el Ground Truth psicométrico.
     */
    public function store(Request $request)
    {
        // 1. Validación de reactivos de escala Likert (1 = Nunca, 5 = Siempre)
        $validated = $request->validate([
            // Dimensión 1: Estresores Académicos (4 reactivos)
            'e_sobrecarga'    => 'required|integer|between:1,5',
            'e_evaluaciones'  => 'required|integer|between:1,5',
            'e_tiempo'        => 'required|integer|between:1,5',
            'e_profesores'    => 'required|integer|between:1,5',

            // Dimensión 2: Reacciones / Síntomas (4 reactivos)
            's_fatiga'        => 'required|integer|between:1,5',
            's_ansiedad'      => 'required|integer|between:1,5',
            's_concentracion' => 'required|integer|between:1,5',
            's_frustracion'   => 'required|integer|between:1,5',

            // Dimensión 3: Afrontamiento (2 reactivos)
            'a_resolucion'    => 'required|integer|between:1,5',
            'a_comunicacion'  => 'required|integer|between:1,5',
        ]);

        // 2. Normalización de subescalas a porcentajes (0 a 100%)
        // Fórmula: (Suma Obtenida - Mínimo Posible) / (Máximo Posible - Mínimo Posible) * 100
        $rawEstresores = $validated['e_sobrecarga'] + $validated['e_evaluaciones'] + $validated['e_tiempo'] + $validated['e_profesores'];
        $scoreEstresores = (int) round((($rawEstresores - 4) / 16) * 100);

        $rawSintomas = $validated['s_fatiga'] + $validated['s_ansiedad'] + $validated['s_concentracion'] + $validated['s_frustracion'];
        $scoreSintomas = (int) round((($rawSintomas - 4) / 16) * 100);

        $rawAfrontamiento = $validated['a_resolucion'] + $validated['a_comunicacion'];
        $scoreAfrontamiento = (int) round((($rawAfrontamiento - 2) / 8) * 100);

        // 3. Índice Global de Estrés SISCO (Ponderado: 40% Estresores + 50% Síntomas - 10% Afrontamiento)
        $scoreGlobal = (int) round(max(0, min(100, ($scoreEstresores * 0.40) + ($scoreSintomas * 0.50) + ((100 - $scoreAfrontamiento) * 0.10))));

        // 4. Baremación Clínica (Nivel de Estrés)
        $nivelEstres = match(true) {
            $scoreGlobal >= 70 => 'severo',
            $scoreGlobal >= 40 => 'moderado',
            default            => 'bajo',
        };

        // 5. Asignación del Ground Truth Afectivo según sintomatología predominante
        $estadoPredominante = $this->determinarEstadoAfectivoPredominante($validated, $scoreGlobal);

        // 6. Persistencia del registro clínico
        EvaluacionPsicometrica::create([
            'user_id'                      => Auth::id(),
            'instrumento'                  => 'SISCO_ESTRES',
            'puntaje_estresores'           => $scoreEstresores,
            'puntaje_sintomas'             => $scoreSintomas,
            'puntaje_afrontamiento'        => $scoreAfrontamiento,
            'puntaje_global'               => $scoreGlobal,
            'nivel_estres'                 => $nivelEstres,
            'estado_afectivo_predominante' => $estadoPredominante,
            'respuestas_detalle'           => $validated,
        ]);

        return redirect()->route('dashboard')->with('success', "Calibración Psicométrica completada con éxito (Nivel: " . ucfirst($nivelEstres) . ").");
    }

    /**
     * Algoritmo de mapeo sintomatológico para clasificación objetiva.
     */
    private function determinarEstadoAfectivoPredominante(array $r, int $scoreGlobal): string
    {
        if ($scoreGlobal < 35 && $r['a_resolucion'] >= 4) {
            return 'alegria';
        }

        if ($r['s_ansiedad'] >= 4 && $r['e_tiempo'] >= 4) {
            return 'ansiedad';
        }

        if ($r['s_frustracion'] >= 4 || $r['e_profesores'] >= 4) {
            return 'frustracion';
        }

        if ($r['s_fatiga'] >= 4 && $r['s_concentracion'] >= 4) {
            return 'tristeza';
        }

        return 'neutralidad';
    }
}
