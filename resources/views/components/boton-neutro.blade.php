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
    Botón neutro: filete y texto, sin color de marca.

    Es el de «cancelar» y el de las acciones terciarias, los que conviven con
    una acción principal sin competir con ella. Antes estos botones eran
    `<button>` con `bg-black/40 text-white` escritos a mano en cada pantalla, y
    en modo claro el texto blanco sobre negro translúcido quedaba justo en el
    límite de legibilidad.

    Para lo destructivo está `x-boton-peligro`, que tampoco lleva relleno: una
    acción que no se puede deshacer no debe parecer un clic más.
--}}

@php
    $clases = 'btn btn--neutro'
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