@extends('layouts.app')

@section('title', 'Comentarios | S-Emotion')

@section('content')
<div class="max-w-4xl">

    {{-- Los comentarios se leen en lista, no en rejilla de tres columnas.

         En rejilla cada tarjeta ocupa como mucho un tercio del ancho y hay que
         pasar la vista tres veces para ver todo lo que ha llegado. En lista,
         varias entradas caben a la vez en la pantalla y se comparan sin
         desplazamiento, que es lo que hace falta para decidir a cuál darle
         prioridad. --}}
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Comentarios</h1>
            <p class="mt-1.5 text-[13px] text-gray-400">
                Lo que han escrito quienes usan la herramienta. Márcalos como
                finalizados cuando los hayas atendido.
            </p>
        </div>

        <p class="text-[12px] text-gray-500">
            <span class="font-mono text-[15px] text-accent-text">{{ count($reportes) }}</span>
            {{ \Illuminate\Support\Str::plural('comentario', count($reportes)) }}
        </p>
    </div>

    @forelse ($reportes as $reporte)
        @php
            // `resuelto` es el estado bueno y `pendiente` el que exige atención.
            // El color va en la misma clave que la etiqueta para que no puedan
            // contradecirse: antes el texto venía del dato y el color de una
            // condición aparte, y bastaba un cambio en el controlador para que
            // «pendiente» saliera en verde.
            $estados = [
                'resuelto' => ['etiqueta' => 'Atendido', 'clases' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'],
                'pendiente' => ['etiqueta' => 'Pendiente', 'clases' => 'border-amber-500/30 bg-amber-500/10 text-amber-300'],
            ];
            $estado = $estados[$reporte->estado] ?? ['etiqueta' => \Illuminate\Support\Str::headline($reporte->estado ?? 'Recibido'), 'clases' => 'border-white/10 bg-white/5 text-gray-400'];
            $nombre = $reporte->user->nombre ?? 'Cuenta eliminada';
        @endphp

        <article class="mb-3 flex gap-4 rounded-panel border border-white/10 bg-black/40 p-4 transition-colors hover:border-white/20 sm:p-5">

            {{-- La acción de borrar estaba en `opacity-0 group-hover:opacity-100`:
                 solo aparecía al pasar el ratón por encima. Con teclado no se
                 veía nunca, y en un móvil no se podía tocar. --}}
            <div class="shrink-0">
                <img src="{{ $reporte->user && $reporte->user->avatar
                        ? asset('storage/' . $reporte->user->avatar)
                        : 'https://ui-avatars.com/api/?name='.urlencode($nombre).'&background=000&color=fff' }}"
                     alt=""
                     class="h-10 w-10 rounded-pill border border-accent/25 object-cover">
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                    <h2 class="text-[14px] font-semibold text-white">{{ $nombre }}</h2>

                    <span class="rounded-pill border px-2 py-0.5 text-[11px] font-medium {{ $estado['clases'] }}">
                        {{ $estado['etiqueta'] }}
                    </span>

                    {{-- La fecha va al final de la línea de metadatos, no
                         destacada: es el dato menos accionable de la fila. --}}
                    <time datetime="{{ $reporte->created_at->toIso8601String() }}"
                          class="text-[12px] text-gray-500">
                        {{ $reporte->created_at->translatedFormat('j \d\e F, H:i') }}
                    </time>
                </div>

                <blockquote class="mt-2.5 border-l-2 border-accent/25 pl-3.5">
                    <p class="text-[13px] leading-relaxed text-gray-300">
                        {{ $reporte->comentario }}
                    </p>
                </blockquote>

                <div class="mt-3.5 flex flex-wrap items-center gap-2">
                    @unless ($reporte->estado === 'resuelto')
                        <form action="{{ route('feedback.updateStatus', $reporte->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            {{-- Antes era un botón con `border-emerald-500/40` y
                                 `text-emerald-400`: el texto se remapeaba en modo
                                 claro pero el filete no, y `--color-emerald-500`
                                 sobre blanco se quedaba en 2.5:1 cuando el umbral
                                 de un filete es 3:1. El estado «atendido» ya lo
                                 dice la etiqueta de al lado; el botón no necesita
                                 ser verde para decirlo también. --}}
                            <x-boton-secundario type="submit" tamano="pequeno" icono="check">
                                Marcar como atendido
                            </x-boton-secundario>
                        </form>
                    @endunless

                    <form action="{{ route('feedback.destroy', $reporte->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <x-boton-peligro tipo="submit" tamano="pequeno" icono="trash-can"
                                 onclick="return confirm('¿Borrar este comentario? No se puede recuperar.')"
                                 aria-label="Borrar el comentario de {{ $nombre }}">
                            Borrar
                        </x-boton-peligro>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="surface rounded-panel">
            <x-estado-vacio
                icono="fa-comment-dots"
                titulo="Todavía no hay comentarios"
                mensaje="Aquí aparecerá lo que escriban las personas usuarias desde su cuenta."
                detalle="Si alguien envía un comentario y no lo ves en esta lista, conviene mirar los registros del servidor: el formulario es el único punto por el que entra." />
        </div>
    @endforelse

</div>
@endsection