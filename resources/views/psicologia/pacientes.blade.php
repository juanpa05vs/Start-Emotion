@extends('layouts.app')

@section('title', 'Mis estudiantes | S-Emotion')

@section('content')
<div class="max-w-5xl">

    <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Mis estudiantes</h1>
            <p class="mt-1.5 text-[13px] text-gray-400">Solo las personas que te dieron su consentimiento.</p>
        </div>

        <x-boton-secundario :href="route('psicologia.invitaciones')" icono="key">
            Generar invitación
        </x-boton-secundario>
    </div>

    {{-- Aviso permanente, no estado vacío: esta restricción está activa
         siempre y conviene tenerla presente, no solo cuando la lista está
         vacía. --}}
    <div class="mb-7 flex items-start gap-3 rounded-panel border border-accent/25 bg-accent-soft px-4 py-3.5">
        <i class="fa-solid fa-shield-halved mt-0.5 text-sm text-accent-text" aria-hidden="true"></i>
        <p class="text-[13px] leading-relaxed text-gray-300">
            Esta lista contiene únicamente a las personas que te dieron su consentimiento
            y que todavía no lo han retirado. Si alguien no aparece aquí, es que no
            autorizó el acceso: no puedes ver sus datos por otras vías.
        </p>
    </div>

    @if ($consentimientos->isEmpty())
        <div class="surface rounded-panel">
            <x-estado-vacio
                icono="fa-user-group"
                titulo="Todavía no atiendes a nadie"
                mensaje="Genera un código y entrégaselo a la persona estudiante. Ella decide qué compartir, y solo entonces aparecerá aquí."
                accion="Generar un código de invitación"
                :url="route('psicologia.invitaciones')" />
        </div>
    @else
        <div class="space-y-3">
            @foreach ($consentimientos as $c)
                @php $estudiante = $c->estudiante; @endphp
                {{-- `.tarjeta` sobre el enlace entero, no sobre una fila de tabla. Cada
                     tarjeta es pulsable y lleva a la ficha, así que se levanta y
                     se ilumina. Además, `color-scheme` no se toca: es un enlace
                     con varios textos dentro y levantarlo es la única señal
                     necesaria —no hay flecha ni botón— para decir que se puede
                     abrir. --}}
                <a href="{{ route('psicologia.paciente', $c) }}"
                   class="tarjeta group flex flex-wrap items-center justify-between gap-4 p-5 hover:bg-accent/[0.04]">
                    <div class="min-w-0">
                        <p class="text-[14px] font-semibold adaptive-title transition-colors group-hover:text-accent-text">
                            {{ $estudiante?->nombre ?? 'Estudiante' }}
                        </p>
                        <p class="mt-1 text-[12px] text-gray-500">
                            <span class="font-mono text-gray-400">{{ $estudiante?->codigo_anonimo }}</span>
                            @if ($estudiante?->edad)<span class="text-gray-600"> · {{ $estudiante->edad }} años</span>@endif
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-[12px] text-gray-500">
                            Autorizado el {{ $c->otorgado_en?->format('d/m/Y') }}
                        </p>
                        <div class="mt-2 flex flex-wrap justify-end gap-1.5">
                            @foreach ($c->alcances() as $alcance)
                                <span class="rounded-control border border-white/10 bg-white/5 px-2 py-0.5 text-[11px] text-gray-300">
                                    {{ $alcance->etiquetaCorta() }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>
@endsection
