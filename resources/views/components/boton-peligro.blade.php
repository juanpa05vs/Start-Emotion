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
    Botón de acción destructiva: anular un código, borrar una anotación, eliminar
    una cuenta.

    Sin relleno, a propósito. Estas acciones no se pueden deshacer y conviven en
    la misma tarjeta con acciones que sí lo son; un botón rojo sólido es una
    invitación a pulsarlo sin leer. El color lo pone `--color-rose-300`, que ya
    se oscurece en modo claro para llegar a 4.5:1 sobre el panel.
--}}

@php
    $clases = 'btn btn--peligro'
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