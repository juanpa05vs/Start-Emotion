@extends('layouts.app')

@section('title', 'Mis datos | S-Emotion')

@section('content')
<div class="max-w-3xl">

    <div class="mb-7">
        <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Mis datos</h1>
        <p class="mt-1.5 text-[13px] text-gray-400">Tú decides qué se comparte y con quién.</p>
    </div>

    {{-- El principio va primero y con el peso visual de un aviso, no de un
         subtítulo: es la respuesta a la pregunta que trae a cualquier persona a
         esta pantalla. --}}
    <div class="mb-7 rounded-panel border border-accent/25 bg-accent-soft px-5 py-4">
        <h2 class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.12em] text-accent-text">Lo más importante</h2>
        <p class="text-[13px] leading-relaxed text-gray-300">
            Nadie puede ver tus registros, tus evaluaciones ni tus partidas hasta que tú lo autorices.
            Puedes autorizarlo a una persona a la vez, y puedes <strong class="text-white">retirarlo cuando quieras</strong>
            desde esta misma pantalla, sin dar explicaciones y sin que nadie te lo pida.
        </p>
    </div>

    {{-- CONECTAR CON UN PROFESIONAL --}}
    <div class="surface rounded-panel p-5 sm:p-6 mb-8">
        <h2 class="text-[15px] font-semibold adaptive-title">Compartir con un profesional</h2>
        <p class="mt-1 text-[13px] text-gray-400 mb-5">
            Si tu profesional de psicología te dio un código, escríbelo aquí. Tú eliges qué partes compartir.
        </p>

        @if ($errors->any())
            {{-- `rose` en vez del `red` de Tailwind: es el mismo tono y es el que
                 el resto de la aplicación usa para el error. `#fca5a5` sobre
                 blanco daba 1.9:1, así que este era el mensaje que explicaba por
                 qué no se había podido conectar y era el menos legible de la
                 página. --}}
            <div class="mb-5 rounded-control border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
                @foreach ($errors->all() as $error)
                    <p class="text-[13px] text-rose-300">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('privacidad.conectar') }}" class="space-y-5">
            @csrf

            <div>
                <label for="codigo" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                    Código que te dio tu profesional
                </label>
                <input type="text" name="codigo" id="codigo" value="{{ old('codigo') }}" maxlength="16"
                       autocomplete="off" autocapitalize="characters" spellcheck="false"
                       placeholder="K7MQ2XPD"
                       class="campo w-full max-w-xs rounded-control border bg-black/40 px-4 py-2.5 font-mono uppercase tracking-[0.2em] text-[15px] text-white placeholder:tracking-normal placeholder:text-gray-700 placeholder:font-sans">
            </div>

            <div>
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">¿Qué quieres compartir?</p>
                <div class="space-y-2">
                    @foreach ($opciones as $opcion)
                        <label class="flex cursor-pointer items-start gap-3.5 rounded-control border border-white/10 bg-black/30 px-4 py-3 transition-colors hover:border-accent/40">
                            {{-- `accent-accent`, no `accent-neon-cyan`: ese token se eliminó al migrar
                                 el color y la clase quedó muerta, así que la casilla salía con
                                 el azul por defecto del navegador en mitad de una paleta
                                 monocroma. `accent-*` es lo que pinta la marca del check; el
                                 relleno y el filete los pone el navegador. --}}
                            <input type="checkbox" name="alcance[]" value="{{ $opcion->value }}"
                                   class="mt-0.5 h-4 w-4 shrink-0 rounded border-white/20 bg-black accent-accent">
                            <span class="min-w-0">
                                <span class="block text-[13px] font-semibold adaptive-title">{{ $opcion->etiqueta() }}</span>
                                <span class="mt-0.5 block text-[12px] leading-relaxed text-gray-500">{{ $opcion->descripcion() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <x-boton>
                Compartir lo que seleccioné
            </x-boton>
        </form>
    </div>

    {{-- LO QUE COMPARTES AHORA --}}
    <h2 class="mb-4 text-[15px] font-semibold adaptive-title">Lo que compartes ahora</h2>

    @forelse ($compartidos->filter(fn ($c) => $c->estaVigente()) as $consentimiento)
        @php $profesional = $consentimiento->profesional; @endphp
        <div class="surface mb-3 rounded-panel p-5">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[14px] font-semibold adaptive-title">{{ $profesional?->nombre ?? 'Profesional' }}</p>
                    <p class="mt-0.5 text-[12px] text-gray-500">
                        Autorizado el {{ $consentimiento->otorgado_en?->format('d/m/Y') }}
                    </p>
                </div>

                {{-- El estado va con punto + palabra, no solo con color: «Activo»
                     es lo que dice si ese acceso existe, y no puede depender de
                     que se distinga el verde. --}}
                <span class="inline-flex items-center gap-1.5 rounded-pill border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-pill bg-emerald-400" aria-hidden="true"></span>
                    Activo
                </span>
            </div>

            <div class="mb-5 flex flex-wrap gap-1.5">
                @forelse ($consentimiento->alcances() as $alcance)
                    <span class="rounded-control border border-white/10 bg-white/5 px-2 py-0.5 text-[11px] text-gray-300">
                        {{ $alcance->etiquetaCorta() }}
                    </span>
                @empty
                    {{-- Caso real: se autoriza a alguien sin elegir ninguna categoría.
                         No es un estado vacío: es un aviso de que ese acceso no
                         muestra nada, y por eso tiene que verse. --}}
                    <span class="inline-flex items-center gap-1.5 rounded-control border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[11px] text-amber-300">
                        <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i>
                        No autorizaste ninguna categoría, así que este acceso no muestra nada
                    </span>
                @endforelse
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-white/5 pt-4">
                @if ($profesional)
                    <a href="{{ route('psicologia.paciente', $consentimiento) }}"
                       class="inline-flex items-center gap-2 rounded-control border border-white/15 px-3.5 py-2 text-[11px] font-semibold text-gray-300 transition-colors hover:border-white/30 hover:text-white">
                        Ver ficha
                    </a>
                @endif

                {{-- Jerarquía: «retirar» es la acción destructiva, así que va
                     como contorno en rojo y no como otro botón del mismo peso. --}}
                <form method="POST" action="{{ route('privacidad.revocar', $consentimiento) }}"
                      onsubmit="return confirm('¿Retirar el acceso? {{ $profesional?->nombre ?? 'Tu profesional' }} dejará de ver tus datos de inmediato.');">
                    @csrf
                    <input type="hidden" name="motivo" value="">
                    <x-boton-peligro icono="link-slash">
                        Retirar acceso
                    </x-boton-peligro>
                </form>
            </div>
        </div>
    @empty
        <div class="surface rounded-panel">
            <x-estado-vacio
                icono="fa-user-lock"
                titulo="No compartes nada con nadie"
                mensaje="Ningún profesional tiene acceso a tus datos. Si tu profesional te dio un código, puedes autorizarlo con el formulario de arriba; si no, esta pantalla vacía es la respuesta correcta."
                detalle="Puedes compartir y retirar el acceso tantas veces como quieras, sin dar explicaciones." />
        </div>
    @endforelse

    {{-- HISTÓRICO --}}
    @if ($historico->isNotEmpty())
        <h2 class="mb-4 mt-9 text-[15px] font-semibold adaptive-title">Accesos que retiraste</h2>
        <div class="overflow-hidden rounded-panel border border-white/10 bg-black/30">
            <table class="w-full text-left">
                <thead class="border-b border-white/10 bg-white/[0.03]">
                    <tr>
                        <th class="px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Profesional</th>
                        <th class="px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Autorizado</th>
                        <th class="px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Retirado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach ($historico as $item)
                        <tr>
                            <td class="px-5 py-3.5 text-[13px] text-gray-300">{{ $item->profesional?->nombre ?? 'Profesional' }}</td>
                            <td class="px-5 py-3.5 text-[12px] text-gray-500">{{ $item->otorgado_en?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3.5 text-[12px] text-gray-500">{{ $item->revocado_en?->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-[12px] leading-relaxed text-gray-500">
            Guardamos este registro para que puedas comprobar quién tuvo acceso y cuándo.
            Los datos en sí no se guardan aquí.
        </p>
    @endif

    <div class="mt-9 border-t border-white/5 pt-6">
        <a href="{{ route('privacidad.politica') }}"
           class="inline-flex items-center gap-2 text-[13px] font-semibold text-accent-text hover:underline hover:underline-offset-4">
            Leer la política de privacidad completa
            <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
        </a>
    </div>

</div>
@endsection
