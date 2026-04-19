@extends('layouts.app')

@section('content')
<div class="p-8">
    <div class="mb-10">
        <h1 class="font-orbitron text-3xl font-black text-white uppercase tracking-widest">
            Monitor <span class="text-neon-cyan">Longitudinal</span>
        </h1>
        <div class="flex items-center gap-2 mt-2">
            <span class="h-[2px] w-12 bg-neon-cyan"></span>
            <p class="text-[10px] text-gray-500 uppercase tracking-[0.4em] font-bold">Calendario de Estabilidad Sincrónica</p>
        </div>
    </div>

    <div class="bg-black/60 border border-white/10 rounded-[2rem] p-10 backdrop-blur-3xl shadow-2xl relative overflow-hidden">
        {{-- Decoración de fondo --}}
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-neon-cyan/5 blur-[100px] rounded-full"></div>

        {{-- Cuadrícula de Días --}}
        <div class="grid grid-cols-7 gap-4 mb-6">
            @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaNombre)
                <div class="text-[10px] text-center font-black text-gray-600 uppercase tracking-widest">{{ $diaNombre }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-4">
            @foreach($rangoDias as $dia)
                @php
                    $fecha = $dia->format('Y-m-d');
                    $promedio = $datosHeatmap[$fecha] ?? null;

                    // Lógica de Color Neural
                    $colorClass = 'bg-white/5 border border-white/5'; // Sin datos
                    if($promedio) {
                        $colorClass = match(true) {
                            $promedio >= 80 => 'bg-neon-cyan shadow-[0_0_20px_rgba(34,211,238,0.4)] border-none',
                            $promedio >= 50 => 'bg-emerald-500 border-none',
                            $promedio >= 25 => 'bg-orange-500 border-none',
                            default         => 'bg-neon-rose animate-pulse border-none',
                        };
                    }
                @endphp

                <div class="group relative aspect-square rounded-xl {{ $colorClass }} transition-all duration-500 hover:scale-110 flex items-center justify-center cursor-help"
                     title="{{ $dia->format('d/m/Y') }}: {{ $promedio ? round($promedio).'%' : 'Sin datos' }}">

                    <span class="text-[10px] font-bold text-white/20 group-hover:text-white transition-colors">
                        {{ $dia->format('d') }}
                    </span>

                    {{-- Tooltip de Ingeniería --}}
                    @if($promedio)
                        <div class="absolute -top-12 left-1/2 -translate-x-1/2 bg-black border border-neon-cyan/30 px-3 py-1 rounded text-[9px] text-neon-cyan opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
                            ENERGÍA: {{ round($promedio) }}%
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Leyenda de Estados --}}
        <div class="mt-12 pt-8 border-t border-white/5 flex items-center justify-between">
            <p class="text-[9px] text-gray-600 uppercase tracking-widest font-bold italic">
                Sincronización Neural: {{ now()->format('F Y') }}
            </p>
            <div class="flex gap-4 items-center">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-neon-rose rounded-full animate-pulse"></div>
                    <span class="text-[8px] text-gray-500 uppercase">Crítico</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-neon-cyan rounded-full"></div>
                    <span class="text-[8px] text-gray-500 uppercase">Óptimo</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
