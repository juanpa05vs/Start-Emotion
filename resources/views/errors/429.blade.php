@extends('layouts.app')

@section('title', 'Un momento | S-Emotion')

@section('content')

{{--
    429.

    Llega del límite de intentos (login, invitaciones, revocaciones…). Antes de
    esta pantalla el límite se agotaba y el navegador mostraba la página por
    defecto de Laravel en inglés, sin explicar que la cuenta no corría ningún
    riesgo. Este límite protege precisamente eso: a la cuenta y al sistema.
    «Intentar de nuevo» recarga la misma página; si el límite aún no se ha
    liberado, la pantalla vuelve a aparecer y basta con esperar.
--}}

<x-pantalla-error
    codigo="429 · Un momento"
    titulo="Demasiadas solicitudes"
    mensaje="Esta pantalla protege el sistema cuando se reciben demasiadas solicitudes a la vez, por ejemplo al intentar entrar varias veces seguidas. Espera un momento y vuelve a intentarlo: no se ha perdido nada de lo que habías guardado."
    recargar
    accion="Intentar de nuevo"
    secundario="Ir a la portada"
    url-secundaria="{{ route('welcome') }}" />

@endsection