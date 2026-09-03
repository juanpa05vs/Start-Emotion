<?php

namespace App\Services;

class RandomForestService
{
    /**
     * Clases objetivo discretas del protocolo FECIEM 2026
     */
    protected array $clases = ['alegria', 'tristeza', 'ansiedad', 'frustracion', 'neutralidad'];

    /**
     * Ejecuta la inferencia del ensamble de Árboles de Decisión sobre el vector conductual.
     *
     * @param array $vector [latencia_ms, tapping_rate, tiempo_ms, rectificaciones, score]
     * @return array
     */
    public function predecir(array $vector): array
    {
        $latencia       = $vector[0] ?? 1200;
        $tapping        = $vector[1] ?? 1.0;
        $tiempoTotal    = $vector[2] ?? 30000;
        $rectificaciones = $vector[3] ?? 2;
        $score          = $vector[4] ?? 100;

        // Vector de votos emitidos por los árboles del ensamble
        $votos = [
            'alegria'     => 0,
            'tristeza'    => 0,
            'ansiedad'    => 0,
            'frustracion' => 0,
            'neutralidad' => 0,
        ];

        // --- ÁRBOL 1: Discriminador de Activación Motriz y Tapping (Cadencia) ---
        if ($tapping >= 1.8) {
            $votos[$latencia < 1000 ? 'ansiedad' : 'frustracion'] += 1.5;
        } elseif ($tapping <= 0.6) {
            $votos['tristeza'] += 1.5;
        } else {
            $votos['neutralidad'] += 1.0;
        }

        // --- ÁRBOL 2: Discriminador de Latencia Cognitiva (Tiempo de Reacción) ---
        if ($latencia < 800) {
            $votos[$score >= 80 ? 'alegria' : 'ansiedad'] += 1.5;
        } elseif ($latencia > 2500) {
            $votos[$rectificaciones >= 4 ? 'frustracion' : 'tristeza'] += 1.5;
        } else {
            $votos['neutralidad'] += 1.0;
        }

        // --- ÁRBOL 3: Evaluador de Vacilación y Rectificaciones (Duda/Aversión) ---
        if ($rectificaciones >= 5) {
            $votos['frustracion'] += 2.0;
            $votos['ansiedad'] += 1.0;
        } elseif ($rectificaciones <= 1) {
            $votos[$score >= 80 ? 'alegria' : 'neutralidad'] += 1.5;
        } else {
            $votos['neutralidad'] += 1.0;
        }

        // --- ÁRBOL 4: Análisis de Desempeño y Resiliencia (Score vs Tiempo) ---
        if ($score >= 80 && $tiempoTotal <= 25000) {
            $votos['alegria'] += 2.0;
        } elseif ($score <= 40 && $tiempoTotal >= 40000) {
            $votos['frustracion'] += 1.5;
            $votos['tristeza'] += 1.0;
        } elseif ($tiempoTotal >= 45000) {
            $votos['ansiedad'] += 1.0;
        }

        // --- ÁRBOL 5: Ensamble Multivariable Cruzado ---
        if ($tapping > 1.2 && $rectificaciones >= 3) {
            $votos['ansiedad'] += 1.8;
        } elseif ($tapping < 0.8 && $latencia > 2000) {
            $votos['tristeza'] += 1.8;
        }

        // Normalización probabilística mediante función Softmax
        $probabilidades = $this->calcularProbabilidades($votos);

        // Determinación de la clase dominante
        arsort($probabilidades);
        $clasePredicha = array_key_first($probabilidades);
        $confianza = $probabilidades[$clasePredicha];

        return [
            'prediccion'     => $clasePredicha,
            'confianza'      => round($confianza * 100, 2),
            'probabilidades' => array_map(fn($p) => round($p * 100, 1), $probabilidades),
            'arboles_votos'  => $votos,
            'vector_entrada' => [
                'latencia_ms'     => $latencia,
                'tapping_rate'    => $tapping,
                'tiempo_ms'       => $tiempoTotal,
                'rectificaciones' => $rectificaciones,
                'score'           => $score
            ]
        ];
    }

    /**
     * Función Softmax para convertir pesos discretos en distribución de probabilidad.
     */
    private function calcularProbabilidades(array $votos): array
    {
        $exp = [];
        $sumExp = 0.0;

        foreach ($votos as $clase => $peso) {
            $e = exp($peso);
            $exp[$clase] = $e;
            $sumExp += $e;
        }

        $probabilidades = [];
        foreach ($exp as $clase => $val) {
            $probabilidades[$clase] = $sumExp > 0 ? ($val / $sumExp) : 0.2;
        }

        return $probabilidades;
    }
}
