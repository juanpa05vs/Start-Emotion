@props([
    'codigo',
    'titulo',
    'mensaje',
    'pasos'    => [],
    'url'      => null,
    'accion'   => 'Ir a mi inicio',
    'secundario' => null,
    'urlSecundaria' => null,
    'recargar' => false,
    'volver'   => false,
])

{{--
    Pantalla de error con la aplicación puesta.

    Sin esto, un 403 o un 404 mostraban la página por defecto de Laravel: un
    documento en inglés, sin hoja de estilos de la aplicación, sin barra lateral
    y sin ningún enlace de vuelta. Es el peor resultado posible para una
    herramienta clínica: alguien llega, ve «Forbidden» y no sabe si ha perdido
    sus datos o si la sesión se ha cerrado.

    La regla que se sigue aquí es la misma que en `x-estado-vacio`: decir qué ha
    pasado y ofrecer el siguiente paso. Un error del que no se puede salir es
    indistinguible de un fallo.

    `pasos` es una lista opcional de lo que se puede hacer. Se usa en el 419, que
    es el único de los tres en el que la acción depende de que la persona
    repita lo que estaba haciendo.
--}}

<div class="flex min-h-[70vh] items-center justify-center py-10">
    <div class="w-full max-w-lg">

        {{-- El código va en Orbitron porque es lo único de la pantalla que se
             parece a un dato: cuatro cifras, y se leen de un vistazo. --}}
        <p class="font-orbitron text-[13px] font-bold uppercase tracking-[0.28em] text-accent-text">
            {{ $codigo }}
        </p>

        <h1 class="mt-3 font-orbitron text-2xl font-bold tracking-tight adaptive-title">
            {{ $titulo }}
        </h1>

        <p class="mt-3 text-[14px] leading-relaxed text-gray-300">
            {{ $mensaje }}
        </p>

        @if (count($pasos))
            <ul class="mt-5 space-y-2.5 border-l-2 border-accent/40 pl-4">
                @foreach ($pasos as $paso)
                    <li class="text-[13px] leading-relaxed text-gray-400">{{ $paso }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-7 flex flex-wrap gap-3">
            {{-- `recargar` es un botón y no un enlace a `#`: recargar la página
                 conserva la ruta, y eso es justo lo que necesita quien está
                 reapuntando un formulario caducado. Con un enlace `#` habría que
                 añadir JavaScript para hacer lo mismo.

                 `volver` es el botón de «atrás» del navegador, no un enlace a
                 una ruta de la aplicación. En el 404 hace falta porque Laravel
                 lanza la excepción antes de aplicar el grupo de middleware
                 `web`: no hay sesión, así que la vista no sabe quién la está
                 viendo y cualquier enlace sería una suposición. El botón de
                 atrás sí funciona, y si no hay historial al que volver —la
                 pestaña se abrió directamente en la dirección mala— lleva a la
                 portada en lugar de no hacer nada. --}}
            @if ($recargar)
                <x-boton tipo="button" icono="arrows-rotate" onclick="window.location.reload()">
                    {{ $accion }}
                </x-boton>
            @elseif ($volver)
                <x-boton tipo="button" icono="arrow-left"
                         onclick="window.history.length > 1 ? window.history.back() : window.location.assign('/')">
                    {{ $accion }}
                </x-boton>
            @elseif ($url)
                <x-boton :href="$url" iconoDerecha="arrow-right">
                    {{ $accion }}
                </x-boton>
            @endif

            @if ($secundario && $urlSecundaria)
                <a href="{{ $urlSecundaria }}"
                   class="inline-flex items-center gap-2 rounded-control border border-white/10 px-5 py-2.5 text-[11px] font-semibold text-gray-300 transition-colors hover:border-white/25 hover:text-gray-200">
                    {{ $secundario }}
                </a>
            @endif
        </div>
    </div>
</div>