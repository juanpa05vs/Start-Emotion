@extends('layouts.app')

@section('title', 'Invitaciones | S-Emotion')

@section('content')
<div class="max-w-5xl">

    <a href="{{ route('psicologia.pacientes') }}"
       class="mb-6 inline-flex items-center gap-2 text-[12px] font-semibold text-accent-text transition-colors hover:text-accent">
        <i class="fa-solid fa-arrow-left text-[10px]" aria-hidden="true"></i>
        Mis estudiantes
    </a>

    <div class="mb-6 border-b border-white/5 pb-5">
        <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Invitaciones</h1>
        <p class="mt-1.5 text-[13px] text-gray-400">La conexión siempre la acepta la otra persona.</p>
    </div>

    {{-- Aviso permanente: el límite no es un estado de la pantalla, es la regla
         del sistema, y conviene tenerlo delante en cada visita. --}}
    <div class="mb-7 flex items-start gap-3 rounded-panel border border-accent/25 bg-accent-soft px-4 py-3.5">
        <i class="fa-solid fa-key mt-0.5 text-sm text-accent-text" aria-hidden="true"></i>
        <p class="text-[13px] leading-relaxed text-gray-300">
            No puedes añadir a nadie por tu cuenta. Generas un código, se lo entregas en mano o por
            el canal que acordéis, y
            <strong class="font-semibold adaptive-title">la persona decide si compartir y qué compartir</strong>.
            Cada código vence a los 14 días y sirve una sola vez.
        </p>
    </div>

    <form method="POST" action="{{ route('psicologia.invitaciones.crear') }}" class="mb-8">
        @csrf
        <x-boton-secundario icono="plus">
            Generar un código nuevo
        </x-boton-secundario>
    </form>

    {{-- No se repite aquí el mensaje de `session('success')`: la maqueta ya lo
         anuncia arriba de todo, y durante un tiempo salía dos veces en la misma
         pantalla. --}}

    @if ($invitaciones->isEmpty())
        {{-- Sin acción en el estado vacío a propósito: `x-estado-vacio` la
             renderiza como un `<a>`, y generar un código es un POST. Un enlace
             ahí devolvería un 405. El botón de arriba es el camino y está a
             treinta píxeles. --}}
        <div class="surface rounded-panel">
            <x-estado-vacio
                icono="fa-key"
                titulo="Todavía no has generado ningún código"
                mensaje="Un código es lo único que conecta a una persona estudiante contigo. Ella lo usa, decide qué compartir y tú lo ves en tu lista." />
        </div>
    @else
        <div class="space-y-3">
            @foreach ($invitaciones as $inv)
                @php
                    $vigente = $inv->estaVigente();
                    $usada = $inv->usada_en !== null;
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-panel border border-white/10 p-5">
                    <div class="min-w-0">
                        {{-- El código va en monoespaciada y muy espaciado a propósito:
                             se lee en voz alta o se teclea de memoria, y las claves
                            Letteros pegadas no se distinguen. --}}
                        <p class="font-mono text-[17px] font-bold tracking-[0.3em] adaptive-title">{{ $inv->codigo }}</p>

                        <p class="mt-1.5 text-[12px] text-gray-500">
                            @if ($usada)
                                Canjeado por {{ $inv->usadaPor?->nombre ?? 'un estudiante' }}
                                el {{ $inv->usada_en?->format('d/m/Y') }}
                            @elseif ($vigente)
                                Vence el {{ $inv->expira_en?->format('d/m/Y') }}
                            @else
                                Venció el {{ $inv->expira_en?->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <span @class([
                            'rounded-control border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em]',
                            'border-white/10 bg-white/5 text-gray-500' => $usada,
                            'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' => ! $usada && $vigente,
                            'border-amber-500/30 bg-amber-500/10 text-amber-400' => ! $usada && ! $vigente,
                        ])>
                            {{ $usada ? 'Canjeado' : ($vigente ? 'Vigente' : 'Vencido') }}
                        </span>

                        @unless ($usada)
                            <form method="POST" action="{{ route('psicologia.invitaciones.revocar', $inv) }}"
                                  onsubmit="return confirm('Se anulará este código y quien lo tenga ya no podrá conectarse con él. Se puede generar otro cuando quieras. ¿Continuar?');">
                                @csrf
                                @method('DELETE')
                                <x-boton-peligro tipo="submit" tamano="pequeno" icono="ban"
                                        aria-label="Anular el código {{ $inv->codigo }}">
                                    Anular
                                </x-boton-peligro>
                            </form>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection