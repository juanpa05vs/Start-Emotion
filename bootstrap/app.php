<?php

use App\Http\Middleware\EsEstudiante;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Proxies de confianza: se configuran con la variable de entorno
        // TRUSTED_PROXIES y se leen en tiempo de petición desde
        // config/trustedproxy.php (seguro con config:cache).
        //
        // No se confía en ningún proxy por defecto. Antes se usaba
        // trustProxies(at: '*'), que permite falsificar la IP y el esquema
        // mediante cabeceras X-Forwarded-*.
        //
        // Si la app va detrás de un balanceador TLS, define TRUSTED_PROXIES
        // (ver .env.example) para recuperar la detección de HTTPS.

        // Registro de alias de middlewares personalizados y de Spatie
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // Autoobservación: inicio, historial, evaluación, calendario,
            // actividades y privacidad. Es de quien se observa a sí mismo.
            'estudiante' => EsEstudiante::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
