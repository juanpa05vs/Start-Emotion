@props(['active', 'href', 'icon', 'step', 'label', 'sub' => null])

@php
// [INGENIERÍA] Lógica de clases activa/inactiva centralizada
$classes = ($active ?? false)
            ? 'bg-accent-soft border border-accent/30 shadow-[0_0_15px_rgba(var(--neon-accent-rgb),0.1)]'
            : 'hover:bg-white/5 border border-transparent';

$iconClasses = ($active ?? false)
                ? 'text-accent'
                : 'text-gray-500 group-hover:text-white';
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => "group flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-300 $classes"]) }}>
    <i class="fa-solid {{ $icon }} text-sm {{ $iconClasses }}"></i>
    <div class="flex flex-col">
        <span class="text-xs font-bold uppercase tracking-widest {{ ($active ?? false) ? 'text-white' : 'text-gray-400 group-hover:text-white' }}">
            {{ $step }}. {{ $label }}
        </span>
        @if($sub)
            <span class="text-[7px] text-gray-600 group-hover:text-accent transition-colors uppercase font-black">
                {{ $sub }}
            </span>
        @endif
    </div>
</a>
