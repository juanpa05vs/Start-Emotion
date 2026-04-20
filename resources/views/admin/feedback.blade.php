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
            <p class="text-gray-500 text-[10px] uppercase tracking-[0.3em]">Protocolo de Mejora Continua // Sector Alpha</p>
        </div>
        <div class="flex gap-8">
            <div class="text-right">
                <span class="text-accent font-black text-2xl font-orbitron">{{ count($reportes) }}</span>
                <p class="text-[7px] text-gray-600 uppercase font-black tracking-widest">Reportes Totales</p>
            </div>
        </div>
    </div>

    {{-- REJILLA DE REPORTES --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($reportes as $reporte)
            @php
                // Lógica de color por estado
                $isResuelto = $reporte->estado === 'resuelto';
                $statusColor = $isResuelto ? 'text-green-400 border-green-500/30 bg-green-500/10' : 'text-amber-400 border-amber-500/30 bg-amber-500/10';
            @endphp

            <div class="bg-black/40 border border-white/10 backdrop-blur-xl rounded-2xl p-6 relative overflow-hidden group hover:border-accent/40 transition-all duration-500">

                {{-- BOTÓN DE ELIMINAR (Top Right) --}}
                <form action="{{ route('feedback.destroy', $reporte->id) }}" method="POST" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('¿Confirmar purga de este reporte?')" class="text-gray-600 hover:text-red-500 transition-colors">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </button>
                </form>

                {{-- INFO DEL OPERADOR --}}
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-10 w-10 rounded-full border border-accent/30 p-0.5 bg-black">
                        <img src="{{ $reporte->user && $reporte->user->avatar ? asset('storage/' . $reporte->user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($reporte->user->nombre ?? 'U').'&background=000&color=fff' }}"
                             class="h-full w-full rounded-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-white text-xs font-black uppercase tracking-tight">{{ $reporte->user->nombre ?? 'Desconocido' }}</h3>
                        <p class="text-[8px] text-accent font-bold uppercase tracking-widest">{{ $reporte->user->rol ?? 'Operador' }}</p>
                    </div>
                </div>

                {{-- CONTENIDO DEL REPORTE --}}
                <div class="min-h-[100px] border-l-2 border-accent/20 pl-4 mb-4">
                    <p class="text-gray-300 text-sm leading-relaxed font-light">
                        "{{ $reporte->comentario }}"
                    </p>
                </div>

                {{-- ACCIONES Y ESTADO --}}
                <div class="mt-6 pt-4 border-t border-white/5 flex justify-between items-center">
                    <div class="flex flex-col">
                        <span class="text-[9px] text-gray-600 font-black uppercase tracking-tighter">
                            <i class="fa-regular fa-clock mr-1"></i> {{ $reporte->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- BOTÓN CAMBIAR ESTADO (Solo si está pendiente) --}}
                        @if(!$isResuelto)
                            <form action="{{ route('feedback.updateStatus', $reporte->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-[8px] text-gray-500 hover:text-green-400 transition-all font-black uppercase">
                                    Finalizar <i class="fa-solid fa-circle-check ml-1"></i>
                                </button>
                            </form>
                        @endif

                        <span class="px-3 py-1 border {{ $statusColor }} text-[8px] font-black uppercase rounded-full shadow-lg">
                            {{ $reporte->estado ?? 'RECIBIDO' }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center bg-black/20 border border-dashed border-white/10 rounded-3xl">
                <i class="fa-solid fa-satellite-dish text-gray-800 text-5xl mb-4"></i>
                <p class="text-gray-600 font-orbitron text-xs uppercase tracking-widest">No se detectan transmisiones de feedback</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
