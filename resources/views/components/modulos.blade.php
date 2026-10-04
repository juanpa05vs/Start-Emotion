@props([
    'modulos',
    'activo' => null,
])

{{--
    Grupo de módulos que se alternan con un clic, sin recargar la página.

    Cada elemento de `$modulos` es un array con:
      - `id`     Nombre técnico. Va en `data-modulo` y en `data-modulo-panel`,
                 y es lo que empareja pestaña con panel.
      - `titulo` Lo que se lee en la pestaña.
      - `icono`  Clase de Font Awesome. Opcional.

    No se exige que el panel se pase por `$slot`: el contenido de cada módulo lo
    escribe la vista dentro de `<x-modulos>`, con `<x-modulo id="...">`. Aquí solo
    van las pestañas, porque las pestañas son lo que este componente sabe hacer y
    el contenido es de cada pantalla.

    Por qué el estado inicial va en el HTML y no lo pone el script: si el marcado
    llegara sin nada seleccionado, el script lo corregiría al cargar y el usuario
    vería un parpadeo con todos los módulos a la vez. Con el estado puesto aquí,
    un fallo de JavaScript deja la página en un solo módulo, que es el modo de
    fallo aceptable.

    Los paneles se identifican por atributo, no por posición, para que reordenar
    la lista de `$modulos` no rompa el emparejamiento.
--}}

@if (count($modulos) >= 2)
    @php
        $activo ??= $modulos[0]['id'];
        $indice = 0;
    @endphp

    <div data-modulos class="flex flex-col gap-6">
        <div
            class="flex flex-wrap items-center gap-1.5 border-b border-white/5 pb-2"
            role="tablist"
            aria-label="Secciones del panel"
        >
            @foreach ($modulos as $modulo)
                @php
                    $esActivo = $modulo['id'] === $activo;
                @endphp

                <button
                    type="button"
                    role="tab"
                    id="pestana-modulo-{{ $modulo['id'] }}"
                    class="modulo-pestana"
                    data-modulo-panel="{{ $modulo['id'] }}"
                    aria-selected="{{ $esActivo ? 'true' : 'false' }}"
                    aria-controls="modulo-{{ $modulo['id'] }}"
                    tabindex="{{ $esActivo ? '0' : '-1' }}"
                >
                    @if (! empty($modulo['icono']))
                        <i class="{{ $modulo['icono'] }} text-[11px]" aria-hidden="true"></i>
                    @endif

                    <span>{{ $modulo['titulo'] }}</span>
                </button>
            @endforeach
        </div>

        <div class="modulos-contenedor">
            {{ $slot }}
        </div>
    </div>
@else
    {{-- Con un solo módulo no hay nada que alternar, así que se muestra tal cual
         y se ahorra un nivel de contenedores. --}}
    {{ $slot }}
@endif