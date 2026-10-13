<?php

use App\Http\Middleware\EsEstudiante;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
        // Proxies de confianza. Render termina el TLS en su proxy de borde y
        // reenvía la petición al contenedor como HTTP con la cabecera
        // X-Forwarded-Proto: https. Si Laravel no confía en ese proxy, cree
        // que la petición es HTTP y genera URLs (route(), url(), asset()) con
        // esquema http; los formularios salen con action="http://…" y el
        // navegador avisa de envío inseguro.
        //
        // El valor sale de TRUSTED_PROXIES (config/trustedproxy.php) y por
        // defecto es '*', que en Laravel 11 significa confiar SOLO en el proxy
        // que conecta directamente (el de Render), no en cualquier origen.
        //
        // Se confía ÚNICAMENTE en X-Forwarded-Proto, que es lo único necesario
        // para detectar el esquema. NO se confía en X-Forwarded-Host/-Port:
        // alimentan getHost()/getPort() y permitirían inyectar otro dominio
        // (p. ej. X-Forwarded-Host: evil.example.com) en route(), url(),
        // asset() y en los Location: de redirección. Tampoco en
        // X-Forwarded-For: no es necesario aquí y su cadena (Render/Cloudflare)
        // es ambigua para el throttle por IP.
        $middleware->trustProxies(
            at: config('trustedproxy.proxies'),
            headers: Request::HEADER_X_FORWARDED_PROTO,
        );

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
