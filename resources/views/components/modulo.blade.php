@props(['id', 'activo' => false])

{{--
    Un panel dentro de `<x-modulos>`.

    `hidden` va puesto desde el servidor, en el propio marcado, y no lo decide el
    script al arrancar. Es lo que leen el lector de pantalla y la búsqueda por
    teclado, así que si se dejara en manos del JavaScript, una página con el
    bundle sin cargar enseñaría los cinco módulos a la vez, apilados.

    `activo` se escribe a mano en el panel que se quiere ver primero. Podría
    deducirse de `data-modulo` contra el `id` que declara el padre, pero eso
    obliga al panel a conocer la decisión del padre y a que ambos coincidan; aquí
    el marcado dice las dos cosas y se lee sin más contexto.

    Uso: `<x-modulo id="tendencia"> ... </x-modulo>`.
--}}

<div
    id="modulo-{{ $id }}"
    data-modulo="{{ $id }}"
    role="tabpanel"
    aria-labelledby="pestana-modulo-{{ $id }}"
    tabindex="0"
    @if (! $activo) hidden @endif
>
    {{ $slot }}
</div>