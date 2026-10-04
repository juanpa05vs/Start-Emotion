@extends('layouts.app')

@section('title', ($estudiante?->nombre ?? 'Ficha').' | S-Emotion')

@section('content')
<div class="max-w-5xl">

    <a href="{{ route('psicologia.pacientes') }}"
       class="mb-6 inline-flex items-center gap-2 text-[12px] font-semibold text-accent-text transition-colors hover:text-accent">
        <i class="fa-solid fa-arrow-left text-[10px]" aria-hidden="true"></i>
        Mis estudiantes
    </a>

    {{-- El nombre va sin forzar a mayúsculas: es un nombre propio y en el resto
         de la aplicación los nombres no van en mayúsculas. --}}
    <div class="mb-6 border-b border-white/5 pb-5">
        <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">
            {{ $estudiante?->nombre ?? 'Estudiante' }}
        </h1>
        <p class="mt-1.5 text-[13px] text-gray-400">
            Ficha interna <span class="font-mono text-gray-300">{{ $estudiante?->codigo_anonimo }}</span>
            <span class="text-gray-600">·</span>
            consentimiento vigente desde {{ $consentimiento->otorgado_en?->format('d/m/Y') }}
        </p>
    </div>

    {{-- Aviso permanente: la restricción está activa siempre, no solo cuando
         falta algún bloque, así que no debe aparecer y desaparecer.

         La redacción importa aquí. Antes decía que las secciones no autorizadas
         «aparecen vacías a propósito», lo que se lee como «los datos están ahí y
         no me los enseñan». No es el caso: el controlador no consulta esas
         tablas cuando el alcance no lo cubre, así que se puede decir sin
         reservas que los datos ni siquiera se llegan a pedir. --}}
    <div class="mb-8 flex items-start gap-3 rounded-panel border border-accent/25 bg-accent-soft px-4 py-3.5">
        <i class="fa-solid fa-shield-halved mt-0.5 text-sm text-accent-text" aria-hidden="true"></i>
        <p class="text-[13px] leading-relaxed text-gray-300">
            Esta ficha solo muestra lo que la persona estudiante autorizó. Las secciones que
            no autorizó no aparecen porque no se consultan: su contenido no llega a esta
            pantalla. Puedes ver exactamente qué comparte en la lista de
            <a href="{{ route('psicologia.pacientes') }}" class="font-semibold text-accent-text hover:underline">mis estudiantes</a>.
        </p>
    </div>

    {{--
        Las tres secciones de datos van como módulos y no apiladas.

        Además de acortar la pantalla, esto resuelve una pregunta que antes
        obligaba a recorrer las tres secciones para contestar: si esta persona
        autorizó o no cada cosa. El icono de la pestaña lo dice de entrada —un
        candado en vez del icono del bloque—, así que quien abre la ficha sabe
        sin pulsar nada qué le falta por ver. Con las secciones apiladas había que
        bajarse hasta el final para enterarse de que una de ellas no se podía
        tocar.
    --}}
    @php
        $pestanas = [
            [
                'id' => 'registros',
                'titulo' => 'Registros',
                'icono' => $consentimiento->incluye(\App\Enums\AlcanceConsentimiento::Registros)
                    ? 'fa-notebook'
                    : 'fa-lock',
            ],
            [
                'id' => 'evaluaciones',
                'titulo' => 'Evaluaciones',
                'icono' => $consentimiento->incluye(\App\Enums\AlcanceConsentimiento::Evaluaciones)
                    ? 'fa-clipboard-check'
                    : 'fa-lock',
            ],
            [
                'id' => 'juego',
                'titulo' => 'Actividad',
                'icono' => $consentimiento->incluye(\App\Enums\AlcanceConsentimiento::Juego)
                    ? 'fa-gamepad'
                    : 'fa-lock',
            ],
        ];
    @endphp

    <x-modulos :modulos="$pestanas">
    {{-- ── REGISTROS ──────────────────────────────────────────────────── --}}
    <x-modulo id="registros" activo>
        <section>
            <h2 class="mb-3 text-[15px] font-semibold adaptive-title">Registros diarios</h2>

        @if (! $consentimiento->incluye(\App\Enums\AlcanceConsentimiento::Registros))
            <div class="surface rounded-panel">
                <x-estado-vacio
                    icono="fa-lock"
                    titulo="No autorizó compartir sus registros"
                    mensaje="Compartió contigo otras cosas, pero los registros diarios quedan fuera. Para verlos tiene que retirarlos de la lista de autorizaciones y volver a aceptarlos." />
            </div>
        @elseif ($registros->isEmpty())
            <div class="surface rounded-panel">
                <x-estado-vacio
                    icono="fa-inbox"
                    titulo="Todavía no ha registrado cómo se ha sentido"
                    mensaje="Cuando anote su primera emoción aparecerá aquí. No hay nada que revisar de momento." />
            </div>
        @else
            <div class="overflow-hidden rounded-panel border border-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left">
                        <caption class="sr-only">
                            Registros diarios de {{ $estudiante?->nombre ?? 'la persona estudiante' }}
                        </caption>
                        <thead class="border-b border-white/10 bg-white/5 text-[11px] uppercase tracking-[0.12em] text-gray-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">Fecha</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Emoción</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Energía</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Estrés</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Nota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($registros as $r)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-[13px] text-gray-400">
                                        <time datetime="{{ $r->created_at?->toDateString() }}">{{ $r->created_at?->format('d/m/Y') }}</time>
                                    </td>
                                    {{-- La emoción se muestra tal cual la guarda la base de datos, sin
                                         «capitalizar»: es lo mismo que lee quien abre el
                                         historial, y el valor crudo es además lo que
                                         permite comparar la ficha con el historial sin
                                         traducir. --}}
                                    <td class="px-5 py-3.5 text-[13px] font-medium text-gray-200">
                                        {{ $r->emocion ?? '—' }}
                                    </td>
                                    {{-- `energia` va de 1 a 100, igual que en el registro. Aquí decía «/10», que
                                     salía de una versión anterior de la barra en la que el
                                     máximo era 10: con un valor de 86 la celda leía «86/10». --}}
                                    <td class="px-5 py-3.5 text-[13px] tabular-nums text-gray-300">
                                        {{ $r->energia ?? '—' }}<span class="text-gray-600">%</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-[13px] tabular-nums text-gray-300">
                                        {{ $r->nivel_estres_estimado ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-[13px] text-gray-400">
                                        {{ \Illuminate\Support\Str::limit($r->observaciones, 60) ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
    </x-modulo>

    {{-- ── EVALUACIONES ───────────────────────────────────────────────── --}}
    <x-modulo id="evaluaciones">
        <section>
            <h2 class="mb-3 text-[15px] font-semibold adaptive-title">Evaluaciones psicológicas</h2>

        @if (! $consentimiento->incluye(\App\Enums\AlcanceConsentimiento::Evaluaciones))
            <div class="surface rounded-panel">
                <x-estado-vacio
                    icono="fa-lock"
                    titulo="No autorizó compartir sus evaluaciones"
                    mensaje="Las respuestas de los cuestionarios SISCO son de las más delicadas que hay aquí, y son suyas: compartirlas es una decisión suya." />
            </div>
        @elseif ($evaluaciones->isEmpty())
            <div class="surface rounded-panel">
                <x-estado-vacio
                    icono="fa-inbox"
                    titulo="No ha completado ninguna evaluación"
                    mensaje="La encuesta SISCO aparece en su propio menú cuando quiera hacerla." />
            </div>
        @else
            <div class="space-y-3">
                @foreach ($evaluaciones as $e)
                    <div class="rounded-panel border border-white/10 p-5">
                        <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-[14px] font-semibold adaptive-title">
                                    <time datetime="{{ $e->created_at?->toDateString() }}">{{ $e->created_at?->translatedFormat('j \d\e F \d\e Y') }}</time>
                                </p>
                                <p class="mt-0.5 text-[12px] text-gray-500">Encuesta SISCO</p>
                            </div>

                            {{-- Un solo componente para los tres niveles. Antes cada
                                 nivel elegía sus propias clases de color y el de
                                 «severo» usaba el `red` de Tailwind, que sobre
                                 blanco daba 2.6:1: era el nivel más alto de
                                 estrés y era el único que no se leía. --}}
                            <span @class([
                                'rounded-control border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em]',
                                'border-rose-500/30 bg-rose-500/10 text-rose-400' => $e->nivel_estres === 'severo',
                                'border-amber-500/30 bg-amber-500/10 text-amber-400' => $e->nivel_estres === 'moderado',
                                'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' => $e->nivel_estres === 'bajo',
                            ])>
                                Estrés {{ $e->nivelEstresEtiqueta() }}
                            </span>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            @foreach ([
                                ['Global', $e->puntaje_global],
                                ['Estresores', $e->puntaje_estresores],
                                ['Síntomas', $e->puntaje_sintomas],
                                ['Afrontamiento', $e->puntaje_afrontamiento],
                            ] as [$etiqueta, $puntaje])
                                {{-- `.dato`: aquí los cuatro puntuajes son lo que se viene a mirar
                                         de esta tarjeta, y antes competían por el mismo
                                         tamaño con el rótulo que los explica. --}}
                                <div class="rounded-control border border-white/5 bg-white/5 px-3.5 py-2.5">
                                    <dt class="dato-rotulo mb-1">{{ $etiqueta }}</dt>
                                    <dd class="dato dato--mediano dato--neutro">{{ $puntaje ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        @if ($e->estadoAfectivoEtiqueta())
                            <p class="mt-3.5 text-[13px] text-gray-400">
                                Estado predominante:
                                <span class="font-medium text-gray-200">{{ $e->estadoAfectivoEtiqueta() }}</span>
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
    </x-modulo>

    {{-- ── PARTIDAS ───────────────────────────────────────────────────── --}}
    <x-modulo id="juego">
        <section>
            <h2 class="mb-3 text-[15px] font-semibold adaptive-title">Actividad en el juego</h2>

        @if (! $consentimiento->incluye(\App\Enums\AlcanceConsentimiento::Juego))
            <div class="surface rounded-panel">
                <x-estado-vacio
                    icono="fa-lock"
                    titulo="No autorizó compartir su actividad en el juego"
                    mensaje="Compartía el tiempo que juega y su ritmo de respuesta, no lo que escribió en el chat del curso ni sus notas de clase." />
            </div>
        @elseif ($telemetrias->isEmpty())
            <div class="surface rounded-panel">
                <x-estado-vacio
                    icono="fa-inbox"
                    titulo="No ha completado ninguna partida"
                    mensaje="«Código Anómalo» está en su menú de actividades. Cada partida terminada aparece aquí." />
            </div>
        @else
            <div class="space-y-3">
                {{-- La cifra de partidas es el dato destacado del módulo y lleva el
                         degradado; la fecha de la última es texto y no compite con
                         ella. Dos tarjetas con el mismo peso obligarían a leer las
                         dos para saber cuál es la que importa. --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="dato-tarjeta rounded-panel border p-5">
                        <p class="dato-rotulo mb-2">Partidas</p>
                        <p class="dato dato--acento">{{ $telemetrias->count() }}</p>
                    </div>
                    <div class="rounded-panel border border-white/10 p-5">
                        <p class="dato-rotulo mb-2">Última actividad</p>
                        <p class="text-[14px] font-semibold adaptive-title">
                            <time datetime="{{ $telemetrias->first()->created_at?->toDateString() }}">
                                {{ $telemetrias->first()->created_at?->translatedFormat('j \d\e F \d\e Y') }}
                            </time>
                        </p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-panel border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left">
                            <caption class="sr-only">Partidas jugadas</caption>
                            <thead class="border-b border-white/10 bg-white/5 text-[11px] uppercase tracking-[0.12em] text-gray-500">
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-semibold">Fecha</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Puntuación</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Duración</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Resultado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($telemetrias as $t)
                                    <tr>
                                        <td class="whitespace-nowrap px-5 py-3.5 text-[13px] text-gray-400">
                                            <time datetime="{{ $t->created_at?->toDateString() }}">{{ $t->created_at?->format('d/m/Y') }}</time>
                                        </td>
                                        <td class="px-5 py-3.5 text-[13px] font-medium tabular-nums text-gray-200">
                                            {{ $t->score_final ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3.5 text-[13px] tabular-nums text-gray-300">
                                            {{ $t->tiempo_total_ms ? round($t->tiempo_total_ms / 1000).' s' : '—' }}
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @if ($t->diagnostico_correcto !== null)
                                                <span @class([
                                                    'text-[12px] font-semibold',
                                                    'text-emerald-400' => $t->diagnostico_correcto,
                                                    'text-rose-400' => ! $t->diagnostico_correcto,
                                                ])>
                                                    {{ $t->diagnostico_correcto ? 'Acierto' : 'Fallo' }}
                                                </span>
                                            @else
                                                <span class="text-[13px] text-gray-600">Sin respuesta</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </section>
    </x-modulo>
    </x-modulos>

    <p class="mt-8 border-t border-white/5 pt-5 text-[12px] leading-relaxed text-gray-500">
        Esta ficha refleja el consentimiento vigente en el momento de abrirla. Si la persona
        estudiante lo retira, tu acceso termina de inmediato y así lo verás la próxima vez
        que entres.
    </p>

</div>
@endsection