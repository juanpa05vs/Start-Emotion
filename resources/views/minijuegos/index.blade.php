@extends('layouts.app')

@section('content')
<div class="p-6 max-w-6xl mx-auto">

    {{-- ENCABEZADO --}}
    <div class="border-b border-white/5 pb-4 mb-8">
        <h1 class="font-orbitron text-3xl font-black text-white uppercase tracking-tighter">
            Sector de <span class="text-accent animate-pulse">Recalibración</span>
        </h1>
        <p class="text-gray-500 text-[10px] uppercase tracking-[0.3em] mt-1">Módulos de Entretenimiento Cognitivo y Diagnóstico Bio-Digital</p>
    </div>

    {{-- PARRILLA DE MINIJUEGOS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        {{-- CARD JUEGO 1: TU IDEA (ADIVINA QUIÉN) --}}
        <div class="bg-black/40 border border-accent/30 backdrop-blur-xl p-6 rounded-2xl relative flex flex-col justify-between shadow-[0_0_15px_rgba(34,211,238,0.1)] group hover:border-accent hover:scale-[1.01] transition-all duration-300">
            <div class="absolute top-3 right-3 bg-accent/20 border border-accent text-accent text-[7px] font-black uppercase px-2 py-0.5 rounded tracking-widest animate-pulse">
                Online // Activo
            </div>

            <div>
                <div class="h-12 w-12 rounded-xl bg-accent/10 border border-accent/20 flex items-center justify-center text-accent text-xl mb-4 group-hover:bg-accent group-hover:text-black transition-all">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <h3 class="font-orbitron text-base font-black text-white uppercase tracking-tight group-hover:text-accent transition-colors">Código Anómalo</h3>
                <p class="text-[8px] text-gray-500 uppercase tracking-wider mt-0.5 font-bold">Estilo: Adivina Quién Emocional</p>
                <p class="text-gray-400 text-xs mt-3 leading-relaxed">
                    El sistema ha aislado una anomalía en el núcleo. Analiza los síntomas fisiológicos, los detonantes del entorno y los bucles cognitivos para descartar las variables falsas y aislar la emoción correcta.
                </p>
            </div>

            <div class="mt-6 pt-4 border-t border-white/5">
                <a href="{{ route('minijuegos.diagnostico') }}"
                   class="block w-full text-center bg-accent text-white py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest hover:shadow-[0_0_15px_var(--neon-accent)] transition-all">
                    Iniciar Depuración
                </a>
            </div>
        </div>

        {{-- CARD JUEGO 2: SINCRONIZACIÓN CUÁNTICA (PROXIMAMENTE) --}}
        <div class="bg-black/20 border border-white/5 backdrop-blur-xl p-6 rounded-2xl relative flex flex-col justify-between opacity-50 select-none">
            <div class="absolute top-3 right-3 bg-purple-500/10 border border-purple-500/30 text-purple-400 text-[7px] font-black uppercase px-2 py-0.5 rounded tracking-widest">
                Fase_02 // Bloqueado
            </div>

            <div>
                <div class="h-12 w-12 rounded-xl bg-purple-500/5 border border-purple-500/10 flex items-center justify-center text-purple-400 text-xl mb-4">
                    <i class="fa-solid fa-heart-pulse"></i>
                </div>
                <h3 class="font-orbitron text-base font-black text-gray-400 uppercase tracking-tight">Sincronización de Núcleo</h3>
                <p class="text-[8px] text-gray-600 uppercase tracking-wider mt-0.5 font-bold">Estilo: Ritmo y Biofeedback</p>
                <p class="text-gray-500 text-xs mt-3 leading-relaxed">
                    Calibra las pulsaciones del sistema sincronizando tu respiración con la onda expansiva cuántica. Diseñado para mitigar picos críticos de ansiedad y estrés académico.
                </p>
            </div>

            <div class="mt-6 pt-4 border-t border-white/5">
                <button disabled class="w-full text-center bg-white/5 border border-white/5 text-gray-600 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest cursor-not-allowed">
                    [ Módulo Encriptado ]
                </button>
            </div>
        </div>

        {{-- CARD JUEGO 3: FILTRO DE DISTORSIONES (PROXIMAMENTE) --}}
        <div class="bg-black/20 border border-white/5 backdrop-blur-xl p-6 rounded-2xl relative flex flex-col justify-between opacity-50 select-none">
            <div class="absolute top-3 right-3 bg-rose-500/10 border border-rose-500/30 text-rose-400 text-[7px] font-black uppercase px-2 py-0.5 rounded tracking-widest">
                Fase_03 // Bloqueado
            </div>

            <div>
                <div class="h-12 w-12 rounded-xl bg-rose-500/5 border border-rose-500/10 flex items-center justify-center text-rose-400 text-xl mb-4">
                    <i class="fa-solid fa-ghost"></i>
                </div>
                <h3 class="font-orbitron text-base font-black text-gray-400 uppercase tracking-tight">Filtro de Pensamientos</h3>
                <p class="text-[8px] text-gray-600 uppercase tracking-wider mt-0.5 font-bold">Estilo: Arcade Destructor</p>
                <p class="text-gray-500 text-xs mt-3 leading-relaxed">
                    Intercepta y destruye las distorsiones cognitivas automáticas que caen del servidor antes de que saturen el procesador mental. Captura solo los datos de análisis objetivo.
                </p>
            </div>

            <div class="mt-6 pt-4 border-t border-white/5">
                <button disabled class="w-full text-center bg-white/5 border border-white/5 text-gray-600 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest cursor-not-allowed">
                    [ Módulo Encriptado ]
                </button>
            </div>
        </div>

    </div>
</div>
@endsection
