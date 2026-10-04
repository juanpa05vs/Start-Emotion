<?php

namespace Tests\Unit;

use App\Services\RandomForestService;
use PHPUnit\Framework\TestCase;

class RandomForestServiceTest extends TestCase
{
    public function test_predice_con_vector_minimo(): void
    {
        $rf = new RandomForestService;
        $res = $rf->predecir([]);

        $this->assertArrayHasKey('prediccion', $res);
        $this->assertArrayHasKey('confianza', $res);
        $this->assertArrayHasKey('probabilidades', $res);
        $this->assertIsString($res['prediccion']);
        $this->assertGreaterThanOrEqual(0, $res['confianza']);
        $this->assertLessThanOrEqual(100, $res['confianza']);
    }

    public function test_predice_clase_valida(): void
    {
        $rf = new RandomForestService;
        $res = $rf->predecir([500, 2.0, 20000, 0, 95]);

        $validas = ['alegria', 'tristeza', 'ansiedad', 'frustracion', 'neutralidad'];
        $this->assertContains($res['prediccion'], $validas);
    }
}
