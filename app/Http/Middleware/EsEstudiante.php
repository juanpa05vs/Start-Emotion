<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reserva el espacio de autoobservación a quien lo usa para eso.
 *
 * El «Menú de Mando» (inicio, historial, evaluación, calendario, actividades y
 * privacidad) es la herramienta de trabajo de la persona que se está
 * observando a sí misma. El administrador no la usa: administra cuentas. Y el
 * profesional de psicología, cuando solo tiene ese rol, tampoco: atiende.
 *
 * Por qué un middleware y no solo esconder los enlaces: esconder un enlace en
 * la barra lateral deja la URL funcionando. Cualquier persona que la conociera
 * —o que se la inventara probando— seguiría viendo el panel. La restricción
 * que vale tiene que estar en la ruta; el menú es solo la señal de que la
 * pantalla existe.
 *
 * Se usa `User::esEstudiante()` y no el middleware `role:estudiante` de Spatie
 * a propósito: `esEstudiante()` mira la columna `rol` y también el rol de
 * Spatie, así que funciona con cuentas creadas por la fábrica de pruebas, que
 * no asignan el rol de Spatie.
 */
class EsEstudiante
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->esEstudiante()) {
            abort(403, 'Esta pantalla es para quienes usan la herramienta para observarse.');
        }

        return $next($request);
    }
}
