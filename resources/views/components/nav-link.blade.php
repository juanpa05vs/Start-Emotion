@props(['active', 'href', 'icon', 'step', 'label', 'sub' => null])

@php
// Estado activo/inactivo en un solo sitio. El menú lateral se lee en vertical y
// necesita una señal de "dónde estoy" que no dependa solo del color: por eso el
// activo lleva también fondo, filete de acento y una barra lateral.
$activo = (bool) ($active ?? false);

$classes = $activo
    ? 'bg-accent-soft border-accent/30 shadow-[0_0_15px_rgba(var(--neon-accent-rgb),0.1)] before:opacity-100'
    : 'border-transparent hover:bg-white/5';

$iconClasses = $activo
    ? 'text-accent-text'
    : 'text-gray-500 group-hover:text-accent-text';

$labelClasses = $activo
    ? 'text-white'
    : 'text-gray-400 group-hover:text-white';
@endphp

{{-- `before:` es la barra de acento de 2px del estado activo. Antes el único
     indicio era un fondo casi transparente, que en una columna de nueve
     entradas no bastaba para saber dónde estabas. --}}
<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => "group relative flex items-center gap-4 rounded-control px-4 py-2.5 pl-5 transition-colors duration-200 border before:absolute before:inset-y-2 before:left-0 before:w-0.5 before:rounded-pill before:bg-accent before:opacity-0 before:transition-opacity $classes",
   ]) }}>

    <i class="fa-solid {{ $icon }} text-sm {{ $iconClasses }} transition-colors" aria-hidden="true"></i>

    <div class="flex flex-col leading-tight">
        <span class="text-[13px] font-semibold tracking-wide {{ $labelClasses }} transition-colors">
            {{ $label }}
        </span>

        @if ($sub)
            <span class="text-[10px] tracking-wide text-gray-600 group-hover:text-accent-text transition-colors">
                {{ $sub }}
            </span>
        @endif
    </div>

    {{-- El código de sección (`01`, `P1`, `AA`) estaba en la etiqueta y competía
         con el nombre del enlace. Ahora va aparte, con cifras tabulares para que
         la columna no baile al cambiar de pantalla.

         Va en `text-gray-500` y no en un gris más apagado porque llega a
         1.95:1 sobre la barra lateral: prácticamente invisible. Aunque el color
         no sea lo que dice esta entrada, tiene que poder leerse. --}}
    <span class="ml-auto shrink-0 font-mono text-[10px] font-bold tracking-wider {{ $activo ? 'text-accent-text' : 'text-gray-500' }}">
        {{ $step }}
    </span>
</a>
