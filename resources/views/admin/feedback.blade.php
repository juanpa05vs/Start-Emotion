@extends('layouts.app')

@section('title', 'Monitor de Feedback | Alpha Sector')

@section('content')
<div class="p-8 max-w-7xl mx-auto">
    {{-- ENCABEZADO TÉCNICO --}}
    <div class="flex justify-between items-end mb-10">
        <div>
            <h1 class="font-orbitron text-3xl font-black text-white uppercase tracking-tighter">
                Monitor de <span class="text-accent">Feedback</span>
            </h1>
            <p class="text-gray-500 text-[10px] uppercase tracking-[0.3em]">Protocolo de Mejora Continua y Análisis de Operadores</p>
        </div>
        <div class="text-right">
            <span class="text-accent font-black text-2xl font-orbitron">{{ $reportes->count() }}</span>
            <p class="text-[7px] text-gray-600 uppercase font-black">Reportes Indexados</p>
        </div>
    </div>

    {{-- REJILLA DE REPORTES --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($reportes as $reporte)
            <div class="bg-black/40 border border-white/10 backdrop-blur-xl rounded-2xl p-6 relative overflow-hidden group hover:border-accent/40 transition-all duration-500">
                {{-- Efecto de luz de fondo --}}
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-accent/5 blur-[50px] group-hover:bg-accent/10 transition-all"></div>

                {{-- INFO DEL OPERADOR --}}
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-10 w-10 rounded-full border border-accent/30 p-0.5 bg-black">
                        <img src="{{ $reporte->user->avatar ? asset('storage/' . $reporte->user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($reporte->user->nombre).'&background=000&color=fff' }}"
                             class="h-full w-full rounded-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-white text-xs font-black uppercase tracking-tight">{{ $reporte->user->nombre }}</h3>
                        <p class="text-[8px] text-accent font-bold uppercase tracking-widest">{{ $reporte->user->rol }}</p>
                    </div>
                </div>

                {{-- CONTENIDO DEL REPORTE --}}
                <div class="min-h-[80px]">
                    <p class="text-gray-400 text-sm leading-relaxed italic">
                        "{{ $reporte->comentario }}"
                    </p>
                </div>

                {{-- METADATOS Y ESTADO --}}
                <div class="mt-8 pt-4 border-t border-white/5 flex justify-between items-center">
                    <span class="text-[9px] text-gray-600 font-black uppercase tracking-tighter">
                        <i class="fa-regular fa-clock mr-1"></i> {{ $reporte->created_at->diffForHumans() }}
                    </span>

                    <span class="px-3 py-1 bg-accent/10 border border-accent/20 text-accent text-[8px] font-black uppercase rounded-full shadow-[0_0_10px_rgba(var(--neon-accent-rgb),0.1)]">
                        {{ $reporte->estado }}
                    </span>
                </div>
            </div>
        @empty
            {{-- ESTADO VACÍO --}}
            <div class="col-span-full py-20 text-center bg-black/20 border border-dashed border-white/10 rounded-3xl">
                <i class="fa-solid fa-satellite-dish text-gray-800 text-5xl mb-4"></i>
                <p class="text-gray-600 font-orbitron text-xs uppercase tracking-widest">No se detectan transmisiones de feedback</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
