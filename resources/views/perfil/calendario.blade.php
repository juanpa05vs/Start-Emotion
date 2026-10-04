@extends('layouts.app')

@section('title', 'Calendario | S-Emotion')

@section('content')

<div class="mb-8">
    <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Calendario</h1>
    <p class="mt-1.5 text-[13px] text-gray-400">
        Cada día muestra tu energía media. Sirve para ver la tendencia del mes, no un día suelto.
    </p>
</div>

<div class="max-w-4xl">

    @php
        // ¿Hay algo que mostrar? Un calendario completamente vacío no es un
        // calendario: es una rejilla que no dice nada. Se comprueba antes de
        // pintar para poder explicar la pantalla.
        $hayDatos = collect($datosHeatmap ?? [])->filter(fn ($v) => $v !== null)->isNotEmpty();

        /* Los dos recuentos del mes salen de `$datosHeatmap`, que ya está
           cargado: no es una consulta nueva. Antes esta pantalla pedía «ver la
           tendencia del mes» y obligaba a mirar treinta y una casillas para
           contestarla. Con «19 días anotados» y «63% de media» la misma pregunta
           se responde de una. */
        $conRegistro = collect($datosHeatmap ?? [])->filter(fn ($v) => $v !== null);
        $mediaMes = $conRegistro->isNotEmpty() ? round($conRegistro->avg()) : null;
    @endphp

    @unless ($hayDatos)
        <div class="surface rounded-panel">
            <x-estado-vacio
                icono="fa-calendar-days"
                titulo="Este mes está vacío"
                mensaje="Todavía no hay registros en el calendario. En cuanto anotes cómo te has sentido, cada día se irá llenando con tu energía media."
                accion="Registrar cómo me siento"
                :url="route('dashboard')" />
        </div>
    @else
    <div class="surface rounded-panel p-6 sm:p-8 relative overflow-hidden">
        {{-- Lectura del mes, arriba de la rejilla y no debajo: es la conclusión y
             la rejilla es la evidencia. Al revés habría que recorrer el mes
             entero para averiguar lo que el resumen ya dice. --}}
        <div class="dato-tarjeta mb-7 grid grid-cols-1 gap-px overflow-hidden rounded-control border sm:grid-cols-2">
            <div class="px-4 py-3.5">
                <p class="dato-rotulo mb-2">Días anotados</p>
                <p class="dato dato--acento">
                    {{ $conRegistro->count() }}<span class="dato--mediano font-body text-gray-500">/{{ now()->daysInMonth }}</span>
                </p>
            </div>

            <div class="px-4 py-3.5">
                <p class="dato-rotulo mb-2">Energía media del mes</p>
                <p class="dato dato--neutro">{{ $mediaMes }}%</p>
            </div>
        </div>

        <p class="mb-6 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">
            {{ now()->translatedFormat('F Y') }}
        </p>

        {{-- Cuadrícula de días. Los rótulos van a 11px y en gris medio: a 9px
             con tracking amplio eran ilegibles, y son la única referencia para
             situarse en la rejilla. --}}
        <div class="grid grid-cols-7 gap-1.5 sm:gap-2.5 mb-2.5">
            @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaNombre)
                <div class="text-[11px] text-center font-semibold text-gray-500 pb-1">{{ $diaNombre }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-1.5 sm:gap-2.5">
            @foreach($rangoDias as $dia)
                @if($dia === null)
                    {{-- Celda de relleno: alinea el día 1 con su columna (Lun..Dom) --}}
                    <div class="aspect-square"></div>
                @else
                    @php
                        $fecha = $dia->format('Y-m-d');
                        $promedio = $datosHeatmap[$fecha] ?? null;

                        /* Escala de energía. Se distinguen por el TONO y por la
                           ETIQUETA de la leyenda, no solo por el color: quien no
                           distinga rojo de verde sigue viendo la tendencia. El
                           cian es el del sistema, así que el nivel bueno se ve
                           como parte de la aplicación.

                           El día sin datos usa `surface` y no `bg-white/5`: un
                           blanco al 5% es invisible sobre el fondo claro. Antes
                           lo tapaba una regla global que pintaba de blanco
                           CUALQUIER hijo de una rejilla y, de paso, le borraba
                           el color a las celdas que sí tenían datos. */
                        $colorClass = 'surface'; // Sin datos
                        $textoCelda = 'text-gray-500';
                        if ($promedio !== null) {
                            [$colorClass, $textoCelda] = match (true) {
                                $promedio >= 80 => ['bg-accent border-accent', 'text-accent-ink'],
                                $promedio >= 50 => ['bg-emerald-500/30 border-emerald-500/50', 'text-emerald-200'],
                                $promedio >= 25 => ['bg-amber-500/30 border-amber-500/50', 'text-amber-200'],
                                default         => ['bg-neon-rose/30 border-neon-rose/50', 'text-rose-200'],
                            };
                        }
                    @endphp

                    {{-- La comilla de `class` se cierra EN ESTA MISMA LÍNEA. Si el atributo
                         se parte en dos líneas, el `title` de abajo queda dentro
                         del valor de `class`: el navegador no lanza ningún error,
                         reparte el resto del marcado en atributos sueltos
                         (`jueves`, `15`, `de`…) y la celda se queda sin
                         información del día. El `border` no se pone aquí porque
                         `$colorClass` ya lo trae en sus cuatro variantes. --}}
                    <div class="group relative aspect-square rounded-control {{ $colorClass }} flex cursor-help items-center justify-center transition-colors"
                         title="{{ $dia->translatedFormat('l j \d\e F') }}: {{ $promedio !== null ? round($promedio).'% de energía' : 'sin registros' }}">

                        <span class="text-[12px] font-semibold {{ $textoCelda }}">
                            {{ $dia->format('d') }}
                        </span>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Leyenda. Antes solo mostraba dos de los cuatro niveles que el
             código pinta: el calendario podía tener días naranjas que no
             aparecían en ninguna parte de la leyenda. --}}
        <div class="mt-8 pt-6 border-t border-white/5">
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">
                Nivel de energía
            </p>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach ([
                    ['bg-accent',                  'Alto, 80% o más'],
                    ['bg-emerald-500/40',          'Bien, entre 50 y 79%'],
                    ['bg-amber-500/40',            'Bajo, entre 25 y 49%'],
                    ['bg-neon-rose/40',            'Muy bajo, menos de 25%'],
                    ['bg-white/10 border border-white/15', 'Sin registros'],
                ] as [$color, $etiqueta])
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-pill {{ $color }}" aria-hidden="true"></span>
                        <span class="text-[12px] text-gray-400">{{ $etiqueta }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endunless
</div>
@endsection
