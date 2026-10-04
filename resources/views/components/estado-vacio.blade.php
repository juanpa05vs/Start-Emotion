@props([
    'icono'    => 'fa-inbox',
    'titulo'   => 'Nada por aquí todavía',
    'mensaje'  => null,
    'detalle'  => null,
    'accion'   => null,
    'url'      => null,
])

{{--
    Estado vacío.

    Una pantalla sin datos no es una pantalla rota, y se distingue de un fallo en
    dos cosas: dice QUÉ falta y ofrece el siguiente paso. Antes solo dos vistas de
    veinte tenían un estado vacío; el resto mostraban un espacio en blanco donde
    el usuario no sabía si había un error o simplemente no había registrado nada
    todavía.

    La acción es opcional a propósito. Cuando la pantalla no ofrece un camino
    siguiente —porque quien la ve no tiene permiso para darlo— es mejor un
    estado vacío honesto que un enlace que devuelve 403.
--}}

<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    {{-- El icono va en un círculo: es un límite visual que separa «aquí no hay
         nada» de «aquí hay algo». --}}
    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-pill border border-accent/25 bg-accent-soft">
        <i class="fa-solid {{ $icono }} text-lg text-accent-text" aria-hidden="true"></i>
    </div>

    <h3 class="font-orbitron text-base font-bold tracking-tight adaptive-title">
        {{ $titulo }}
    </h3>

    @if ($mensaje)
        <p class="mt-2.5 max-w-lg text-[13px] leading-relaxed text-gray-400">
            {!! $mensaje !!}
        </p>
    @endif

    @if ($detalle)
        <p class="mt-3 max-w-lg text-[12px] leading-relaxed text-gray-500">
            {!! $detalle !!}
        </p>
    @endif

    @if ($accion && $url)
        <x-boton :href="$url" class="mt-6" iconoDerecha="arrow-right">
            {{ $accion }}
        </x-boton>
    @endif
</div>