@extends('layouts.app')

@section('title', 'Validación científica | S-Emotion')

@section('content')
<div class="space-y-7">

    {{-- ENCABEZADO --}}
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            {{-- «RANDOM FOREST» es el nombre del modelo, y es un dato: dice qué
                 se está evaluando. Va en Rajdhani, no en Orbitron de 10px, que a
                 ese tamaño pierde los contornos y solo se lee como una textura. --}}
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="rounded-pill border border-accent/30 bg-accent-soft px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-accent-text">
                    Modelo: Random Forest
                </span>
                <span class="rounded-pill border border-white/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                    {{ $totalMuestras }} muestras
                </span>
            </div>

            <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">
                <span class="text-accent-text">Validación</span> del modelo
            </h1>
            <p class="mt-1 max-w-2xl text-[13px] leading-relaxed text-gray-500">
                Con qué acierto clasifica el modelo cada emoción y dónde falla.
                La matriz contrasta lo que ocurrió con lo que el modelo previó.
            </p>
        </div>

        <x-boton-secundario :href="route('admin.validacion.exportar')" icono="file-csv">
            Exportar CSV
        </x-boton-secundario>
    </div>

    {{-- Los cuatro indicadores se leen como UN panel con filetes, no como
         cuatro tarjetas iguales. Son cuatro cifras del mismo modelo y muy
         distintas entre sí (un porcentaje, otro porcentaje, milisegundos y un
         recuento); hacerlas cajas separadas obligaba a compararlas de
         memoria. --}}
    {{-- `.dato-tarjeta`: es el bloque que se mira primero al entrar, así que
         lleva el degradado y el filete de acento. Los cuatro valores usan `.dato`
         y no una medida fija, porque «Latencia media» puede valer 4 ms o 400:
         con un tamaño constante, un valor de cuatro dígitos y otro de uno se
         leen igual de rápido, que es justo lo que no debe pasar en un panel de
         métricas. --}}
    <section class="dato-tarjeta overflow-hidden rounded-panel border">
        <div class="grid divide-y divide-white/5 sm:grid-cols-2 sm:divide-x lg:grid-cols-4 lg:divide-y-0">

            <div class="px-5 py-4">
                <p class="dato-rotulo mb-2">Exactitud</p>
                <p class="dato {{ $accuracy >= 80 ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ $accuracy }}%
                </p>
                <p class="mt-1 text-[11px] text-gray-500">
                    {{ $aciertosTotales }} de {{ $totalMuestras }} clasificados bien
                    @isset($muestrasIncluidasMatriz)
                        <span class="block">en la matriz: {{ $muestrasIncluidasMatriz }}</span>
                    @endisset
                </p>
            </div>

            <div class="px-5 py-4">
                <p class="dato-rotulo mb-2">F1 medio</p>
                <p class="dato dato--acento">{{ $macroF1 }}%</p>
                <p class="mt-1 text-[11px] text-gray-500">
                    Media armónica de precisión y exhaustividad
                </p>
            </div>

            <div class="px-5 py-4">
                <p class="dato-rotulo mb-2">Latencia media</p>
                <p class="dato dato--acento">
                    {{ $latenciaMedia }}
                    <span class="dato--mediano font-body font-medium text-gray-500">ms</span>
                </p>
                <p class="mt-1 text-[11px] text-gray-500">
                    Tiempo entre el estímulo y la respuesta
                </p>
            </div>

            <div class="px-5 py-4">
                <p class="dato-rotulo mb-2">Muestras</p>
                <p class="dato dato--neutro">{{ $totalMuestras }}</p>
                <p class="mt-1 text-[11px] text-gray-500">
                    @if ($muestrasDescartadas > 0)
                        {{ $muestrasDescartadas }} descartadas por etiqueta fuera de taxonomía
                    @else
                        Ninguna descartada
                    @endif
                </p>
            </div>

        </div>
    </section>
    </div>

    {{-- MATRIZ DE CONFUSIÓN --}}
    <section class="surface rounded-panel p-5 sm:p-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-white/5 pb-4">
            <div>
                <h2 class="text-[15px] font-semibold adaptive-title">Matriz de confusión</h2>
                <p class="mt-1 text-[13px] leading-relaxed text-gray-500">
                    Cada fila es lo que ocultaba el código y cada columna lo que decidió el modelo.
                    En la diagonal están los aciertos; fuera, los errores.
                </p>
            </div>

            {{-- Se cuentan los aciertos aquí, y no con un rótulo suelto: sin
                 el total, una matriz no se puede leer de un vistazo. --}}
            <span class="shrink-0 rounded-control border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-semibold tabular-nums text-gray-400">
                {{ $aciertosTotales }} de {{ $totalMuestras }} en la diagonal
            </span>
        </div>

        <div class="overflow-x-auto">
            @if($muestrasIncluidasMatriz === 0)
                {{-- Con cero muestras una matriz de ceros no dice nada útil: se explica qué falta. --}}
                <div class="rounded-panel border border-dashed border-accent/25 bg-accent/[0.03] px-6 py-10 text-center">
                    <p class="text-[15px] font-semibold adaptive-title">Sin datos para evaluar el modelo</p>
                    <p class="mx-auto mt-3 max-w-xl text-[13px] leading-relaxed text-gray-400">
                        Este panel se alimenta de <strong>las partidas del juego «Código Anómalo»</strong>.
                        Cada partida guarda cómo se resolvió —si se encontró el fallo, cuánto se tardó,
                        cuántas veces hubo que corregir— y el modelo compara esa lectura con el
                        problema real que el código ocultaba.
                    </p>
                    <p class="mx-auto mt-3 max-w-xl text-[13px] leading-relaxed text-gray-500">
                        No hay ninguna muestra todavía porque no se ha completado ninguna partida.
                    </p>

                    {{-- El botón de "iniciar una partida" solo se ofrece a quien puede
                         jugar. El administrador no usa el minijuego, y enlazar a
                         una pantalla que le devuelve un 403 sería dejar un enlace
                         roto justo en la pantalla de estado vacío. --}}
                    @if (auth()->user()?->esEstudiante())
                        <x-boton :href="route('minijuegos.diagnostico')" class="mt-6" icono="play">
                            Iniciar una partida
                        </x-boton>
                    @else
                        <p class="mx-auto mt-6 max-w-xl text-[13px] leading-relaxed text-gray-500">
                            Las muestras las generan quienes juegan la actividad. En cuanto haya
                            partidas completadas, las métricas aparecerán aquí.
                        </p>
                    @endif

                    <p class="mt-4 text-[11px] leading-relaxed text-gray-500">
                        Se necesitan varias sesiones por clase para que las métricas sean interpretables.
                    </p>
                </div>
            @else
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th scope="col" class="p-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">
                                Real \ Predicho
                            </th>
                            @foreach($clases as $c)
                                <th scope="col" class="p-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-accent-text">
                                    {{ $c }}
                                </th>
                            @endforeach
                            <th scope="col" class="p-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">
                                Recall
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($clases as $real)
                            <tr class="transition-colors hover:bg-white/[0.02]">
                                <th scope="row" class="p-3 text-left text-[12px] font-semibold text-gray-300">
                                    {{ $real }}
                                </th>
                                @foreach($clases as $pred)
                                    @php
                                        $val = $matrizConfusion[$real][$pred] ?? 0;
                                        $isDiagonal = ($real === $pred);
                                        $bg = '';
                                        if ($isDiagonal && $val > 0) {
                                            $bg = 'bg-emerald-500/15 text-emerald-300 font-semibold border border-emerald-500/30';
                                        } elseif (!$isDiagonal && $val > 0) {
                                            $bg = 'bg-rose-500/15 text-rose-300 font-semibold border border-rose-500/30';
                                        } else {
                                            $bg = 'text-gray-600';
                                        }
                                    @endphp
                                    <td class="p-3">
                                        <span class="inline-block px-3 py-1.5 rounded-control {{ $bg }} min-w-[35px]">
                                            {{ $val }}
                                        </span>
                                    </td>
                                @endforeach
                                {{-- El recall va en cifras tabulares: es una columna
                                     de números y tiene que alinearse para poder
                                     comparar las filas de un vistazo. --}}
                                <td class="p-3 text-right text-[12px] font-semibold tabular-nums text-emerald-400">
                                    {{ $metricasPorClase[$real]['recall'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- La nota metodológica va fuera de la tabla y en prosa: es la
                         explicación de por qué las filas no cuadran, y escondida
                         debajo de la rejilla con tipografía de consola obligaba a
                         quien quisiera entenderla a ir buscándola. --}}
                <div class="mt-5 border-t border-white/5 pt-4 text-[13px] leading-relaxed text-gray-500">
                    <p>
                        <strong class="font-semibold text-gray-400">Cómo leerlo:</strong>
                        las 8 emociones del juego se colapsan a las {{ count($clases) }} clases que
                        el modelo puede emitir
                        ({{ collect($mapaColapso)->map(fn ($v, $k) => "$k → $v")->join(', ') }}),
                        así que varias filas comparten clase. La matriz se construye sobre el
                        espacio de salida del clasificador, no sobre el vocabulario del juego.
                    </p>
                    @if($muestrasDescartadas > 0)
                        <p class="mt-2 text-amber-400">
                            {{ $muestrasDescartadas }} muestra(s) descartada(s) por etiqueta fuera de
                            taxonomía, excluidas del cálculo:
                            @foreach($detalleDescartadas as $d){{ $d['real'] }}→{{ $d['pred'] }} @endforeach
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- Los dos paneles de abajo son cifras de cobertura: qué volumen hay
         detrás de cada cifra de arriba. --}}
    <div class="grid gap-5 lg:grid-cols-2">

        {{-- DISTRIBUCIÓN DE EVALUACIONES SISCO --}}
        <section class="surface rounded-panel p-5">
            <div class="mb-4 flex items-center gap-2.5 border-b border-white/5 pb-3.5">
                <i class="fa-solid fa-chart-simple text-[12px] text-accent-text" aria-hidden="true"></i>
                <h2 class="text-[15px] font-semibold adaptive-title">Distribución del estrés</h2>
            </div>

            {{-- Agregado, no listado. Antes esta tarjeta mostraba las últimas
                 evaluaciones una por una con participante, estado afectivo y
                 nivel de estrés: historial clínico individual que el
                 administrador no tiene por qué ver. --}}
            <div class="space-y-2">
                @php
                    $etiquetasEstres = [
                        'bajo' => 'Bajo',
                        'moderado' => 'Moderado',
                        'severo' => 'Severo',
                    ];
                    // Una barra por nivel, con la misma forma que un medidor: se
                    // comparan longitudes, no recuentos sueltos.
                    $barraEstres = [
                        'bajo' => 'bg-emerald-400',
                        'moderado' => 'bg-amber-400',
                        'severo' => 'bg-rose-400',
                    ];
                @endphp

                @if ($totalEvaluaciones === 0)
                    <p class="py-6 text-center text-[13px] leading-relaxed text-gray-500">
                        Todavía no se ha hecho ninguna evaluación SISCO, así que no hay
                        distribución que mostrar.
                    </p>
                @else
                    @foreach ($etiquetasEstres as $clave => $etiqueta)
                        @php
                            $conteo = $distribucionEvaluaciones[$clave] ?? 0;
                            $porcentaje = round(($conteo / $totalEvaluaciones) * 100);
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="w-24 shrink-0 text-[12px] font-medium text-gray-400">
                                {{ $etiqueta }}
                            </span>
                            <div class="h-2 flex-1 overflow-hidden rounded-pill bg-white/5">
                                <div class="h-full rounded-pill {{ $barraEstres[$clave] }}"
                                     style="width: {{ $porcentaje }}%"></div>
                            </div>
                            <span class="w-14 shrink-0 text-right text-[12px] tabular-nums text-gray-400">
                                {{ $conteo }} · {{ $porcentaje }}%
                            </span>
                        </div>
                    @endforeach

                    <p class="pt-2 text-[12px] leading-relaxed text-gray-500">
                        {{ $totalEvaluaciones }} evaluaciones en total. Solo se muestra el recuento por
                        nivel: el detalle individual requiere un consentimiento vigente y se consulta
                        en el espacio del profesional.
                    </p>
                @endif
            </div>
        </section>

        {{-- COBERTURA DE LA MUESTRA --}}
        <section class="surface rounded-panel p-5">
            <div class="mb-4 flex items-center gap-2.5 border-b border-white/5 pb-3.5">
                <i class="fa-solid fa-users text-[12px] text-accent-text" aria-hidden="true"></i>
                <h2 class="text-[15px] font-semibold adaptive-title">Cobertura de la muestra</h2>
            </div>

            <dl class="divide-y divide-white/5">
                @foreach ([
                    'Sesiones registradas' => $totalMuestras,
                    'Personas distintas' => $participantesTelemetria,
                    'Evaluaciones SISCO' => $totalEvaluaciones,
                ] as $etiqueta => $cifra)
                    <div class="flex items-baseline justify-between gap-4 py-2.5">
                        <dt class="text-[13px] text-gray-400">{{ $etiqueta }}</dt>
                        <dd class="dato dato--mediano dato--neutro">{{ $cifra }}</dd>
                    </div>
                @endforeach
            </dl>

            <p class="pt-3 text-[12px] leading-relaxed text-gray-500">
                Cifras agregadas de toda la población. No se listan participantes ni sus
                resultados: eso requiere consentimiento vigente y vive en el espacio
                del profesional de psicología.
            </p>
        </section>

    </div>

</div>
@endsection
