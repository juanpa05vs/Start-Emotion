<?php

namespace App\Services;

use App\Enums\EmocionEnum;

class RandomForestService
{
    /**
     * Ejecuta la inferencia del ensamble de Árboles de Decisión sobre el vector conductual.
     *
     * @param  array  $vector  [latencia_ms, tapping_rate, tiempo_ms, rectificaciones, score]
     */
    public function predecir(array $vector): array
    {
        $latencia = (float) ($vector[0] ?? 1200);
        $tapping = (float) ($vector[1] ?? 1.0);
        $tiempoTotal = (int) ($vector[2] ?? 30000);
        $rectificaciones = (int) ($vector[3] ?? 2);
        $score = (int) ($vector[4] ?? 100);

        $votos = [
            EmocionEnum::ALEGRIA->value => 0.0,
            EmocionEnum::TRISTEZA->value => 0.0,
            EmocionEnum::ANSIEDAD->value => 0.0,
            EmocionEnum::FRUSTRACION->value => 0.0,
            EmocionEnum::NEUTRALIDAD->value => 0.0,
        ];

        // ÁRBOL 1
        if ($tapping >= 1.8) {
            $votos[$latencia < 1000 ? EmocionEnum::ANSIEDAD->value : EmocionEnum::FRUSTRACION->value] += 1.5;
        } elseif ($tapping <= 0.6) {
            $votos[EmocionEnum::TRISTEZA->value] += 1.5;
        } else {
            $votos[EmocionEnum::NEUTRALIDAD->value] += 1.0;
        }

        // ÁRBOL 2
        if ($latencia < 800) {
            $votos[$score >= 80 ? EmocionEnum::ALEGRIA->value : EmocionEnum::ANSIEDAD->value] += 1.5;
        } elseif ($latencia > 2500) {
            $votos[$rectificaciones >= 4 ? EmocionEnum::FRUSTRACION->value : EmocionEnum::TRISTEZA->value] += 1.5;
        } else {
            $votos[EmocionEnum::NEUTRALIDAD->value] += 1.0;
        }

        // ÁRBOL 3
        if ($rectificaciones >= 5) {
            $votos[EmocionEnum::FRUSTRACION->value] += 2.0;
            $votos[EmocionEnum::ANSIEDAD->value] += 1.0;
        } elseif ($rectificaciones <= 1) {
            $votos[$score >= 80 ? EmocionEnum::ALEGRIA->value : EmocionEnum::NEUTRALIDAD->value] += 1.5;
        } else {
            $votos[EmocionEnum::NEUTRALIDAD->value] += 1.0;
        }

        // ÁRBOL 4
        if ($score >= 80 && $tiempoTotal <= 25000) {
            $votos[EmocionEnum::ALEGRIA->value] += 2.0;
        } elseif ($score <= 40 && $tiempoTotal >= 40000) {
            $votos[EmocionEnum::FRUSTRACION->value] += 1.5;
            $votos[EmocionEnum::TRISTEZA->value] += 1.0;
        } elseif ($tiempoTotal >= 45000) {
            $votos[EmocionEnum::ANSIEDAD->value] += 1.0;
        }

        // ÁRBOL 5
        if ($tapping > 1.2 && $rectificaciones >= 3) {
            $votos[EmocionEnum::ANSIEDAD->value] += 1.8;
        } elseif ($tapping < 0.8 && $latencia > 2000) {
            $votos[EmocionEnum::TRISTEZA->value] += 1.8;
        }

        $probabilidades = $this->calcularProbabilidades($votos);

        arsort($probabilidades, SORT_NUMERIC);
        $clasePredicha = array_key_first($probabilidades);
        $confianza = $probabilidades[$clasePredicha] ?? 0.0;

        return [
            'prediccion' => $clasePredicha,
            'confianza' => round($confianza * 100, 2),
            'probabilidades' => array_map(fn ($p) => round((float) $p * 100, 1), $probabilidades),
            'arboles_votos' => $votos,
            'vector_entrada' => [
                'latencia_ms' => $latencia,
                'tapping_rate' => $tapping,
                'tiempo_ms' => $tiempoTotal,
                'rectificaciones' => $rectificaciones,
                'score' => $score,
            ],
        ];
    }

    private function calcularProbabilidades(array $votos): array
    {
        $exp = [];
        $sumExp = 0.0;

        foreach ($votos as $clase => $peso) {
            $e = exp((float) $peso);
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
