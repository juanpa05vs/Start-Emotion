<?php

namespace App\Http\Controllers;

use App\Models\EvaluacionPsicometrica;
use Illuminate\Http\Request;
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
            'e_sobrecarga' => 'required|integer|between:1,5',
            'e_evaluaciones' => 'required|integer|between:1,5',
            'e_tiempo' => 'required|integer|between:1,5',
            'e_profesores' => 'required|integer|between:1,5',

            // Dimensión 2: Reacciones / Síntomas (4 reactivos)
            's_fatiga' => 'required|integer|between:1,5',
            's_ansiedad' => 'required|integer|between:1,5',
            's_concentracion' => 'required|integer|between:1,5',
            's_frustracion' => 'required|integer|between:1,5',

            // Dimensión 3: Afrontamiento (2 reactivos)
            'a_resolucion' => 'required|integer|between:1,5',
            'a_comunicacion' => 'required|integer|between:1,5',
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
        $nivelEstres = match (true) {
            $scoreGlobal >= 70 => 'severo',
            $scoreGlobal >= 40 => 'moderado',
            default => 'bajo',
        };

        // 5. Asignación del Ground Truth Afectivo según sintomatología predominante
        $estadoPredominante = $this->determinarEstadoAfectivoPredominante($validated, $scoreGlobal);

        // 6. Persistencia del registro clínico
        // 'user_id' no está en $fillable (a propósito: evita asignación masiva), por eso
        // se crea la entidad y se asigna la FK como propiedad, no vía create().
        $evaluacion = new EvaluacionPsicometrica;
        $evaluacion->instrumento = 'SISCO_ESTRES';
        $evaluacion->puntaje_estresores = $scoreEstresores;
        $evaluacion->puntaje_sintomas = $scoreSintomas;
        $evaluacion->puntaje_afrontamiento = $scoreAfrontamiento;
        $evaluacion->puntaje_global = $scoreGlobal;
        $evaluacion->nivel_estres = $nivelEstres;
        $evaluacion->estado_afectivo_predominante = $estadoPredominante;
        $evaluacion->respuestas_detalle = $validated;
        $evaluacion->user_id = Auth::id();
        $evaluacion->save();

        // El mensaje es lo único que ve quien acaba de enviar el cuestionario, y antes
        // decía «Calibración Psicométrica completada con éxito (Nivel: Severo)»:
        // tres términos que no significan nada fuera del equipo, y un «éxito» que
        // celebrate el guardado en lugar de decir qué ha pasado. Además daba el
        // nivel sin decir qué significa, que es justo lo que deja con dudas a
        // quien ha contestado.
        $mensaje = $nivelEstres === 'bajo'
            ? 'Respuesta guardada. Tu nivel de estrés está dentro de lo habitual.'
            : ($nivelEstres === 'moderado'
                ? 'Respuesta guardada. Tu nivel de estrés está por encima de lo habitual: puede ser buena idea hablarlo con alguien.'
                : 'Respuesta guardada. Tu nivel de estrés está alto. Si te cuesta mucho, considera hablarlo con el profesional de psicología de tu centro.');

        return redirect()->route('dashboard')->with('success', $mensaje);
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
