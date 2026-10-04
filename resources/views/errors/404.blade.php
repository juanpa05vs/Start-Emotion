@extends('layouts.app')

@section('title', 'Página no encontrada | S-Emotion')

@section('content')

{{--
    404.

    Esta pantalla se dibuja en un caso particular: Laravel lanza la excepción
    antes de aplicar el grupo de middleware `web`, así que aquí NO hay sesión.
    La vista no sabe si la está viendo alguien que ha entrado o no, y por eso el
    botón es el de «atrás» del navegador en lugar de un enlace a una pantalla
    concreta: un enlace habría que adivinarlo.

    Un enlace mal escrito o una pantalla que se ha movido. Es la única de las
    tres en la que la respuesta correcta no es explicar los permisos, sino dejar
    que la persona vuelva al trabajo sin haber perdido nada: aquí no hay datos
    en juego.
--}}

<x-pantalla-error
    codigo="404 · No encontrado"
    titulo="Esta dirección no lleva a ninguna parte"
    mensaje="El enlace puede estar mal escrito, o la pantalla puede haberse movido. No se ha perdido nada: lo que tenías guardado sigue donde estaba."
    volver
    accion="Volver a la página anterior"
    secundario="Ir a la portada"
    url-secundaria="{{ route('welcome') }}" />

@endsection