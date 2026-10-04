@props([
    'href'         => null,
    'tipo'         => 'submit',
    'tamano'       => null,      // null (normal) | grande | pequeno
    'icono'        => null,
    'iconoDerecha' => null,
    'inactivo'     => false,
    'class'        => '',
])

{{--
    Botón secundario: mismo acento que el principal, sin robarle el protagonismo.

    Es el caso de «Generar invitación» al lado de la lista, o del botón de
    enviar en el formulario que tiene un enlace de vuelta al lado. Comparte
    props con `x-boton` a propósito: cambiarlas de un sitio a otro no debe
    obligar a reescribir todos los botones de la aplicación.
--}}

@php
    $clases = 'btn btn--secundario'
        .($tamano ? ' btn--' . $tamano : '')
        .($inactivo ? ' pointer-events-none' : '')
        .' ' . $class;
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $clases }}" {{ $attributes }} @if ($inactivo) aria-disabled="true" @endif>
        @if ($icono)<i class="fa-solid fa-{{ $icono }} text-[10px]" aria-hidden="true"></i>@endif
        {{ $slot }}
        @if ($iconoDerecha)<i class="fa-solid fa-{{ $iconoDerecha }} text-[10px]" aria-hidden="true"></i>@endif
    </a>
@else
    <button type="{{ $tipo }}" class="{{ $clases }}" {{ $attributes }} @disabled($inactivo)>
        @if ($icono)<i class="fa-solid fa-{{ $icono }} text-[10px]" aria-hidden="true"></i>@endif
        {{ $slot }}
        @if ($iconoDerecha)<i class="fa-solid fa-{{ $iconoDerecha }} text-[10px]" aria-hidden="true"></i>@endif
    </button>
@endif