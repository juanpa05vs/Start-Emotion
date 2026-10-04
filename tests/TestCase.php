<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Instancia base de la aplicación para cada test.
     *
     * El guard de abajo aborta la suite si la base de datos configurada NO es
     * desechable. Es una protección deliberada: RefreshDatabase ejecuta
     * `migrate:fresh`, y si por un descuido la config quedara cacheada (de modo
     * que phpunit.xml no aplique sus overrides), los tests apuntan a la BD real
     * de desarrollo y la borran. Mejor un fallo ruidoso que perder datos.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $esDesechable = $connection === 'sqlite'
            && in_array($database, [':memory:', 'database/database.sqlite'], true);

        if (! $esDesechable) {
            throw new RuntimeException(
                "ABORTADO: los tests se conectarían a '{$connection}' / '{$database}' y RefreshDatabase "
                .'ejecutaría migrate:fresh sobre esa base. Configura DB_CONNECTION=sqlite y '
                ."DB_DATABASE=:memory: en .env.testing, y ejecuta 'php artisan config:clear' "
                .'antes de correr la suite.'
            );
        }
    }
}
