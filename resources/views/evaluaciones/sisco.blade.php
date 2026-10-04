@extends('layouts.app')

@section('title', 'Encuesta de estrés | S-Emotion')

@section('content')
<div class="max-w-5xl">

    {{--
        La escala se llama SISCO, que es el nombre del instrumento, pero la
        pantalla se titula por lo que hace. Antes se llamaba «Calibración de
        Estrés Académico» y llevaba dos etiquetas —«GOLD STANDARD» y
        «BAREMACIÓN PSICOMÉTRICA»— que no significan nada para quien contesta y
        sí para quien se loenioría leyendo: el mensaje que transmite es que el
        cuestionario es serio, y eso se consigue con preguntas claras, no con
        dos términos en inglés.
    --}}
    <div class="mb-6 border-b border-white/5 pb-5">
        <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Encuesta de estrés</h1>
        <p class="mt-1.5 max-w-2xl text-[13px] leading-relaxed text-gray-400">
            Diez afirmaciones sobre cómo has vivido las últimas semanas. No hay respuestas
            buenas ni malas: sirve para ver cómo estás ahora, no para compararte con nadie.
        </p>
    </div>

    {{-- Resultado de la última vez. Solo aparece si ya se respondió alguna. --}}
    @if (! empty($ultimaEvaluacion))
        <div class="dato-tarjeta mb-7 flex flex-wrap items-center gap-x-8 gap-y-4 rounded-panel border px-5 py-4">
            <div>
                <p class="dato-rotulo mb-1.5">Tu última encuesta</p>
                <p class="mt-1 text-[14px] font-semibold adaptive-title">
                    <time datetime="{{ $ultimaEvaluacion->created_at?->toDateString() }}">
                        {{ $ultimaEvaluacion->created_at?->translatedFormat('j \d\e F \d\e Y') }}
                    </time>
                </p>
            </div>

            <div class="hidden h-9 w-px bg-white/10 sm:block" aria-hidden="true"></div>

            <div>
                <p class="dato-rotulo mb-1.5">Nivel de estrés</p>
                <p @class([
                    'mt-1 text-[14px] font-semibold adaptive-title',
                    'text-rose-400' => $ultimaEvaluacion->nivel_estres === 'severo',
                    'text-amber-400' => $ultimaEvaluacion->nivel_estres === 'moderado',
                    'text-emerald-400' => $ultimaEvaluacion->nivel_estres === 'bajo',
                ])>
                    {{ $ultimaEvaluacion->nivelEstresEtiqueta() }}
                </p>
            </div>

            <div class="hidden h-9 w-px bg-white/10 sm:block" aria-hidden="true"></div>

            <div>
                <p class="dato-rotulo mb-1.5">Lo que más pesó</p>
                <p class="mt-1 text-[14px] font-semibold adaptive-title">
                    {{ $ultimaEvaluacion->estadoAfectivoEtiqueta() ?? '—' }}
                </p>
            </div>

            <div class="hidden h-9 w-px bg-white/10 sm:block" aria-hidden="true"></div>

            <div>
                <p class="dato-rotulo mb-1.5">Índice global</p>
                <p class="dato dato--mediano">
                    {{ $ultimaEvaluacion->puntaje_global }}<span class="dato--mediano font-body text-gray-500">/100</span>
                </p>
            </div>
        </div>
    @endif

    {{--
        Guía de la escala. Se lee como una frase, no como una tabla: los números
        van en Orbitron porque son un dato, y las palabras en la tipografía del
        cuerpo porque es lo que hay que entender. Antes era una fila de texto en
        mayúsculas con corchetes alrededor del título.
    --}}
    <div class="mb-8 rounded-panel border border-white/5 bg-white/[0.02] px-5 py-3.5">
        <p class="text-[13px] leading-relaxed text-gray-400">
            <span class="font-semibold adaptive-title">Cómo responder:</span>
            cada afirmación se valora del 1 al 5, de
            <span class="font-orbitron text-accent-text">1</span> (nunca)
            a <span class="font-orbitron text-accent-text">5</span> (siempre).
        </p>
    </div>

    {{-- `data-envio-unico`: son diez preguntas y el guardado recalcula el
         índice global. Sin aviso de espera, quien tiene prisa contesta y pulsa
         otra vez creyendo que no se ha guardado. --}}
    <form action="{{ route('psicometria.store') }}" method="POST" data-envio-unico>
        @csrf

        @php
            $escala = [
                1 => 'Nunca',
                2 => 'Rara vez',
                3 => 'Algunas veces',
                4 => 'Casi siempre',
                5 => 'Siempre',
            ];

            $bloques = [
                [
                    'numero' => '1',
                    'titulo' => 'Lo que te pone bajo presión',
                    'ayuda' => 'Situaciones del entorno académico que te generan tensión.',
                    'tono' => 'accent',
                    'items' => [
                        ['name' => 'e_sobrecarga', 'label' => 'Sobrecarga de tareas, reportes y trabajos escolares.'],
                        ['name' => 'e_evaluaciones', 'label' => 'Evaluaciones, exámenes parciales y entregas finales.'],
                        ['name' => 'e_tiempo', 'label' => 'Tiempo limitado para realizar las actividades académicas.'],
                        ['name' => 'e_profesores', 'label' => 'Nivel de exigencia o metodología de los docentes.'],
                    ],
                ],
                [
                    'numero' => '2',
                    'titulo' => 'Lo que notas en el cuerpo y en el ánimo',
                    'ayuda' => 'Reacciones físicas y emocionales que has observado cuando aparece esa presión.',
                    'tono' => 'rose',
                    'items' => [
                        ['name' => 's_fatiga', 'label' => 'Fatiga crónica, cansancio excesivo, dolores de cabeza o tensión muscular.'],
                        ['name' => 's_ansiedad', 'label' => 'Inquietud, palpitaciones, sensación de prisa constante o angustia.'],
                        ['name' => 's_concentracion', 'label' => 'Problemas de concentración, olvidos frecuentes o bloqueo mental.'],
                        ['name' => 's_frustracion', 'label' => 'Sentimientos de irritabilidad, impotencia, frustración o desgano.'],
                    ],
                ],
                [
                    'numero' => '3',
                    'titulo' => 'Lo que haces para manejarlo',
                    'ayuda' => 'Qué te ha servido hasta ahora para bajar esa carga.',
                    'tono' => 'accent',
                    'items' => [
                        ['name' => 'a_resolucion', 'label' => 'Planificar, priorizar y resolver problemas paso a paso.'],
                        ['name' => 'a_comunicacion', 'label' => 'Pedir apoyo, hablar con alguien o acudir a tutoría.'],
                    ],
                ],
            ];
        @endphp

        <div class="space-y-6">
            @foreach ($bloques as $bloque)
                <section class="rounded-panel border border-white/10 p-5 sm:p-6">
                    <div class="mb-5 flex items-start gap-3.5 border-b border-white/5 pb-4">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-control border font-orbitron text-[12px] font-bold',
                            'border-accent/30 bg-accent/10 text-accent-text' => $bloque['tono'] === 'accent',
                            'border-rose-500/30 bg-rose-500/10 text-rose-400' => $bloque['tono'] === 'rose',
                        ])>
                            {{ $bloque['numero'] }}
                        </span>
                        <div>
                            <h2 class="text-[15px] font-semibold adaptive-title">{{ $bloque['titulo'] }}</h2>
                            <p class="mt-0.5 text-[12px] leading-relaxed text-gray-500">{{ $bloque['ayuda'] }}</p>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($bloque['items'] as $item)
                            {{-- Estas filas NO llevan `.tarjeta`. Se mueven al pasar el ratón, pero no se
                                     levantan: son treinta y dos filas seguidas, y
                                     una columna de tarjetas elevándose cada vez que
                                     el ratón pasa de arriba abajo convierte la
                                     encuesta en una superficie difícil de leer de
                                     un tirón. El color del filete y el estado
                                     marcado bastan. --}}
                            <div class="flex flex-col gap-3.5 rounded-control border border-white/5 bg-white/[0.02] p-4 transition-colors hover:border-white/15 md:flex-row md:items-center md:justify-between">
                                <p class="text-[13px] leading-relaxed text-gray-200 md:max-w-xl">
                                    {{ $item['label'] }}
                                </p>

                                {{-- `escala-likert` aporta el aspecto y el estado
                                     marcado, y el color sale de los tokens del
                                     tema: nada de esta fila está escrito dos
                                     veces. --}}
                                <div class="escala-likert flex shrink-0 items-center gap-1.5">
                                    @foreach ($escala as $valor => $palabra)
                                        <input type="radio"
                                               id="{{ $item['name'] }}_{{ $valor }}"
                                               name="{{ $item['name'] }}"
                                               value="{{ $valor }}"
                                               class="sr-only"
                                               required>
                                        <label for="{{ $item['name'] }}_{{ $valor }}">
                                            <span aria-hidden="true">{{ $valor }}</span>
                                            <span class="sr-only">{{ $valor }} · {{ $palabra }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
            <p class="max-w-sm text-[12px] leading-relaxed text-gray-500">
                La respuesta se guarda junto a tus registros y solo es visible para ti y para
                el profesional de psicología que tú autorices.
            </p>

            <x-boton class="w-full shrink-0 sm:w-auto" icono="check">
                Guardar mi respuesta
            </x-boton>
        </div>
    </form>
</div>
@endsection