@props([
    'href'         => null,
    'tipo'         => 'submit',
    'tamano'       => null,      // null (normal) | grande | pequeno
    'icono'        => null,      // nombre corto de Font Awesome, sin prefijo
    'iconoDerecha' => null,
    'inactivo'     => false,
    'class'        => '',
])

{{--
    Botón de la acción principal de una pantalla.

    Lo que resuelve no es el color —eso ya lo pone `app.css`— sino tres cosas
    que se perdían al escribir el HTML a mano:

      · la etiqueta correcta. Sin `href` es un `<button type="submit">`; con
        `href` es un `<a>`. A mano se mezclaban las dos, y un `<a>` dentro de un
        formulario no envía nada.

      · el icono marcado como decorativo. Los botones repetían
        `aria-hidden="true"` quince veces y el que se olvidaba lo leía el
        lector de pantalla como texto suelto.

      · el estado deshabilitado. `inactivo` marca el atributo y el estilo a la
        vez, de forma que no puede quedar uno sin el otro.

    Para lo que no sea la acción principal están `x-boton-secundario` y
    `x-boton-neutro`. Lo que haga falta aparte del aspecto —un margen, un ancho
    a pantalla completa— va en `class`, que se añade al final.
--}}

@php
    $clases = 'btn btn--principal'
        .($tamano ? ' btn--' . $tamano : '')
        .($inactivo ? ' pointer-events-none' : '')
        .' ' . $class;
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $clases }}" {{ $attributes }} @if ($inactivo) aria-disabled="true" @endif>
        @if ($icono)
            <i class="fa-solid fa-{{ $icono }} text-[10px]" aria-hidden="true"></i>
        @endif
        {{ $slot }}
        @if ($iconoDerecha)
            <i class="fa-solid fa-{{ $iconoDerecha }} text-[10px]" aria-hidden="true"></i>
        @endif
    </a>
@else
    <button type="{{ $tipo }}" class="{{ $clases }}" {{ $attributes }} @disabled($inactivo)>
        @if ($icono)
            <i class="fa-solid fa-{{ $icono }} text-[10px]" aria-hidden="true"></i>
        @endif
        {{ $slot }}
        @if ($iconoDerecha)
            <i class="fa-solid fa-{{ $iconoDerecha }} text-[10px]" aria-hidden="true"></i>
        @endif
    </button>
@endif