<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelemetriaGameplay;
use App\Services\RandomForestService;
use Illuminate\Support\Facades\Auth;

class DiagnosticoController extends Controller
{
    /**
     * Motor de Inteligencia Artificial (Bosques Aleatorios).
     */
    protected RandomForestService $rfService;

    public function __construct(RandomForestService $rfService)
    {
        $this->rfService = $rfService;
    }

    /**
     * Muestra el catálogo principal de minijuegos.
     */
    public function index()
    {
        return view('minijuegos.index');
    }

    /**
     * Carga el entorno de juego "Código Anómalo".
     */
    public function diagnostico()
    {
        return view('minijuegos.diagnostico');
    }

    /**
     * [INGENIERÍA CIENTÍFICA]: Procesa el vector con Bosques Aleatorios y persiste la telemetría.
     */
    public function guardarTelemetria(Request $request)
    {
        $validated = $request->validate([
            'minijuego_id'           => 'required|string',
            'latencia_promedio_ms'   => 'required|numeric',
            'frecuencia_tapping'     => 'required|numeric',
            'tiempo_total_ms'        => 'required|integer',
            'conteo_rectificaciones' => 'required|integer',
            'errores_diagnostico'    => 'required|integer',
            'score_final'            => 'required|integer',
            'emocion_objetivo'       => 'required|string',
            'emocion_predicha'       => 'required|string',
            'diagnostico_correcto'   => 'required|boolean',
            'vector_caracteristicas' => 'required|array',
        ]);

        // 🧠 1. EJECUCIÓN DEL MODELO RANDOM FOREST SOBRE EL VECTOR CONDUCTUAL
        $analisisIA = $this->rfService->predecir($validated['vector_caracteristicas']);

        // 💾 2. PERSISTENCIA EN BASE DE DATOS
        $registro = TelemetriaGameplay::create([
            'user_id'                => auth::id(),
            'minijuego_id'           => $validated['minijuego_id'],
            'latencia_promedio_ms'   => $validated['latencia_promedio_ms'],
            'frecuencia_tapping'     => $validated['frecuencia_tapping'],
            'tiempo_total_ms'        => $validated['tiempo_total_ms'],
            'conteo_rectificaciones' => $validated['conteo_rectificaciones'],
            'errores_diagnostico'    => $validated['errores_diagnostico'],
            'score_final'            => $validated['score_final'],
            'emocion_objetivo'       => $validated['emocion_objetivo'],
            'emocion_predicha'       => $analisisIA['prediccion'], // Clasificación generada por el Ensamble
            'diagnostico_correcto'   => ($analisisIA['prediccion'] === $validated['emocion_objetivo']),
            'vector_caracteristicas' => array_merge($validated['vector_caracteristicas'], [
                'confianza_ia'       => $analisisIA['confianza'],
                'probabilidades'     => $analisisIA['probabilidades'],
                'seleccion_operador' => $validated['emocion_predicha'],
            ]),
        ]);

        // 🚀 3. RETORNO ASÍNCRONO DE TELEMETRÍA Y RESPUESTA IA
        return response()->json([
            'status'    => 'success',
            'message'   => 'TELEMETRY_LOGGED: Inferencia de Bosque Aleatorio completada con éxito.',
            'id'        => $registro->id,
            'ia_output' => $analisisIA
        ], 201);
    }
}
