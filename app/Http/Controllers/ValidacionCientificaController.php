<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelemetriaGameplay;
use App\Models\EvaluacionPsicometrica;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidacionCientificaController extends Controller
{
    /**
     * Clases afectivas normalizadas según el protocolo
     */
    protected array $clases = ['ansiedad', 'ira', 'frustracion', 'melancolia', 'felicidad', 'estres', 'tristeza'];

    /**
     * Renderiza el panel de validación estadística y métricas de IA.
     */
    public function index()
    {
        $telemetrias = TelemetriaGameplay::with('user')->latest()->get();
        $totalMuestras = $telemetrias->count();

        // 1. Inicialización de la Matriz de Confusión [Real][Predicho]
        $matrizConfusion = [];
        foreach ($this->clases as $real) {
            foreach ($this->clases as $pred) {
                $matrizConfusion[$real][$pred] = 0;
            }
        }

        $aciertosTotales = 0;
        $latenciaAcumulada = 0;
        $tappingAcumulado = 0;

        foreach ($telemetrias as $t) {
            $real = strtolower($t->emocion_objetivo);
            $pred = strtolower($t->emocion_predicha);

            if (isset($matrizConfusion[$real][$pred])) {
                $matrizConfusion[$real][$pred]++;
            }

            if ($real === $pred) {
                $aciertosTotales++;
            }

            $latenciaAcumulada += $t->latencia_promedio_ms;
            $tappingAcumulado += $t->frecuencia_tapping;
        }

        // 2. Cálculo de Exactitud Global (Accuracy)
        $accuracy = $totalMuestras > 0 ? round(($aciertosTotales / $totalMuestras) * 100, 2) : 0;
        $latenciaMedia = $totalMuestras > 0 ? round($latenciaAcumulada / $totalMuestras) : 0;
        $tappingMedio = $totalMuestras > 0 ? round($tappingAcumulado / $totalMuestras, 2) : 0;

        // 3. Cálculo de Precisión, Sensibilidad (Recall) y F1-Score por Clase
        $metricasPorClase = [];
        $sumPrecision = 0;
        $sumRecall = 0;
        $sumF1 = 0;
        $clasesEvaluadas = 0;

        foreach ($this->clases as $c) {
            $tp = $matrizConfusion[$c][$c] ?? 0;
            $fn = 0; // Falsos Negativos (Fila c, columnas != c)
            $fp = 0; // Falsos Positivos (Columna c, filas != c)

            foreach ($this->clases as $otra) {
                if ($otra !== $c) {
                    $fn += $matrizConfusion[$c][$otra] ?? 0;
                    $fp += $matrizConfusion[$otra][$c] ?? 0;
                }
            }

            $precision = ($tp + $fp) > 0 ? ($tp / ($tp + $fp)) : 0;
            $recall = ($tp + $fn) > 0 ? ($tp / ($tp + $fn)) : 0;
            $f1 = ($precision + $recall) > 0 ? (2 * ($precision * $recall) / ($precision + $recall)) : 0;

            if (($tp + $fn) > 0 || ($tp + $fp) > 0) {
                $sumPrecision += $precision;
                $sumRecall += $recall;
                $sumF1 += $f1;
                $clasesEvaluadas++;
            }

            $metricasPorClase[$c] = [
                'tp'        => $tp,
                'fp'        => $fp,
                'fn'        => $fn,
                'precision' => round($precision * 100, 1),
                'recall'    => round($recall * 100, 1),
                'f1'        => round($f1 * 100, 1),
            ];
        }

        $macroF1 = $clasesEvaluadas > 0 ? round(($sumF1 / $clasesEvaluadas) * 100, 2) : 0;
        $macroPrecision = $clasesEvaluadas > 0 ? round(($sumPrecision / $clasesEvaluadas) * 100, 2) : 0;
        $macroRecall = $clasesEvaluadas > 0 ? round(($sumRecall / $clasesEvaluadas) * 100, 2) : 0;

        // 4. Triangulación con Gold Standard (SISCO)
        $evaluacionesPsicometricas = EvaluacionPsicometrica::with('user')->latest()->take(10)->get();

        return view('admin.validacion', [
            'clases'                   => $this->clases,
            'matrizConfusion'          => $matrizConfusion,
            'totalMuestras'            => $totalMuestras,
            'aciertosTotales'          => $aciertosTotales,
            'accuracy'                 => $accuracy,
            'latenciaMedia'            => $latenciaMedia,
            'tappingMedio'             => $tappingMedio,
            'macroF1'                  => $macroF1,
            'macroPrecision'           => $macroPrecision,
            'macroRecall'              => $macroRecall,
            'metricasPorClase'         => $metricasPorClase,
            'telemetriasRecientes'     => $telemetrias->take(15),
            'evaluacionesPsicometricas'=> $evaluacionesPsicometricas,
        ]);
    }

    /**
     * [BIOÉTICA & SPSS]: Exporta el dataset anonimizado en formato CSV estándar.
     */
    public function exportarCSV(): StreamedResponse
    {
        $fileName = 'Dataset_StartEmotion_TelemetriaIA_' . date('Ymd_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function() {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para apertura correcta en Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados tabulares científicos
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
                'Timestamp_Captura'
            ]);

            TelemetriaGameplay::with('user')->chunk(100, function($registros) use ($handle) {
                foreach ($registros as $row) {
                    fputcsv($handle, [
                        $row->user ? $row->user->codigo_anonimo : 'TESVB-SIST-0000',
                        $row->minijuego_id,
                        $row->latencia_promedio_ms,
                        $row->frecuencia_tapping,
                        $row->tiempo_total_ms,
                        $row->conteo_rectificaciones,
                        $row->errores_diagnostico,
                        $row->score_final,
                        $row->emocion_objetivo,
                        $row->emocion_predicha,
                        $row->diagnostico_correcto ? '1' : '0',
                        $row->created_at->format('Y-m-d H:i:s')
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
