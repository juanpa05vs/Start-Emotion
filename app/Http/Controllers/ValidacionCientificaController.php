<?php

namespace App\Http\Controllers;

use App\Enums\EmocionEnum;
use App\Models\EvaluacionPsicometrica;
use App\Models\TelemetriaGameplay;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidacionCientificaController extends Controller
{
    /**
     * Panel de validación estadística y métricas de IA.
     *
     * LÍMITE DE PRIVACIDAD QUE IMPONE ESTE CONTROLADOR
     *
     * Todo lo que se muestra aquí es AGREGADO: métricas calculadas sobre el
     * conjunto de la población. Una matriz de confusión, un accuracy o un
     * promedio de latencia no identifican a nadie, y son lo que un comité de
     * ética y una tesis necesitan.
     *
     * Lo que este controlador NO hace —y antes sí hacía— es mostrar resultados
     * individuales: "el participante X obtuvo ansiedad con estrés severo". Eso
     * es el mismo dato clínico que el espacio del psicólogo solo puede ver con
     * consentimiento vigente, y aquí se veía sin ninguno. Por eso no se cargan
     * las relaciones `user` ni se enumeran filas por persona: no es que se
     * oculten en la vista, es que los datos no llegan a existir para esta
     * pantalla.
     */
    public function index()
    {
        // Sin `with('user')`: ninguna consulta de este panel trae nombres,
        // correos ni códigos de participante.
        $telemetrias = TelemetriaGameplay::latest()->get();
        $totalMuestras = $telemetrias->count();

        $clases = EmocionEnum::validacion();

        // Matriz de confusión [Real][Predicho]
        $matrizConfusion = [];
        foreach ($clases as $real) {
            foreach ($clases as $pred) {
                $matrizConfusion[$real][$pred] = 0;
            }
        }

        $aciertosTotales = 0;
        $latenciaAcumulada = 0.0;
        $tappingAcumulado = 0.0;
        $muestrasIncluidas = 0;

        // Etiquetas que no se pueden colapsar a una clase de la matriz. Se contabilizan
        // para que el panel pueda decir "N muestras descartadas por etiqueta desconocida"
        // en vez de mostrar métricas infladas por un subconjunto silencioso.
        $descartadas = [];

        foreach ($telemetrias as $t) {
            $realBase = strtolower(trim((string) $t->emocion_objetivo));
            $predBase = strtolower(trim((string) $t->emocion_predicha));

            $realNorm = EmocionEnum::normalizarParaRF($realBase);
            $predNorm = EmocionEnum::normalizarParaRF($predBase);

            if (! in_array($realNorm, $clases, true) || ! in_array($predNorm, $clases, true)) {
                $descartadas[] = ['real' => $realBase, 'pred' => $predBase];

                continue;
            }

            $matrizConfusion[$realNorm][$predNorm]++;
            $muestrasIncluidas++;

            if ($realNorm === $predNorm) {
                $aciertosTotales++;
            }

            $latenciaAcumulada += (float) ($t->latencia_promedio_ms ?? 0);
            $tappingAcumulado += (float) ($t->frecuencia_tapping ?? 0);
        }

        // El denominador es el de las muestras QUE ENTRARON en la matriz, no el total
        // bruto: si no, el accuracy se calcularía sobre una población distinta de la
        // que la matriz representa. Ver $descartadas.
        $accuracy = $muestrasIncluidas > 0 ? round(($aciertosTotales / $muestrasIncluidas) * 100, 2) : 0;
        $latenciaMedia = $muestrasIncluidas > 0 ? round($latenciaAcumulada / $muestrasIncluidas, 2) : 0;
        $tappingMedio = $muestrasIncluidas > 0 ? round($tappingAcumulado / $muestrasIncluidas, 2) : 0;

        $metricasPorClase = [];
        $sumPrecision = 0.0;
        $sumRecall = 0.0;
        $sumF1 = 0.0;
        $clasesEvaluadas = 0;

        foreach ($clases as $c) {
            $tp = $matrizConfusion[$c][$c] ?? 0;
            $fn = 0;
            $fp = 0;

            foreach ($clases as $otra) {
                if ($otra !== $c) {
                    $fn += $matrizConfusion[$c][$otra] ?? 0;
                    $fp += $matrizConfusion[$otra][$c] ?? 0;
                }
            }

            $precision = ($tp + $fp) > 0 ? ($tp / ($tp + $fp)) : 0.0;
            $recall = ($tp + $fn) > 0 ? ($tp / ($tp + $fn)) : 0.0;
            $f1 = ($precision + $recall) > 0 ? (2 * ($precision * $recall) / ($precision + $recall)) : 0.0;

            if (($tp + $fn) > 0 || ($tp + $fp) > 0 || $tp > 0) {
                $sumPrecision += $precision;
                $sumRecall += $recall;
                $sumF1 += $f1;
                $clasesEvaluadas++;
            }

            $metricasPorClase[$c] = [
                'tp' => $tp,
                'fp' => $fp,
                'fn' => $fn,
                'precision' => round($precision * 100, 1),
                'recall' => round($recall * 100, 1),
                'f1' => round($f1 * 100, 1),
            ];
        }

        $macroF1 = $clasesEvaluadas > 0 ? round(($sumF1 / $clasesEvaluadas) * 100, 2) : 0.0;
        $macroPrecision = $clasesEvaluadas > 0 ? round(($sumPrecision / $clasesEvaluadas) * 100, 2) : 0.0;
        $macroRecall = $clasesEvaluadas > 0 ? round(($sumRecall / $clasesEvaluadas) * 100, 2) : 0.0;

        // Distribución de clases reales, agregada. Antes el panel listaba las 10
        // últimas evaluaciones una por una (participante + estado afectivo +
        // nivel de estrés), que es historial clínico individual sin
        // consentimiento. Contar cuántos casos hay de cada clase sirve igual
        // para calibrar y no identifica a nadie.
        $distribucionEvaluaciones = EvaluacionPsicometrica::query()
            ->selectRaw('nivel_estres, COUNT(*) as total')
            ->groupBy('nivel_estres')
            ->pluck('total', 'nivel_estres')
            ->map(fn ($n) => (int) $n)
            ->all();

        $totalEvaluaciones = array_sum($distribucionEvaluaciones);

        // Cuántas personas distintas han jugado. Un número, no una lista: sirve
        // para saber si la muestra es suficiente y no dice quién.
        $participantesTelemetria = TelemetriaGameplay::query()
            ->distinct()
            ->count('user_id');

        return view('admin.validacion', [
            'clases' => $clases,
            'matrizConfusion' => $matrizConfusion,
            'totalMuestras' => $totalMuestras,
            'aciertosTotales' => $aciertosTotales,
            'accuracy' => $accuracy,
            'latenciaMedia' => $latenciaMedia,
            'tappingMedio' => $tappingMedio,
            'macroF1' => $macroF1,
            'macroPrecision' => $macroPrecision,
            'macroRecall' => $macroRecall,
            'metricasPorClase' => $metricasPorClase,
            'muestrasIncluidasMatriz' => $muestrasIncluidas,
            'muestrasDescartadas' => count($descartadas),
            'detalleDescartadas' => array_slice($descartadas, 0, 10),
            'mapaColapso' => EmocionEnum::mapaColapso(),
            'distribucionEvaluaciones' => $distribucionEvaluaciones,
            'totalEvaluaciones' => $totalEvaluaciones,
            'participantesTelemetria' => $participantesTelemetria,
        ]);
    }

    public function exportarCSV(): StreamedResponse
    {
        $fileName = 'Dataset_StartEmotion_TelemetriaIA_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID_Anonimizado',
                'Minijuego',
                'Latencia_Promedio_MS',
                'Frecuencia_Tapping_Hz',
                'Tiempo_Total_MS',
                'Conteo_Rectificaciones',
                'Errores_Cometidos',
                'Puntaje_Integridad',
                'Emocion_Objetivo_Real',
                'Prediccion_RandomForest',
                'Diagnostico_Correcto',
                'Timestamp_Captura',
            ]);

            // El dataset se genera SIN cargar la tabla de usuarios: el código
            // de participante se calcula a partir del user_id, así que este
            // archivo no puede contener un nombre ni un correo aunque alguien
            // añadiera una columna por error. El código es un HMAC, no el id.
            TelemetriaGameplay::chunk(500, function ($registros) use ($handle) {
                foreach ($registros as $row) {
                    fputcsv($handle, [
                        User::codigoParticipante($row->user_id),
                        $row->minijuego_id,
                        $row->latencia_promedio_ms,
                        $row->frecuencia_tapping,
                        $row->tiempo_total_ms,
                        $row->conteo_rectificaciones,
                        $row->errores_diagnostico,
                        $row->score_final,
                        strtolower((string) $row->emocion_objetivo),
                        strtolower((string) $row->emocion_predicha),
                        $row->diagnostico_correcto ? '1' : '0',
                        $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : null,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
