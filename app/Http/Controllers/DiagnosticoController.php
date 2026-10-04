<?php

namespace App\Http\Controllers;

use App\Enums\EmocionEnum;
use App\Models\TelemetriaGameplay;
use App\Services\RandomForestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DiagnosticoController extends Controller
{
    protected RandomForestService $rfService;

    public function __construct(RandomForestService $rfService)
    {
        $this->rfService = $rfService;
    }

    public function index()
    {
        return view('minijuegos.index');
    }

    public function diagnostico()
    {
        return view('minijuegos.diagnostico');
    }

    public function guardarTelemetria(Request $request)
    {
        $juegoClases = EmocionEnum::juego();
        $rfClases = EmocionEnum::rf();

        $validated = $request->validate([
            'minijuego_id' => 'required|string|max:50',
            // Los límites superiores son guardas anti-corrupción, NO reglas de negocio:
            // una partida legítima debe persistir SIEMPRE. Si se rechazan muestras por
            // aquí, la matriz de validación se queda sin datos sin avisar.
            // 'latencia_promedio_ms' mide el intervalo entre clics, así que incluye el
            // tiempo de reflexión: un usuario que piensa 30s antes de su primer clic
            // promedia ~30000ms, y eso no es un dato corrupto.
            'latencia_promedio_ms' => 'required|numeric|min:0|max:120000',
            'frecuencia_tapping' => 'required|numeric|min:0|max:30',
            'tiempo_total_ms' => 'required|integer|min:0|max:300000',
            'conteo_rectificaciones' => 'required|integer|min:0|max:1000',
            'errores_diagnostico' => 'required|integer|min:0|max:1000',
            'score_final' => 'required|integer|min:0|max:100',
            'emocion_objetivo' => ['required', 'string', Rule::in($juegoClases)],
            // 'ninguno' es lo que el juego envía al agotarse el reloj sin haber elegido
            // tarjeta. Es una partida válida (fallada), no un payload corrupto.
            'emocion_predicha' => ['required', 'string', Rule::in(array_merge($juegoClases, $rfClases, ['ninguno']))],
            'diagnostico_correcto' => 'required|boolean',
            'vector_caracteristicas' => 'required|array|size:5',
            'vector_caracteristicas.0' => 'required|numeric|min:0|max:120000',
            'vector_caracteristicas.1' => 'required|numeric|min:0|max:30',
            'vector_caracteristicas.2' => 'required|integer|min:0|max:300000',
            'vector_caracteristicas.3' => 'required|integer|min:0|max:1000',
            'vector_caracteristicas.4' => 'required|integer|min:0|max:100',
        ]);

        $analisisIA = $this->rfService->predecir($validated['vector_caracteristicas']);

        $registro = new TelemetriaGameplay;
        $registro->fill([
            'minijuego_id' => $validated['minijuego_id'],
            'latencia_promedio_ms' => $validated['latencia_promedio_ms'],
            'frecuencia_tapping' => $validated['frecuencia_tapping'],
            'tiempo_total_ms' => $validated['tiempo_total_ms'],
            'conteo_rectificaciones' => $validated['conteo_rectificaciones'],
            'errores_diagnostico' => $validated['errores_diagnostico'],
            'score_final' => $validated['score_final'],
            'emocion_objetivo' => strtolower((string) $validated['emocion_objetivo']),
            'emocion_predicha' => $analisisIA['prediccion'],
            'diagnostico_correcto' => ($analisisIA['prediccion'] === strtolower((string) $validated['emocion_objetivo'])),
            'vector_caracteristicas' => array_merge($validated['vector_caracteristicas'], [
                'confianza_ia' => $analisisIA['confianza'],
                'probabilidades' => $analisisIA['probabilidades'],
                'seleccion_operador' => $validated['emocion_predicha'],
            ]),
        ]);
        $registro->user_id = Auth::id();
        $registro->save();

        return response()->json([
            'status' => 'success',
            // El mensaje que viajaba antes —«TELEMETRY_LOGGED: Inferencia de
            // Bosque Aleatorio completada con éxito»— era exactamente la jerga
            // que el comité pidió retirar, y además no lo mostraba nadie: la
            // interfaz lo sustituía por su propio texto. Aun así, viajaba.
            'message' => 'Partida registrada.',
            'id' => $registro->id,
            'ia_output' => array_merge($analisisIA, [
                // La clave va sin tilde porque es la de la base de datos; la
                // etiqueta es lo único que puede leerse.
                'etiqueta' => EmocionEnum::etiqueta($analisisIA['prediccion']),
            ]),
        ], 201);
    }
}
