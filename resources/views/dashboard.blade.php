@extends('layouts.app')

@section('title', 'Registro | S-Emotion')

@section('content')

{{-- La serie se calcula una vez, aquí arriba, y la reutilizan la gráfica y el
     estado vacío. Antes se pedía dos veces con la misma consulta.

     Vive fuera de los módulos a propósito: el script de la gráfica lo necesita y
     se ejecuta al final de la plantilla. --}}
@php
    $serie = auth()->user()->emociones()->latest()->take(7)->get()->reverse()->values();
@endphp

{{-- AVISO DE ACCIÓN --}}
@if (session('status'))
    {{-- Sin rebote. Un aviso que salta y desaparece es ruido; lo que importa es
         que se lea. --}}
    <div class="mb-6 flex items-start gap-3 rounded-panel border border-accent/30 bg-accent-soft px-4 py-3.5">
        <i class="fa-solid fa-circle-check mt-0.5 text-sm text-accent-text" aria-hidden="true"></i>
        <p class="flex-1 text-[13px] leading-relaxed text-gray-200">{{ session('status') }}</p>
        <button type="button" onclick="this.parentElement.remove()"
                aria-label="Cerrar el aviso"
                class="shrink-0 text-gray-500 transition-colors hover:text-white">
            <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
        </button>
    </div>
@endif

{{-- ─── Estado actual ───────────────────────────────────────────────────
     Las tres cifras que antes eran tres tarjetas iguales pasan a ser un solo
     panel con filetes. No es un adorno: son tres lecturas de un mismo hecho —
     cómo te sientes, con qué intensidad, y qué nivel de estrés sugiere — y
     presentarlas como tres cajas separadas hacía pensar que eran tres datos
     independientes que había que consultar uno por uno.

     Este panel NO va dentro de los módulos y queda siempre arriba. Es la
     respuesta a «¿cómo estoy ahora?», y esconderla tras una pestaña obligaría a
     volver atrás para releerla justo después de escribir un registro nuevo. --}}
<section class="surface dato-tarjeta mb-7 overflow-hidden rounded-panel">
    <div class="grid divide-y divide-white/5 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
        <div class="px-5 py-4">
            <p class="dato-rotulo mb-2">Cómo te sientes</p>
            {{-- Una palabra, no una cifra: por eso lleva la medida mediana. A
                 tamaño completo «Productivo» se salía de su celda en móvil. --}}
            <p class="dato dato--mediano {{ $ultimoRegistro ? 'dato--acento' : 'dato--vacio' }}">
                {{ $ultimoRegistro ? \Illuminate\Support\Str::ucfirst($ultimoRegistro->emocion) : 'Sin registros' }}
            </p>
        </div>

        <div class="px-5 py-4">
            <p class="dato-rotulo mb-2">Intensidad</p>
            <p class="dato {{ $ultimoRegistro ? 'dato--neutro' : 'dato--vacio' }}">
                {{ $ultimoRegistro ? $ultimoRegistro->energia.'%' : '—' }}
            </p>
        </div>

        <div class="px-5 py-4">
            <p class="dato-rotulo mb-2">Estrés estimado</p>
            <p class="dato {{ $ultimoRegistro ? 'dato--alerta' : 'dato--vacio' }}">
                {{ $ultimoRegistro ? $ultimoRegistro->nivel_estres_estimado.'%' : '—' }}
            </p>
        </div>
    </div>

    {{-- Estado calculado por el modelo. Solo aparece si hay un registro: antes
         mostraba «ESPERANDO SEÑAL» incluso sin datos, que confunde con que
         hubiera una lectura pendiente. --}}
    @if ($ultimoRegistro)
        {{-- Asignación en forma de bloque. La forma de una sola línea, con la
             expresión entre paréntesis justo detrás de la palabra, emite la
             apertura de PHP sin cerrarla y se traga el resto de la vista. --}}
        @php
            $estado = $ultimoRegistro->getStressStatus();
        @endphp

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 border-t border-white/5 bg-black/20 px-5 py-3">
            <span class="inline-flex items-center gap-2 text-[12px] font-semibold {{ $estado['color'] }}">
                <span class="{{ $estado['pulse'] }} h-1.5 w-1.5 rounded-pill bg-current" aria-hidden="true"></span>
                {{ $estado['label'] }}
            </span>
            <span class="text-[12px] text-gray-500">
                Última lectura el {{ $ultimoRegistro->created_at->translatedFormat('j \d\e F, H:i') }}
            </span>
        </div>
    @endif
</section>

{{-- ─── Módulos ──────────────────────────────────────────────────────────
     Antes esta pantalla era un scroll largo: el gráfico, la lectura del estado y
     un formulario de cuatro pasos, todo seguido. Lo que más se viene a hacer a
     esta pantalla es registrar; lo demás se consulta de vez en cuando. Separar
     las dos cosas es la diferencia entre llegar y escribir, o llegar y tener que
     pasar por todo lo de arriba para llegar al formulario.

     Se alternan con un clic y sin recargar, y también con el teclado: las flechas
     izquierda y derecha cambian de módulo. Un panel que se pliega sin animación
     es un detalle menor; quedarse sin forma de llegar al segundo módulo sin
     ratón ya no lo es. --}}
<x-modulos :modulos="[
    ['id' => 'registro', 'titulo' => 'Registrar', 'icono' => 'fa-plus'],
    ['id' => 'estado',   'titulo' => 'Estado',   'icono' => 'fa-chart-line'],
]">
    {{-- ─── Módulo · Registrar ────────────────────────────────────────── --}}
    <x-modulo id="registro" activo>
        <section class="surface rounded-panel p-5 sm:p-6 lg:p-7">
            <h2 class="mb-6 text-[15px] font-semibold adaptive-title">Registrar cómo me siento</h2>

            {{-- `data-envio-unico`: este envío recalcula el estrés estimado y
                 vuelve a pintar el panel de arriba. Sin aviso de espera, quien
                 tiene prisa pulsa dos veces y guarda dos registros del mismo
                 momento. --}}
            <form action="{{ route('emociones.store') }}" method="POST" class="space-y-8" data-envio-unico>
                @csrf

                {{-- 1 · Cómo te sientes.

                     El color de cada opción NO es decoración: es el mismo tono con el que
                     esa emoción aparecerá después en el historial. Así se reconoce de un
                     vistazo, sin tener que recordar qué significaba cada caja. --}}
                <fieldset>
                    <legend class="mb-3 text-[13px] font-semibold text-gray-300">
                        <span class="mr-1.5 font-mono text-[12px] text-accent-text">1</span>
                        ¿Cómo te sientes ahora?
                    </legend>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                        @php
                            /* Un solo lugar donde se decide el tono de cada emoción.
                               `historial.blade.php` usa la misma asignación; si divergen,
                               una emoción se ve de un color al registrarla y de otro al
                               consultarla.

                               Las clases van ESCRITAS ENTERAS, no parts de un nombre al
                               que se le pegue el tono. Tailwind lee el código fuente al
                               compilar y no ejecuta PHP: una clase montada por
                               interpolación no existe en el CSS y el estilo
                               sencillamente no se aplica.

                               `marcar` son el filete y el relleno del estado elegido;
                               `texto`, el color con el que se pinta ese estado. Se
                               declaran en el contenedor y no en el icono ni en la
                               etiqueta porque la variante de estado marcado solo alcanza
                               a hermanos, y esos dos son nietos del hermano. */
                            $opciones = [
                                'felicidad'  => [
                                    'icono' => 'fa-face-smile',
                                    'marcar' => 'peer-checked:border-accent peer-checked:bg-accent/10',
                                    'texto' => 'peer-checked:text-accent-text',
                                    'escala' => 'positivo',
                                ],
                                'entusiasta' => [
                                    'icono' => 'fa-rocket',
                                    'marcar' => 'peer-checked:border-accent peer-checked:bg-accent/10',
                                    'texto' => 'peer-checked:text-accent-text',
                                    'escala' => 'positivo',
                                ],
                                'productivo' => [
                                    'icono' => 'fa-code',
                                    'marcar' => 'peer-checked:border-accent peer-checked:bg-accent/10',
                                    'texto' => 'peer-checked:text-accent-text',
                                    'escala' => 'positivo',
                                ],
                                'relajado'   => [
                                    'icono' => 'fa-mug-hot',
                                    'marcar' => 'peer-checked:border-emerald-400 peer-checked:bg-emerald-400/10',
                                    'texto' => 'peer-checked:text-emerald-200',
                                    'escala' => 'neutral',
                                ],
                                'ansioso'    => [
                                    'icono' => 'fa-bolt',
                                    'marcar' => 'peer-checked:border-amber-400 peer-checked:bg-amber-400/10',
                                    'texto' => 'peer-checked:text-amber-200',
                                    'escala' => 'alerta',
                                ],
                                'tristeza'   => [
                                    'icono' => 'fa-face-sad-tear',
                                    'marcar' => 'peer-checked:border-blue-400 peer-checked:bg-blue-400/10',
                                    'texto' => 'peer-checked:text-blue-200',
                                    'escala' => 'baja',
                                ],
                                'melancolia' => [
                                    'icono' => 'fa-cloud-showers-water',
                                    'marcar' => 'peer-checked:border-purple-400 peer-checked:bg-purple-400/10',
                                    'texto' => 'peer-checked:text-purple-200',
                                    'escala' => 'baja',
                                ],
                                'agotado'    => [
                                    'icono' => 'fa-battery-empty',
                                    'marcar' => 'peer-checked:border-gray-400 peer-checked:bg-gray-400/10',
                                    'texto' => 'peer-checked:text-gray-200',
                                    'escala' => 'baja',
                                ],
                                'ira'        => [
                                    'icono' => 'fa-fire-flame-curved',
                                    'marcar' => 'peer-checked:border-neon-rose peer-checked:bg-neon-rose/10',
                                    'texto' => 'peer-checked:text-rose-200',
                                    'escala' => 'alerta',
                                ],
                            ];
                        @endphp

                        {{-- El color del estado marcado va en el CONTENEDOR, y el icono y
                             la etiqueta heredan con `text-current`.

                             No es una preferencia de estilo: la variante `peer-checked:`
                             de Tailwind compila a `:where(.peer):checked ~ *`, y el `*`
                             solo alcanza a HERMANOS. El icono y la etiqueta están dentro
                             del hermano, no son hermanos de él, así que ponerles el
                             color a ellos no hace nada: el estado marcado se veía en el
                             filete y en el fondo, pero el texto se quedaba gris. --}}
                        @foreach ($opciones as $valor => $opcion)
                            <label class="group cursor-pointer">
                                <input type="radio"
                                       name="emocion"
                                       value="{{ $valor }}"
                                       class="peer sr-only"
                                       data-escala="{{ $opcion['escala'] }}"
                                       @if ($loop->first) required @endif>
                                <span class="flex h-[4.5rem] flex-col items-center justify-center gap-1.5 rounded-control border border-white/10 bg-black/30 px-2 text-center transition-colors
                                             hover:border-white/25
                                             peer-focus-visible:border-accent peer-focus-visible:ring-1 peer-focus-visible:ring-accent
                                             {{ $opcion['marcar'] }}
                                             text-gray-400 group-hover:text-gray-200 {{ $opcion['texto'] }}">
                                    <i class="fa-solid {{ $opcion['icono'] }} text-[18px] opacity-70 transition-opacity group-hover:opacity-100"
                                       aria-hidden="true"></i>
                                    <span class="text-[13px] font-semibold">
                                        {{ \Illuminate\Support\Str::ucfirst($valor) }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- 2 · Intensidad y 3 · contexto --}}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="lg:col-span-2" data-intensidad>

                        {{-- Las lecturas de cada familia. Van aquí, y no en el guion,
                             porque es texto de la vista: son cuatro formas de
                             nombrar lo mismo, y quién las lea para saber qué poner en
                             la tabla es quien decide cómo se llama. El guion solo las
                             lee y las reparte.

                             Y los rótulos del estado inicial NO son los de ninguna
                             familia. El campo de emoción es obligatorio y va sin valor
                             por defecto a propósito —prefijar «felicidad» haría que
                             quien solo pulse enviar quedara registrado como feliz—, así
                             que al abrir la página no hay ninguna elegida y la escala
                             todavía no dice qué mide. Por eso aquí va el texto
                             genérico, el mismo que escribe `LECTURA_SIN_EMOCION` en
                             `resources/js/app.js`.

                             Poner aquí los rótulos de la primera emoción, como estaba
                             antes, hacía que la página cambiara de texto sola al cargar
                             el guion: el marcado decía una cosa y un instante después
                             el guion ponía otra. `IntensidadBarraTest` comprueba que
                             los dos textos coincidan, sobre el HTML que sale y no
                             comparando estas dos listas consigo mismas. --}}
                        <script type="application/json" data-lecturas-escala>
                            {
                                "alerta":   { "titulo": "Cuánta sobrecarga notas",           "min": "Sintomatología leve",   "max": "Descontrol total" },
                                "baja":     { "titulo": "Cuánta dificultad te cuesta",       "min": "Desgano pasajero",      "max": "Bloqueo afectivo" },
                                "positivo": { "titulo": "Cuánta energía y enfoque tienes",   "min": "Impulso sutil",         "max": "Plena fluidez" },
                                "neutral":  { "titulo": "Cuánta calma notas",               "min": "Calma inicial",         "max": "Relajación profunda" }
                            }
                        </script>

                        <div class="mb-2 flex items-baseline justify-between gap-3">
                            <label for="energy-input-slider" class="text-[13px] font-semibold text-gray-300">
                                <span class="mr-1.5 font-mono text-[12px] text-accent-text">2</span>
                                <span data-intensidad-titulo>Con qué intensidad</span>
                            </label>

                            {{-- La cifra y la palabra van juntas porque son la misma
                                 medida dicha de dos maneras: el número para quien ya
                                 sabe cuánto es, la palabra para quien prefiere leerla.
                                 Las dos se actualizan en el mismo `input`, en
                                 `resources/js/app.js`.

                                 Lo que hay escrito aquí es el estado para quien
                                 llega sin JavaScript, así que el `50`, la palabra
                                 «Media» y su tono tienen que ser los del valor 50:
                                 el 50 cae en el peldaño quinto, que es `medio` y no
                                 `bajo`. El guion lo corrige al conectar, pero hasta
                                 entonces se ve, y estaba en `bajo`. Lo vigila
                                 `test_sin_javascript_el_numero_y_la_palabra_del_marcado_no_se_contradicen`. --}}
                            <span class="flex items-baseline gap-2">
                                <span class="dato dato--mediano" data-intensidad-cifra>50%</span>
                                <span class="intensidad-nivel" data-intensidad-nivel data-nivel="bajo">Media</span>
                            </span>
                        </div>

                        {{-- El relleno de lo recorrido lo pinta `--avance`, que el
                             guion escribe en la barra en cada `input`; el margen del
                             anillo de foco, en `.intensidad:focus-visible`. Aquí solo
                             queda el control y su nombre. --}}
                        <input type="range"
                               name="energia"
                               id="energy-input-slider"
                               min="1"
                               max="100"
                               value="50"
                               class="intensidad campo h-1.5 w-full appearance-none rounded-pill border-0 bg-white/10 p-0">

                        {{-- Los extremos cambian de significado según la emoción elegida
                             («Ansioso» va de leve a descontrol, «Relajado» de calma a
                             relajación profunda). Es la misma escala con dos lecturas.

                             Sin ninguna elegida quedan en genérico, igual que el título. --}}
                        <div class="mt-2 flex justify-between text-[11px] text-gray-500">
                            <span data-intensidad-minimo>Intensidad mínima</span>
                            <span data-intensidad-maximo>Intensidad máxima</span>
                        </div>
                    </div>

                    <div>
                        <label for="contexto" class="mb-2 block text-[13px] font-semibold text-gray-300">
                            <span class="mr-1.5 font-mono text-[12px] text-accent-text">3</span>
                            En qué contexto
                        </label>
                        {{-- Un `<option>` no admite iconos: el navegador dibuja solo el
                             texto. Se cambian los emojis por palabras, que además es lo
                             que queda bien en todos los sistemas. Los `value` no se
                             tocan porque son los que valida el servidor. --}}
                        <select name="contexto"
                                id="contexto"
                                class="campo w-full cursor-pointer rounded-control border bg-black/40 px-3.5 py-2.5 text-[13px] text-gray-200 hover:border-white/25">
                            <option value="General">Ámbito general</option>
                            <option value="Exámenes">Periodo de exámenes</option>
                            <option value="Proyecto">Desarrollo de proyecto</option>
                            <option value="Clases">Horario de clases</option>
                        </select>
                    </div>
                </div>

                {{-- 4 · Nota y envío --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4 lg:items-end">
                    <div class="lg:col-span-3">
                        <label for="observaciones" class="mb-2 block text-[13px] font-semibold text-gray-300">
                            <span class="mr-1.5 font-mono text-[12px] text-accent-text">4</span>
                            ¿Qué notas hoy?
                        </label>
                        <input type="text"
                               name="observaciones"
                               id="observaciones"
                               maxlength="500"
                               placeholder="Una frase basta. Se analiza para ver patrones entre registros."
                               class="campo w-full rounded-control border bg-black/40 px-4 py-3 text-[14px] text-white placeholder:text-gray-600 hover:border-white/25">
                        <p class="mt-1.5 text-[11px] leading-relaxed text-gray-500">
                            Lo que escribas se analiza junto a tus registros. Solo tú y los profesionales
                            a los que hayas dado consentimiento pueden leerlo.
                        </p>
                    </div>

                    <div class="lg:col-span-1">
                        <x-boton class="w-full">
                            Guardar registro
                        </x-boton>
                    </div>
                </div>
            </form>
        </section>
    </x-modulo>

    {{-- ─── Módulo · Estado ─────────────────────────────────────────────
         La curva y la lectura van en el mismo módulo porque son dos formas de
         Contestar a la misma pregunta: una la muestra y la otra la explica.
         Separarlas obligaría a ir y volver para entender un número. --}}
    <x-modulo id="estado">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="surface rounded-panel p-5 sm:p-6 lg:col-span-2">
                <h2 class="mb-5 text-[15px] font-semibold adaptive-title">Tendencia de intensidad</h2>

                @if ($serie->isEmpty())
                    <x-estado-vacio
                        icono="fa-chart-line"
                        titulo="Todavía no hay una línea que dibujar"
                        mensaje="En cuanto registres cómo te sientes verás aquí tu intensidad de los últimos siete días." />
                @else
                    {{-- El lienzo se pinta al declarar visible este módulo, no al
                         cargar la página. Un `<canvas>` dentro de algo oculto mide
                         cero por cero, y Chart.js dibuja una gráfica vacía que no
                         se redibuja sola al mostrar el panel. --}}
                    <div class="h-56"><canvas id="emocionChart" aria-label="Intensidad de los últimos siete registros"></canvas></div>

                    {{-- Tabla equivalente al gráfico. Un canvas no se puede leer con
                         lector de pantalla ni es legible si la gráfica no carga, y esta
                         pantalla es el resumen de la actividad de la persona. --}}
                    <table class="sr-only">
                        <caption>Intensidad de los últimos registros</caption>
                        <thead><tr><th scope="col">Fecha</th><th scope="col">Intensidad</th></tr></thead>
                        <tbody>
                            @foreach ($serie as $registro)
                                <tr>
                                    <td>{{ $registro->created_at->translatedFormat('j \d\e F') }}</td>
                                    <td>{{ $registro->energia }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="surface flex flex-col rounded-panel p-5 sm:p-6">
                <h2 class="mb-3 text-[15px] font-semibold adaptive-title">Lectura de tu estado</h2>

                @if ($ultimoRegistro)
                    <p class="flex-1 text-[13px] leading-relaxed text-gray-300">
                        {{ $ultimoRegistro->recomendacion }}
                    </p>
                @else
                    {{-- Sin datos, este bloque decía «Sincroniza el sistema para iniciar el
                         análisis conductual», que suena a que el sistema está esperando algo
                         que el usuario no sabe qué es. --}}
                    <p class="flex-1 text-[13px] leading-relaxed text-gray-400">
                        Cuando registres cómo te has sentido, aparecerá aquí una lectura breve
                        sobre cómo combinar ese estado con tu energía.
                    </p>
                @endif

                <x-boton-secundario
                    class="mt-6 self-start"
                    icono="file-lines"
                    :href="route('emociones.reporte')">
                    Generar informe
                </x-boton-secundario>
            </section>
        </div>
    </x-modulo>
</x-modulos>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    /* El guion de la barra de intensidad —la palabra del nivel, el relleno de lo
       recorrido y los rótulos que cambian con la emoción— está en
       `resources/js/app.js`, con el resto de las interacciones. Aquí solo queda
       la gráfica, que es de esta pantalla y de ninguna otra. */

    document.addEventListener('DOMContentLoaded', function () {
        const lienzo = document.getElementById('emocionChart');

        /* Se pinta una sola vez. La segunda llamada —al abrir el módulo que la
           contiene— encuentra `grafica` ya hecha y no hace nada, de modo que
           pulsar la pestaña varias veces no apila gráficas encima una de otra. */
        let grafica = null;

        const pintar = () => {
            if (!lienzo || grafica) return;   // Estado vacío: no hay gráfica que pintar.
            if (typeof Chart === 'undefined') return;   // El CDN no respondió.
            if (lienzo.clientWidth === 0) return;   // El módulo sigue oculto: se dibuja al abrirlo.

            const raiz = getComputedStyle(document.documentElement);

            /* El color de la gráfica se LEE del tema en vez de escribirse como
               hexadecimal. Con los valores fijos, cambiar el color de acento en
               «Mi cuenta» dejaba la curva siempre en cian sobre un fondo que ya no
               era cian: era el único elemento de la pantalla que ignoraba el tema. */
            const acento = raiz.getPropertyValue('--neon-accent').trim() || '#22d3ee';
            const fondo   = raiz.getPropertyValue('--bg-primary').trim() || '#030712';
            const tenue   = raiz.getPropertyValue('--border-system').trim() || 'rgba(255,255,255,0.1)';

            // Los ejes necesitan un color que se lea sobre el fondo del tema, no el gris fijo.
            // La clase `light-mode` vive en <html>, no en <body>.
            const colorEje = document.documentElement.classList.contains('light-mode') ? '#64748b' : '#94a3b8';

            /* Chart.js pide un color con transparencia y los tokens son hex o rgb(a).
               Se normaliza aquí en lugar de inventar un cuarto color en el CSS. */
            const conOpacidad = (color, grado) => {
                const m = String(color).match(/rgba?\(([^)]+)\)/);
                if (m) {
                    const [r, g, b] = m[1].split(',').map(Number);
                    return `rgba(${r}, ${g}, ${b}, ${grado})`;
                }
                const h = String(color).replace('#', '');
                const n = h.length === 3 ? h.split('').map(c => c + c).join('') : h;
                const i = parseInt(n, 16);
                return `rgba(${(i >> 16) & 255}, ${(i >> 8) & 255}, ${i & 255}, ${grado})`;
            };

            const registros = @json($serie->map(fn ($r) => [
                $r->created_at->format('d/m'),
                $r->energia,
            ]));

            grafica = new Chart(lienzo.getContext('2d'), {
                type: 'line',
                data: {
                    labels: registros.map(r => r[0]),
                    datasets: [{
                        label: 'Intensidad',
                        data: registros.map(r => r[1]),
                        borderColor: acento,
                        backgroundColor: conOpacidad(acento, 0.08),
                        pointBackgroundColor: acento,
                        pointBorderColor: fondo,
                        borderWidth: 2,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: fondo,
                            borderColor: conOpacidad(acento, 0.4),
                            borderWidth: 1,
                            titleColor: colorEje,
                            bodyColor: colorEje,
                            displayColors: false,
                            callbacks: {
                                label: (ctx) => 'Intensidad: ' + ctx.parsed.y + '%',
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            border: { display: false },
                            grid: { color: tenue },
                            ticks: { color: colorEje, font: { size: 11 }, maxTicksLimit: 5 },
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { color: colorEje, font: { size: 11 } },
                        },
                    },
                },
            });
        };

        pintar();

        /* Si la gráfica no se pintó porque su módulo estaba oculto, espera a que
           se abra. `modulo:visible` lo emite `resources/js/app.js` al terminar
           la animación de entrada del panel. */
        document.querySelectorAll('[data-modulo]').forEach(function (modulo) {
            modulo.addEventListener('modulo:visible', pintar);
        });
    });
</script>
@endpush
