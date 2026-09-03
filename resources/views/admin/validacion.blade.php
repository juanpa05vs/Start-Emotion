@extends('layouts.app')

@section('title', 'Start-Emotion | Validación Científica FECIEM')

@section('content')
<div class="space-y-8 select-none">

    {{-- ENCABEZADO CIENTÍFICO --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-white/10 pb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-[8px] font-black tracking-widest uppercase bg-cyan-500/10 text-neon-cyan border border-cyan-500/30 font-orbitron">
                    ANÁLISIS EXPERIMENTAL // TELEMETRÍA
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[8px] font-black tracking-widest uppercase bg-purple-500/10 text-purple-400 border border-purple-500/30 font-orbitron">
                    RANDOM FOREST
                </span>
            </div>
            <h1 class="font-orbitron text-2xl font-black adaptive-title uppercase tracking-tighter">
                Matriz de <span class="text-accent">Validación y Desempeño</span>
            </h1>
            <p class="text-gray-500 text-[9px] uppercase tracking-[0.3em] mt-1 font-semibold">
                Evaluación estadística de exactitud, sesgo algorítmico y calibración psicométrica
            </p>
        </div>

        {{-- BOTÓN EXPORTAR CSV --}}
        <a href="{{ route('admin.validacion.exportar') }}"
           class="bg-accent/10 border border-accent text-accent hover:bg-accent hover:text-black px-6 py-3 rounded-2xl font-orbitron text-[9px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(34,211,238,0.15)] active:scale-95">
            <i class="fa-solid fa-file-csv text-sm"></i>
            [ EXPORTAR DATASET CSV // SPSS & R ]
        </a>
    </div>

    {{-- TARJETAS DE INDICADORES GLOBALES (ACCURACY, F1, LATENCIA, MUESTRAS) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Exactitud (Accuracy) --}}
        <div class="bg-black/40 border border-white/10 p-5 rounded-3xl backdrop-blur-xl relative overflow-hidden shadow-xl">
            <div class="flex items-center justify-between">
                <span class="text-[8px] font-black uppercase tracking-widest text-gray-500 font-orbitron">Exactitud Global</span>
                <span class="text-[8px] font-black font-orbitron {{ $accuracy >= 80 ? 'text-emerald-400' : 'text-amber-400' }}">
                    UMBRAL ≥ 80%
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black font-orbitron {{ $accuracy >= 80 ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ $accuracy }}%
                </span>
                <span class="text-[9px] text-gray-500 font-mono">({{ $aciertosTotales }}/{{ $totalMuestras }})</span>
            </div>
            <p class="text-[7px] text-gray-500 uppercase tracking-widest mt-1">Accuracy del Clasificador</p>
        </div>

        {{-- F1-Score Macro --}}
        <div class="bg-black/40 border border-white/10 p-5 rounded-3xl backdrop-blur-xl relative overflow-hidden shadow-xl">
            <span class="text-[8px] font-black uppercase tracking-widest text-gray-500 font-orbitron block">F1-Score Macro</span>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black font-orbitron text-purple-400">{{ $macroF1 }}%</span>
                <span class="text-[9px] text-gray-500 font-mono">Balance P/R</span>
            </div>
            <p class="text-[7px] text-gray-500 uppercase tracking-widest mt-1">Media Armónica Multiclase</p>
        </div>

        {{-- Latencia Cognitiva Media --}}
        <div class="bg-black/40 border border-white/10 p-5 rounded-3xl backdrop-blur-xl relative overflow-hidden shadow-xl">
            <span class="text-[8px] font-black uppercase tracking-widest text-gray-500 font-orbitron block">Latencia Media</span>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black font-orbitron text-cyan-400">{{ $latenciaMedia }}</span>
                <span class="text-[10px] text-cyan-400 font-orbitron">MS</span>
            </div>
            <p class="text-[7px] text-gray-500 uppercase tracking-widest mt-1">Tiempo de Respuesta Implícito</p>
        </div>

        {{-- Universo Experimental (N) --}}
        <div class="bg-black/40 border border-white/10 p-5 rounded-3xl backdrop-blur-xl relative overflow-hidden shadow-xl">
            <span class="text-[8px] font-black uppercase tracking-widest text-gray-500 font-orbitron block">Muestras Registradas</span>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black font-orbitron adaptive-title">{{ $totalMuestras }}</span>
                <span class="text-[9px] text-gray-500 font-mono">Sesiones</span>
            </div>
            <p class="text-[7px] text-gray-500 uppercase tracking-widest mt-1">Población TESVB</p>
        </div>
    </div>

    {{-- MATRIZ DE CONFUSIÓN CON MAPA DE CALOR --}}
    <div class="bg-black/40 border border-white/10 p-6 md:p-8 rounded-3xl backdrop-blur-xl shadow-2xl">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/5">
            <div>
                <span class="text-[8px] font-black text-cyan-400 tracking-[0.3em] uppercase font-orbitron block mb-1">
                    [ EVALUACIÓN MULTICLASE // RANDOM FOREST ]
                </span>
                <h2 class="text-sm font-black uppercase font-orbitron adaptive-title">Matriz de Confusión Experimental</h2>
            </div>
            <span class="text-[8px] font-mono text-gray-500 uppercase tracking-widest">FILAS: Real (Gold) | COLUMNAS: Predicho (IA)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-center border-collapse">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="p-3 text-[8px] font-black uppercase font-orbitron text-gray-500 text-left">Real \ Pred</th>
                        @foreach($clases as $c)
                            <th class="p-3 text-[8px] font-black uppercase font-orbitron text-cyan-400">{{ strtoupper($c) }}</th>
                        @endforeach
                        <th class="p-3 text-[8px] font-black uppercase font-orbitron text-emerald-400">Recall (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-mono text-xs">
                    @foreach($clases as $real)
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="p-3 text-[9px] font-black uppercase font-orbitron text-cyan-400 text-left">{{ strtoupper($real) }}</td>
                            @foreach($clases as $pred)
                                @php
                                    $val = $matrizConfusion[$real][$pred] ?? 0;
                                    $isDiagonal = ($real === $pred);
                                    $bg = '';
                                    if ($isDiagonal && $val > 0) {
                                        $bg = 'bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30';
                                    } elseif (!$isDiagonal && $val > 0) {
                                        $bg = 'bg-rose-500/20 text-rose-400 font-bold border border-rose-500/30';
                                    } else {
                                        $bg = 'text-gray-600';
                                    }
                                @endphp
                                <td class="p-3">
                                    <span class="inline-block px-3 py-1.5 rounded-lg {{ $bg }} min-w-[35px]">
                                        {{ $val }}
                                    </span>
                                </td>
                            @endforeach
                            <td class="p-3 font-orbitron text-[10px] font-black text-emerald-400">
                                {{ $metricasPorClase[$real]['recall'] }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRID SECUNDARIO: TRIANGULACIÓN GOLD STANDARD & REGISTROS RECIENTES --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- TRIANGULACIÓN CON TEST PSICOMÉTRICO (SISCO) --}}
        <div class="bg-black/40 border border-white/10 p-6 rounded-3xl backdrop-blur-xl shadow-2xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                <div>
                    <span class="text-[8px] font-black text-purple-400 tracking-widest uppercase font-orbitron block">
                        GOLD STANDARD // PSICOMETRÍA
                    </span>
                    <h3 class="text-xs font-black uppercase font-orbitron adaptive-title">Calibraciones SISCO Recientes</h3>
                </div>
                <i class="fa-solid fa-brain text-purple-400 text-base"></i>
            </div>

            <div class="space-y-3">
                @forelse($evaluacionesPsicometricas as $eval)
                    <div class="p-3 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between">
                        <div>
                            <span class="text-[8px] font-mono font-bold text-gray-400 uppercase">{{ $eval->user->codigo_anonimo }}</span>
                            <p class="text-xs font-black uppercase font-orbitron text-purple-300 mt-0.5">
                                {{ $eval->estado_afectivo_predominante }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-[8px] font-orbitron font-bold uppercase {{ $eval->nivel_estres === 'severo' ? 'text-rose-400' : ($eval->nivel_estres === 'moderado' ? 'text-amber-400' : 'text-emerald-400') }}">
                                Estrés {{ $eval->nivel_estres }}
                            </span>
                            <p class="text-[8px] font-mono text-gray-500">{{ $eval->puntaje_global }}% Global</p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 italic text-center py-6">No hay evaluaciones SISCO registradas aún.</p>
                @endforelse
            </div>
        </div>

        {{-- TELEMETRÍA IMPLÍCITA DE GAMEPLAY RECIENTE --}}
        <div class="bg-black/40 border border-white/10 p-6 rounded-3xl backdrop-blur-xl shadow-2xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                <div>
                    <span class="text-[8px] font-black text-cyan-400 tracking-widest uppercase font-orbitron block">
                        BIOMARCADORES DE JUEGO
                    </span>
                    <h3 class="text-xs font-black uppercase font-orbitron adaptive-title">Inferencia Conductual en Vivo</h3>
                </div>
                <i class="fa-solid fa-microchip text-neon-cyan text-base"></i>
            </div>

            <div class="space-y-3">
                @forelse($telemetriasRecientes as $t)
                    <div class="p-3 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[8px] font-mono font-bold text-gray-400 uppercase">{{ $t->user->codigo_anonimo }}</span>
                                <span class="text-[7px] font-mono px-1.5 py-0.5 rounded bg-white/5 text-gray-500">{{ $t->latencia_promedio_ms }}ms</span>
                            </div>
                            <p class="text-xs font-black uppercase font-orbitron {{ $t->diagnostico_correcto ? 'text-emerald-400' : 'text-rose-400' }} mt-0.5">
                                [{{ strtoupper($t->emocion_predicha) }}] vs Real: {{ strtoupper($t->emocion_objetivo) }}
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-[7px] font-black font-orbitron uppercase {{ $t->diagnostico_correcto ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                            {{ $t->diagnostico_correcto ? 'ACIERTO' : 'DISCORDANCIA' }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 italic text-center py-6">No hay registros de telemetría aún.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
