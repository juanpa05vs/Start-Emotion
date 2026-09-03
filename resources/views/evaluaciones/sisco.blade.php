@extends('layouts.app')

@section('title', 'S-Emotion | Calibración Psicométrica SISCO')

@push('styles')
<style>
    /* Estilos reactivos para los botones de escala Likert */
    .likert-option input[type="radio"]:checked + label {
        background-color: var(--neon-accent);
        color: #000000;
        font-weight: 900;
        border-color: var(--neon-accent);
        box-shadow: 0 0 15px var(--neon-accent);
        transform: scale(1.05);
    }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-8 select-none">

    {{-- CABECERA --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-white/10 pb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-[8px] font-black tracking-widest uppercase bg-purple-500/10 text-purple-400 border border-purple-500/30 font-orbitron">
                    GOLD STANDARD // INVENTARIO SISCO
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[8px] font-black tracking-widest uppercase bg-cyan-500/10 text-neon-cyan border border-cyan-500/30 font-orbitron">
                    BAREMACIÓN PSICOMÉTRICA
                </span>
            </div>
            <h1 class="font-orbitron text-2xl font-black adaptive-title uppercase tracking-tighter">
                Calibración de <span class="text-accent">Estrés Académico</span>
            </h1>
            <p class="text-gray-500 text-[9px] uppercase tracking-[0.3em] mt-1 font-semibold">
                Evaluación estandarizada para determinar el estado de referencia conductual
            </p>
        </div>

        @if(isset($ultimaEvaluacion))
            <div class="bg-black/40 border border-white/10 p-4 rounded-2xl backdrop-blur-md flex items-center gap-4">
                <div>
                    <span class="text-[7px] text-gray-500 uppercase tracking-widest block font-orbitron font-black">Última Calibración</span>
                    <span class="text-xs font-black uppercase font-orbitron {{ $ultimaEvaluacion->nivel_estres === 'severo' ? 'text-rose-500' : ($ultimaEvaluacion->nivel_estres === 'moderado' ? 'text-amber-400' : 'text-emerald-400') }}">
                        Estrés {{ $ultimaEvaluacion->nivel_estres }} ({{ $ultimaEvaluacion->puntaje_global }}%)
                    </span>
                </div>
                <div class="h-8 w-[1px] bg-white/10"></div>
                <div class="text-right">
                    <span class="text-[7px] text-gray-500 uppercase tracking-widest block font-orbitron font-black">Predominante</span>
                    <span class="text-xs font-black uppercase font-orbitron text-purple-400">
                        {{ $ultimaEvaluacion->estado_afectivo_predominante }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    {{-- GUÍA DE ESCALA LIKERT --}}
    <div class="bg-white/[0.02] border border-white/5 p-4 rounded-2xl flex flex-wrap items-center justify-between gap-2 text-[8px] font-orbitron uppercase text-gray-400">
        <span class="font-black text-accent">[ GUÍA DE ESCALA LIKERT ]:</span>
        <span><strong class="text-white">1</strong> = Nunca</span>
        <span><strong class="text-white">2</strong> = Rara vez</span>
        <span><strong class="text-white">3</strong> = Algunas veces</span>
        <span><strong class="text-white">4</strong> = Casi siempre</span>
        <span><strong class="text-white">5</strong> = Siempre</span>
    </div>

    {{-- FORMULARIO SISCO --}}
    <form action="{{ route('psicometria.store') }}" method="POST" class="space-y-8">
        @csrf

        {{-- ======================================================== --}}
        {{-- DIMENSIÓN 1: ESTRESORES ACADÉMICOS --}}
        {{-- ======================================================== --}}
        <div class="bg-black/40 border border-white/10 p-6 md:p-8 rounded-3xl backdrop-blur-xl shadow-2xl space-y-6">
            <div class="flex items-center gap-3 border-b border-white/5 pb-4">
                <div class="h-8 w-8 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 font-orbitron font-black text-xs">
                    01
                </div>
                <div>
                    <h2 class="font-orbitron text-sm font-black uppercase tracking-wider text-cyan-400">
                        Dimensión I: Estresores Académicos
                    </h2>
                    <p class="text-gray-500 text-[8px] uppercase tracking-widest">Estímulos del entorno universitario que generan tensión</p>
                </div>
            </div>

            @php
                $estresores = [
                    ['name' => 'e_sobrecarga', 'label' => 'Sobrecarga de tareas, reportes y trabajos escolares.'],
                    ['name' => 'e_evaluaciones', 'label' => 'Evaluaciones, exámenes parciales y entregas finales.'],
                    ['name' => 'e_tiempo', 'label' => 'Tiempo limitado para realizar las actividades académicas.'],
                    ['name' => 'e_profesores', 'label' => 'Nivel de exigencia o metodología de los docentes.'],
                ];
            @endphp

            <div class="space-y-4">
                @foreach($estresores as $item)
                    <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-white/20 transition-all">
                        <p class="text-xs adaptive-title font-medium leading-relaxed md:max-w-xl">
                            {{ $item['label'] }}
                        </p>
                        <div class="flex items-center gap-2 shrink-0">
                            @for($i = 1; $i <= 5; $i++)
                                <div class="likert-option">
                                    <input type="radio"
                                           id="{{ $item['name'] }}_{{ $i }}"
                                           name="{{ $item['name'] }}"
                                           value="{{ $i }}"
                                           class="sr-only"
                                           required>
                                    <label for="{{ $item['name'] }}_{{ $i }}"
                                           class="h-9 w-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-xs font-orbitron cursor-pointer hover:border-white/40 transition-all text-gray-300">
                                        {{ $i }}
                                    </label>
                                </div>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- DIMENSIÓN 2: MANIFESTACIONES (SÍNTOMAS / REACCIONES) --}}
        {{-- ======================================================== --}}
        <div class="bg-black/40 border border-white/10 p-6 md:p-8 rounded-3xl backdrop-blur-xl shadow-2xl space-y-6">
            <div class="flex items-center gap-3 border-b border-white/5 pb-4">
                <div class="h-8 w-8 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 font-orbitron font-black text-xs">
                    02
                </div>
                <div>
                    <h2 class="font-orbitron text-sm font-black uppercase tracking-wider text-rose-400">
                        Dimensión II: Manifestaciones Corporales, Psicológicas y Anímicas
                    </h2>
                    <p class="text-gray-500 text-[8px] uppercase tracking-widest">Reacciones físicas y emocionales experimentadas frente al estrés</p>
                </div>
            </div>

            @php
                $sintomas = [
                    ['name' => 's_fatiga', 'label' => 'Fatiga crónica, cansancio excesivo, dolores de cabeza o tensión muscular.'],
                    ['name' => 's_ansiedad', 'label' => 'Inquietud, palpitaciones, sensación de prisa constante o angustia.'],
                    ['name' => 's_concentracion', 'label' => 'Problemas de concentración, olvidos frecuentes o bloqueo mental.'],
                    ['name' => 's_frustracion', 'label' => 'Sentimientos de irritabilidad, impotencia, frustración o desgano.'],
                ];
            @endphp

            <div class="space-y-4">
                @foreach($sintomas as $item)
                    <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-white/20 transition-all">
                        <p class="text-xs adaptive-title font-medium leading-relaxed md:max-w-xl">
                            {{ $item['label'] }}
                        </p>
                        <div class="flex items-center gap-2 shrink-0">
                            @for($i = 1; $i <= 5; $i++)
                                <div class="likert-option">
                                    <input type="radio"
                                           id="{{ $item['name'] }}_{{ $i }}"
                                           name="{{ $item['name'] }}"
                                           value="{{ $i }}"
                                           class="sr-only"
                                           required>
                                    <label for="{{ $item['name'] }}_{{ $i }}"
                                           class="h-9 w-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-xs font-orbitron cursor-pointer hover:border-white/40 transition-all text-gray-300">
                                        {{ $i }}
                                    </label>
                                </div>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- DIMENSIÓN 3: ESTRATEGIAS DE AFRONTAMIENTO --}}
        {{-- ======================================================== --}}
        <div class="bg-black/40 border border-white/10 p-6 md:p-8 rounded-3xl backdrop-blur-xl shadow-2xl space-y-6">
            <div class="flex items-center gap-3 border-b border-white/5 pb-4">
                <div class="h-8 w-8 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 font-orbitron font-black text-xs">
                    03
                </div>
                <div>
                    <h2 class="font-orbitron text-sm font-black uppercase tracking-wider text-purple-400">
                        Dimensión III: Estrategias de Afrontamiento
                    </h2>
                    <p class="text-gray-500 text-[8px] uppercase tracking-widest">Acciones implementadas para mitigar la carga académica</p>
                </div>
            </div>

            @php
                $afrontamiento = [
                    ['name' => 'a_resolucion', 'label' => 'Habilidad para planificar, priorizar y resolver problemas paso a paso.'],
                    ['name' => 'a_comunicacion', 'label' => 'Búsqueda de apoyo social, diálogo con compañeros o asesoría docente.'],
                ];
            @endphp

            <div class="space-y-4">
                @foreach($afrontamiento as $item)
                    <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-white/20 transition-all">
                        <p class="text-xs adaptive-title font-medium leading-relaxed md:max-w-xl">
                            {{ $item['label'] }}
                        </p>
                        <div class="flex items-center gap-2 shrink-0">
                            @for($i = 1; $i <= 5; $i++)
                                <div class="likert-option">
                                    <input type="radio"
                                           id="{{ $item['name'] }}_{{ $i }}"
                                           name="{{ $item['name'] }}"
                                           value="{{ $i }}"
                                           class="sr-only"
                                           required>
                                    <label for="{{ $item['name'] }}_{{ $i }}"
                                           class="h-9 w-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-xs font-orbitron cursor-pointer hover:border-white/40 transition-all text-gray-300">
                                        {{ $i }}
                                    </label>
                                </div>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- BOTÓN DE ENVÍO --}}
        <div class="pt-6 pb-12 flex justify-end">
            <button type="submit"
                    style="background-color: var(--neon-accent); color: #000000; box-shadow: 0 0 25px rgba(var(--neon-accent-rgb), 0.4);"
                    class="w-full md:w-auto font-orbitron text-xs font-black uppercase tracking-[0.2em] px-8 py-4 rounded-2xl hover:bg-white hover:text-black hover:shadow-[0_0_35px_#ffffff] transition-all duration-300 active:scale-95 flex items-center justify-center gap-3 cursor-pointer">
                <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                [ GUARDAR Y CALIBRAR BASE PSICOMÉTRICA ]
            </button>
        </div>

    </form>
</div>
@endsection
