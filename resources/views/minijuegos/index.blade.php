@extends('layouts.app')

@section('title', 'Actividades | S-Emotion')

@section('content')
{{-- Sin `p-6`: el `<main>` de la maqueta ya pone el relleno. --}}
<div class="max-w-5xl">

    <div class="mb-7">
        <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Actividades</h1>
        <p class="mt-1.5 text-[13px] text-gray-400">
            Ejercicios de autoobservación que puede pedirte tu profesional de
            psicología. Los resultados solo se comparten si tú lo autorizas.
        </p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">

        {{-- ── CÓDIGO ANÓMALO ────────────────────────────────────────────
             Lleva `.tarjeta`: es lo único de esta pantalla que se puede abrir, y
             se levanta al pasar el ratón por una razón —señalar que hay algo
             pulsable dentro—. Las de «no disponible» NO la llevan, y por eso se
             quedan quietas: si todo se levanta al pasar por encima, no se puede
             distinguir lo disponible de lo que todavía no existe, que es
             justamente lo que esta pantalla tiene que comunicar. --}}
        <section class="tarjeta flex flex-col p-5">
            <span class="mb-4 inline-flex w-fit items-center gap-1.5 rounded-pill border border-accent/30 bg-accent-soft px-2.5 py-1 text-[11px] font-semibold text-accent-text">
                <span class="h-1.5 w-1.5 rounded-pill bg-accent" aria-hidden="true"></span>
                Disponible
            </span>

            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-control border border-accent/25 bg-accent-soft text-accent-text">
                <i class="fa-solid fa-code text-[15px]" aria-hidden="true"></i>
            </div>

            <h2 class="font-orbitron text-[15px] font-bold tracking-tight text-white">Código Anómalo</h2>
            <p class="mt-1 text-[12px] text-gray-500">Actividad de autoobservación</p>

            <p class="mt-3 flex-1 text-[13px] leading-relaxed text-gray-400">
                Ocho emociones se esconden detrás de un fallo de código. Tienes que
                deducir cuál es a partir de lo que hace, de lo que lo rodea y de lo que
                te hace sospechar. Sirve para practicar poner nombre a lo que sientes.
            </p>

            {{-- La tinta del botón va en `text-accent-ink`, no en `text-white`: los
                 cuatro acentos disponibles son todos claros y el blanco sobre un
                 cian claro da 1.8:1. --}}
            <x-boton :href="route('minijuegos.diagnostico')" class="mt-5" icono="play">
                Empezar
            </x-boton>
        </section>

        {{-- ── NO DISPONIBLES ─────────────────────────────────────────────
             Se muestran, y no se esconden, para que quede claro que la
             herramienta tiene más de una pantalla y que estas están en
             camino. Lo que no se hace es ponerlas al mismo nivel: sin borde
             de acento, sin icono saturado y sin botón pulsable. --}}
        @foreach ([
            ['Respiración Guiada', 'fa-wind', 'Ritmo y respiración',
             'Para trabajar momentos de ansiedad y estrés académico antes de una prueba o de una presentación.'],
            ['Filtro de Pensamientos', 'fa-shuffle', 'Replantear pensamientos',
             'Para reconocer los pensamientos automáticos que generan malestar y buscar una lectura alternativa.'],
        ] as [$titulo, $icono, $subtitulo, $descripcion])
            <section class="flex flex-col rounded-panel border border-white/5 bg-black/20 p-5">
                <span class="mb-4 inline-flex w-fit items-center rounded-pill border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                    No disponible
                </span>

                <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-control border border-white/10 bg-white/5 text-gray-500">
                    <i class="fa-solid {{ $icono }} text-[15px]" aria-hidden="true"></i>
                </div>

                <h2 class="font-orbitron text-[15px] font-bold tracking-tight text-gray-400">{{ $titulo }}</h2>
                <p class="mt-1 text-[12px] text-gray-500">{{ $subtitulo }}</p>

                <p class="mt-3 flex-1 text-[13px] leading-relaxed text-gray-500">{{ $descripcion }}</p>

                {{-- `aria-disabled` sobre un `<p>` y no un `disabled` sobre un
                     `<button>`: es un rótulo, no un control. Con un botón
                     deshabilitado habría que adivinar por qué no responde. --}}
                <p class="mt-5 inline-flex items-center justify-center gap-2 rounded-control border border-white/5 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500"
                   aria-disabled="true">
                    <i class="fa-solid fa-clock text-[10px]" aria-hidden="true"></i>
                    Próximamente
                </p>
            </section>
        @endforeach

    </div>

    {{-- Aviso de privacidad. Va aquí y no solo en `/privacidad` porque este es
         justo el sitio donde surge la duda: «¿esto se lo va a ver mi
         psicólogo?». --}}
    <div class="mt-6 flex items-start gap-3 rounded-panel border border-white/10 bg-white/5 px-5 py-4">
        <i class="fa-solid fa-lock mt-0.5 text-[12px] text-gray-500" aria-hidden="true"></i>
        <p class="text-[13px] leading-relaxed text-gray-400">
            Tu profesional es quien decide si te pide esta actividad, pero lo que
            haces aquí <strong class="font-semibold text-gray-300">no se le envía
            automáticamente</strong>. Solo se comparte cuando das tu consentimiento,
            y puedes revocarlo cuando quieras desde
            <a href="{{ route('privacidad.index') }}" class="font-medium text-accent-text underline underline-offset-2 hover:text-accent">Privacidad</a>.
        </p>
    </div>
</div>
@endsection