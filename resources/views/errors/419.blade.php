@extends('layouts.app')

@section('title', 'Formulario caducado | S-Emotion')

@section('content')

{{--
    419 · página caducada.

    Este sí aparece en el uso normal. Cada formulario lleva un testigo de
    seguridad que caduca a los pocos minutos, y en una aplicación donde la
    gente anota su estado de ánimo a lo largo del día es fácil tener el
    formulario de una emoción abierto durante media hora y volver a él cuando
    ya ha caducado.

    Antes esto devolvía la página de Laravel en inglés: «Page Expired». Sin
    estilos, sin barra lateral y sin ninguna indicación de que lo que la
    persona llevaba un minuto escribiendo se pueda volver a enviar. Es el peor
    momento posible para romper la calma de alguien, así que aquí se dice
    exactamente eso y se ofrece recargar.
--}}

<x-pantalla-error
    codigo="419 · Formulario caducado"
    titulo="Este formulario se quedó abierto demasiado tiempo"
    mensaje="Por seguridad, los formularios de esta aplicación caducan a los pocos minutos, para que una sesión antigua no pueda reutilizarse. Tus datos siguen intactos."
    :pasos="[
        'Recarga esta página y vuelve a escribir el registro.',
        'Si es un registro largo, tenlo abierto delante mientras lo escribes: se guardará en cuanto lo envíes.',
    ]"
    recargar
    accion="Recargar la página"
    secundario="Ir a mi inicio"
    url-secundaria="{{ auth()->user()?->rutaDeInicio() ? route(auth()->user()->rutaDeInicio()) : route('login') }}" />

@endsection