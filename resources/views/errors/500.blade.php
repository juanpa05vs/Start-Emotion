@extends('layouts.app')

@section('title', 'Algo no salió bien | S-Emotion')

@section('content')

{{--
    500.

    Un error interno puede lanzarse en cualquier punto de una petición, así que
    esta vista no presume que hay sesión: el botón es el de «atrás» y el enlace
    es la portada, como en el 404. Lo importante del texto es que un fallo del
    servidor no parece «haber perdido los datos»: no es la cuenta, y lo que
    estaba guardado sigue donde estaba.
--}}

<x-pantalla-error
    codigo="500 · Error interno"
    titulo="Algo no salió bien"
    mensaje="La solicitud llegó, pero la aplicación no pudo completarla. No es un problema de tu cuenta ni de tus datos: lo que tenías guardado sigue donde estaba."
    volver
    accion="Volver a la página anterior"
    secundario="Ir a la portada"
    url-secundaria="{{ route('welcome') }}" />

@endsection