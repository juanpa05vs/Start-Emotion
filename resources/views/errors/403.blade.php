@extends('layouts.app')

@section('title', 'Acceso no permitido | S-Emotion')

@section('content')

{{--
    403.

    Esta pantalla aparece de verdad: el心理学家 que abre `/dashboard` y el
    administrador que sigue un marcador viejo. Ninguna de las dos rutas es un
    error de la aplicación —el reparto de espacios es intencionado— pero la
    página que se veía era la de Laravel en inglés.

    El enlace lleva a la pantalla de inicio que corresponde a la cuenta, no al
    panel del estudiante: para un profesional ese es justo el error que acaba de
    provocar el 403.
--}}

<x-pantalla-error
    codigo="403 · Acceso no permitido"
    titulo="Esta sección no es de tu cuenta"
    mensaje="Cada tipo de cuenta entra en una parte distinta de la aplicación. El estudiante lleva su propio seguimiento, el profesional entra en las personas que le han autorizado, y la administración gestiona las cuentas. Lo que has abierto es de otra."
    :url="auth()->user()?->rutaDeInicio() ? route(auth()->user()->rutaDeInicio()) : route('login')"
    :accion="auth()->user() ? 'Ir a mi inicio' : 'Entrar'"
    secundario="Leer la política de privacidad"
    url-secundaria="{{ route('privacidad.politica') }}" />

@endsection