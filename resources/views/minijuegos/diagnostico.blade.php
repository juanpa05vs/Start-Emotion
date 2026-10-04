@extends('layouts.app')

@section('title', 'Código Anómalo | S-Emotion')

@push('styles')
<style>
    /* Sacudida de la pantalla al fallar una respuesta.

       El efecto se queda: es el que da la sensación de «pulsar algo y que no
       funcione». Lo que se ha ido es el decorado de terminal que lo rodeaba:
       rótulos con barras oblicuas, y la palabra «interferencia». */
    @keyframes glitch-shake {
        0% { transform: translate(0) skew(0deg); }
        20% { transform: translate(-6px, 4px) skew(-3deg); }
        40% { transform: translate(6px, -4px) skew(3deg); filter: contrast(150%) hue-rotate(90deg); }
        60% { transform: translate(-3px, -2px) skew(1deg); }
        80% { transform: translate(4px, 3px) skew(-2deg); filter: invert(10%); }
        100% { transform: translate(0) skew(0deg); }
    }

    .error-tablero {
        animation: glitch-shake 0.35s linear 1;
        box-shadow: 0 0 40px rgba(244, 63, 94, 0.4) !important;
        border-color: var(--color-neon-rose) !important;
    }

    /* Un solo latigazo, no un bucle. Con `infinite` el error seguía temblando
       mientras el jugador leía por qué había fallado, que es justo cuando
       hace falta leer el mensaje. */
    .destello-error {
        position: fixed;
        inset: 0;
        background: radial-gradient(circle, rgba(244, 63, 94, 0.15) 0%, rgba(0, 0, 0, 0) 80%);
        mix-blend-mode: difference;
        z-index: 9990;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.05s ease;
    }

    .destello-error.activo { opacity: 1; }
</style>
@endpush

@section('content')
<div id="destello-overlay" class="destello-error"></div>

{{-- Sin `p-6`: el `<main>` de la maqueta ya pone el relleno. --}}
<div class="max-w-6xl select-none">

    <div class="mb-6 flex flex-col gap-5 border-b border-white/5 pb-5 md:flex-row md:items-end md:justify-between">
        <div>
            <span class="mb-2.5 inline-flex w-fit items-center gap-1.5 rounded-pill border border-accent/30 bg-accent-soft px-2.5 py-1 text-[11px] font-semibold text-accent-text">
                <span class="h-1.5 w-1.5 rounded-pill bg-accent" aria-hidden="true"></span>
                Actividad asignada
            </span>

            <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Código Anómalo</h1>
            <p class="mt-1.5 max-w-2xl text-[13px] leading-relaxed text-gray-400">
                Hay una emoción oculta detrás de tres pistas: lo que notas en el cuerpo,
                lo que la provocan y lo que te dices a ti mismo. Descarta las que no
                encajen y acierta con la que queda.
            </p>
        </div>

        {{-- Las dos cifras de arriba. Su color lo pone `data-nivel`, no el
             JavaScript: ver `.dato-estado` en `resources/css/app.css`. --}}
        <div class="dato-tarjeta flex gap-4 rounded-control border p-1.5">
            <div class="min-w-[104px] rounded-control px-4 py-2.5 text-center">
                <p class="dato-rotulo mb-1.5">
                    Puntos
                </p>
                <p id="hud-score" class="dato-estado" data-nivel="estable" aria-live="off">100</p>
            </div>

            <div class="min-w-[104px] rounded-control px-4 py-2.5 text-center">
                <p class="dato-rotulo mb-1.5">
                    Tiempo
                </p>
                <p id="hud-temp" class="dato-estado" data-nivel="estable">60 s</p>
            </div>
        </div>
    </div>

    {{-- Cuenta atrás. La barra se vacía, así que el ancho es el porcentaje de
         tiempo que queda, no el consumido. --}}
    <div class="mb-6 h-1.5 overflow-hidden rounded-pill border border-white/5 bg-white/5">
        <div id="temp-bar"
             class="h-full rounded-pill bg-gradient-to-r from-rose-500 via-amber-500 to-accent"
             style="width: 100%"></div>
    </div>

    <div id="master-game-panel" class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">

        {{-- ── LAS TRES PISTAS ─────────────────────────────────────────── --}}
        <div class="space-y-4 lg:sticky lg:top-24">
            <div class="surface rounded-panel p-5">
                <h2 class="mb-4 flex items-center gap-2 border-b border-white/5 pb-3.5 text-[15px] font-semibold adaptive-title">
                    <i class="fa-solid fa-magnifying-glass text-[12px] text-accent-text" aria-hidden="true"></i>
                    Pistas
                </h2>

                <div class="space-y-3">
                    <div class="rounded-control border border-white/5 bg-white/5 p-3.5">
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-accent-text">
                            1 · Lo que notas en el cuerpo
                        </p>
                        <p id="clue-sintomas" class="text-[13px] leading-relaxed text-gray-300">Extrayendo datos corporales…</p>
                    </div>

                    <div id="wrapper-detonante" class="rounded-control border border-white/5 bg-white/5 p-3.5 opacity-25 transition-opacity duration-500">
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-accent-text">
                            2 · Lo que lo provocando
                        </p>
                        <p id="clue-detonante" class="text-[13px] leading-relaxed text-gray-500">
                            Todavía no has pedido esta pista.
                        </p>
                    </div>

                    <div id="wrapper-cognitivo" class="rounded-control border border-white/5 bg-white/5 p-3.5 opacity-25 transition-opacity duration-500">
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-accent-text">
                            3 · Lo que te dices
                        </p>
                        <p id="clue-cognitivo" class="text-[13px] leading-relaxed text-gray-500">
                            Todavía no has pedido esta pista.
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex gap-2">
                    <x-boton-secundario type="button" id="btn-next-clue" class="flex-1" onclick="revealNextClue()">
                        Pedir otra pista
                    </x-boton-secundario>

                    <x-boton tipo="button" id="btn-verdict" onclick="verifyDiagnosis()" class="flex-1">
                        Confirmar
                    </x-boton>
                </div>
            </div>

            {{-- Lo que va contando la actividad. `role="status"` porque lo que
                 ocurre aquí es el resultado de la última acción, no un rótulo
                 fijo: con lector de pantalla hay que oírlo sin tener que
                 buscarlo. --}}
            <p id="system-feedback" class="aviso-estado" data-estado="info" role="status">
                Empieza por lo que notas en el cuerpo. Marca las emociones que no encajen
                para apartarlas.
            </p>
        </div>

        {{-- ── EL TABLERO ───────────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <h2 class="sr-only">Ocho emociones posibles</h2>

            <div id="emotion-matrix"
                 class="grid grid-cols-2 gap-3 [perspective:1000px] sm:grid-cols-3 md:grid-cols-4"></div>

            <div class="mt-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <p id="telemetry-live-indicator" class="linea-estado" data-estado="esperando" role="status">
                    Se guardará tu respuesta y lo que tardas en decidir.
                </p>

                <x-boton-neutro type="button" class="shrink-0" tamano="pequeno" icono="arrows-rotate" onclick="initGame()">
                    Empezar de nuevo
                </x-boton-neutro>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    /*
    |--------------------------------------------------------------------------
    | Efectos de sonido
    |--------------------------------------------------------------------------
    */
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

    function playSound(type) {
        if (audioCtx.state === 'suspended') audioCtx.resume();

        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);

        if (type === 'click') {
            osc.type = 'sine'; osc.frequency.setValueAtTime(600, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.05, audioCtx.currentTime); gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.05);
            osc.start(); osc.stop(audioCtx.currentTime + 0.05);
        } else if (type === 'flip') {
            osc.type = 'triangle'; osc.frequency.setValueAtTime(150, audioCtx.currentTime); osc.frequency.exponentialRampToValueAtTime(40, audioCtx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime); gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
            osc.start(); osc.stop(audioCtx.currentTime + 0.15);
        } else if (type === 'success') {
            osc.type = 'sine'; osc.frequency.setValueAtTime(523.25, audioCtx.currentTime);
            osc.frequency.setValueAtTime(659.25, audioCtx.currentTime + 0.1);
            osc.frequency.setValueAtTime(783.99, audioCtx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.08, audioCtx.currentTime); gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
            osc.start(); osc.stop(audioCtx.currentTime + 0.4);
        } else if (type === 'error') {
            osc.type = 'sawtooth'; osc.frequency.setValueAtTime(100, audioCtx.currentTime); osc.frequency.linearRampToValueAtTime(60, audioCtx.currentTime + 0.3);
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime); gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
            osc.start(); osc.stop(audioCtx.currentTime + 0.3);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Banco de casos
    |
    | Ocho emociones, cada una con tres pistas. Las pistas son de la emoción, no
    | del sistema: el jugador no ve «telemetría» ni «variables», ve lo que le
    | pasa a alguien en esa situación. El `id` lo usa el endpoint de telemetría
    | para casar la partida con EmocionEnum, así que no se toca.
    |--------------------------------------------------------------------------
    */
    const bancoEmociones = [
        { id: 'ansiedad', nombre: 'Ansiedad', icono: 'fa-bolt-lightning', sintomas: 'Palpitaciones aceleradas, respiración entrecortada y manos frías.', detonante: 'Faltan dos horas para la entrega final y todo lo que te queda por hacer se te viene encima de golpe.', cognitivo: '"Va a salir mal, voy a suspender y con esto arrastro el resto del curso".' },
        { id: 'ira', nombre: 'Ira', icono: 'fa-fire-flame-curved', sintomas: 'Mandíbula tensa, calor súbito en la cara y el pulso acelerado.', detonante: 'Un compañero del equipo borra el archivo que habíais subido y desaparece sin decir nada.', cognitivo: '"No respeto nada. Siempre hay que arreglar lo que hacen los demás".' },
        { id: 'frustracion', nombre: 'Frustración', icono: 'fa-triangle-exclamation', sintomas: 'Hombros rígidos, suspiros seguidos y la sensación de ir contra algo.', detonante: 'Llevas seis horas con un error que no sabes de dónde viene, y cada intento lo hace peor.', cognitivo: '"Por más que lo intento no avanzo. Quizá esto no sea lo mío".' },
        { id: 'melancolia', nombre: 'Melancolía', icono: 'fa-cloud-showers-water', sintomas: 'Sin ganas de moverte, mirada fija y un peso leve en el pecho.', detonante: 'Has encontrado una carpeta de fotos de tus primeros semestres, de cuando todo era más sencillo.', cognitivo: '"Aquello era mejor que esto. Ya no tiene sentido compartirlo con nadie".' },
        { id: 'euforia', nombre: 'Euforia', icono: 'fa-rocket', sintomas: 'Una energía que no puedes contener: hablas rápido y no paras de moverte.', detonante: 'Tu trabajo de la asignatura ha sido elegido como el mejor de la promoción.', cognitivo: '"Soy capaz. Todo el esfuerzo ha servido, hasta las noches sin dormir".' },
        { id: 'estres', nombre: 'Estrés', icono: 'fa-brain', sintomas: 'Dolor de cabeza, los ojos arden y te pican los párpados.', detonante: 'Tienes tres exámenes pesados y un trabajo grupal en la misma semana.', cognitivo: '"No me da el tiempo ni para respirar. Con todo esto no llego a nada".' },
        { id: 'apatia', nombre: 'Apatía', icono: 'fa-eye-slash', sintomas: 'Hablas sin entonación, tienes sueño constante y te mueves más lento.', detonante: 'Llevas un mes repitiendo los mismos días sin que nada cambie en ninguno.', cognitivo: '"Da igual lo que haga. El resultado va a ser el mismo".' },
        { id: 'culpa', nombre: 'Culpa', icono: 'fa-shield-halved', sintomas: 'Un vacío en el estómago, ganas de apartarte y malestar por dentro.', detonante: 'Te quedaste dormido y faltaste a la exposición en la que te tocaba presentar.', cognitivo: '"Es culpa mía que el equipo suspenda. Soy el que lo ha arruinado".' }
    ];

    /*
    |--------------------------------------------------------------------------
    | Estado de la partida
    |
    | `tiempoRestante` va de 0 a 100 y se incrementa 1,66 por segundo: al llegar
    | a 100 se han consumido los sesenta segundos. Se mantiene como porcentaje
    | porque la barra se dibuja con él.
    |--------------------------------------------------------------------------
    */
    let emocionSecreta = null;
    let sospechosoSeleccionado = null;
    let pistaActual = 1;
    let score = 100;
    let estadosTarjetas = {};
    let gameTimer = null;
    let tiempoRestante = 0;

    /* Lo que se guarda de cada partida. La emoción es la etiqueta, no una
       entrada del modelo: el modelo solo ve cuánto se tarda, cuántas veces se
       cambia de idea y el resultado. */
    let startTime = 0;
    let lastEventTime = 0;
    let tapTimestamps = [];
    let latencies = [];
    let rectificacionesCount = 0;
    let erroresCount = 0;
    let telemetriaPersistida = false;

    /* Clases del tablero. Se declaran como literales porque Tailwind compila
       por texto: una clase construida en tiempo de ejecución no existiría en el
       CSS publicado. */
    const TARJETA = 'flex h-28 w-full flex-col items-center justify-center rounded-control border border-white/10 bg-black/40 p-4 text-center transition-all duration-500 [transform-style:preserve-3d]';
    const TARJETA_ENCIMA = 'scale-[1.02] border-accent bg-accent-soft';
    const TARJETA_ENCIMA_TEXTO = 'text-accent-text';
    const TARJETA_APAGADA = 'opacity-20 border-transparent [transform:rotateX(-80deg)]';
    const TARJETA_ENCIMA_SIMBOLO = 'text-accent-text animate-pulse';
    const TARJETA_SIMBOLO = 'text-gray-400 group-hover:text-white';
    const TARJETA_APAGADA_TEXTO = 'text-gray-600';

    /**
     * Pinta una cifra según lo que significa en este momento.
     *
     * Antes esto se resolvía asignando `className` entero desde el guion, con
     * la lista de clases repetida en cada punto del código. Ahora solo se mueve
     * `data-nivel` y el aspecto sale de `.dato-estado` en `app.css`.
     */
    function marcarDato(id, nivel) {
        document.getElementById(id).dataset.nivel = nivel;
    }

    function nivelDePuntos(valor) {
        if (valor >= 75) return 'resuelto';
        if (valor >= 50) return 'estable';
        if (valor >= 25) return 'aviso';
        return 'critico';
    }

    function pintarPuntos() {
        document.getElementById('hud-score').textContent = score;
        marcarDato('hud-score', nivelDePuntos(score));
    }

    function pintarReloj(segundos) {
        document.getElementById('hud-temp').textContent = `${segundos} s`;
        marcarDato('hud-temp', segundos >= 55 ? 'critico' : (segundos >= 40 ? 'aviso' : 'estable'));

        // La barra se VACÍA: lo que se ve es el tiempo que aún queda. El
        // degradado va de acento a rosa hacia la derecha, así que la parte que
        // sobrevive es la calma, en la izquierda.
        const barra = document.getElementById('temp-bar');
        barra.style.width = `${(segundos / 60) * 100}%`;
    }

    /** El aspecto del mensaje central depende de lo que pasó, no del texto. */
    function avisar(texto, estado) {
        const aviso = document.getElementById('system-feedback');
        aviso.textContent = texto;
        aviso.dataset.estado = estado;
    }

    function initGame() {
        if (gameTimer) clearInterval(gameTimer);

        pistaActual = 1;
        score = 100;
        tiempoRestante = 0;
        sospechosoSeleccionado = null;
        estadosTarjetas = {};
        telemetriaPersistida = false;

        startTime = performance.now();
        lastEventTime = performance.now();
        tapTimestamps = [];
        latencies = [];
        rectificacionesCount = 0;
        erroresCount = 0;

        emocionSecreta = bancoEmociones[Math.floor(Math.random() * bancoEmociones.length)];

        pintarPuntos();
        pintarReloj(60);

        const btnPista = document.getElementById('btn-next-clue');
        btnPista.disabled = false;
        btnPista.className = 'flex-1 rounded-control border border-accent/40 px-4 py-2.5 text-[11px] font-semibold text-accent-text transition-colors hover:bg-accent-soft';
        document.getElementById('btn-verdict').disabled = false;

        document.getElementById('wrapper-detonante').classList.add('opacity-25');
        document.getElementById('wrapper-cognitivo').classList.add('opacity-25');

        document.getElementById('clue-sintomas').textContent = emocionSecreta.sintomas;
        document.getElementById('clue-detonante').textContent = 'Todavía no has pedido esta pista.';
        document.getElementById('clue-cognitivo').textContent = 'Todavía no has pedido esta pista.';

        avisar('Empieza por lo que notas en el cuerpo. Marca las emociones que no encajen para apartarlas.', 'info');

        const indicador = document.getElementById('telemetry-live-indicator');
        indicador.dataset.estado = 'esperando';
        indicador.textContent = 'Se guardará tu respuesta y lo que tardas en decidir.';

        gameTimer = setInterval(() => {
            tiempoRestante += 1.66;
            const segundos = Math.max(0, Math.min(60, Math.ceil(60 - tiempoRestante)));

            pintarReloj(segundos);

            if (segundos <= 0) {
                triggerGameOver(`Se acabó el tiempo. La emoción era ${emocionSecreta.nombre}.`);
            }
        }, 1000);

        renderMatrix();
    }

    function recordInteractionLatency() {
        const now = performance.now();
        const delta = now - lastEventTime;
        latencies.push(delta);
        lastEventTime = now;
    }

    function renderMatrix() {
        const contenedor = document.getElementById('emotion-matrix');
        contenedor.textContent = '';

        bancoEmociones.forEach((emocion) => {
            estadosTarjetas[emocion.id] = estadosTarjetas[emocion.id] || { descartada: false };

            const descartada = estadosTarjetas[emocion.id].descartada;
            const seleccionada = sospechosoSeleccionado === emocion.id;

            const celda = document.createElement('div');
            celda.className = 'relative';

            /*
             * La tarjeta y el botón de descartar son DOS `<button>` hermanos y
             * no un botón dentro de otro: los botones anidados son HTML
             * inválido, y la tarjeta con `onclick` sobre un `<div>` no era
             * alcanzable con el teclado — la actividad entera se jugaba solo
             * con ratón.
             *
             * El botón de descartar queda FUERA de la tarjeta por lo mismo:
             * dentro, al pulsarlo se dispararía también la selección.
             */
            const tarjeta = document.createElement('button');
            tarjeta.type = 'button';
            tarjeta.className = TARJETA + ' '
                + (descartada ? TARJETA_APAGADA : (seleccionada ? TARJETA_ENCIMA : 'hover:scale-[1.02] hover:border-white/30'));
            tarjeta.setAttribute('aria-pressed', String(seleccionada));
            if (descartada) tarjeta.setAttribute('aria-disabled', 'true');

            const simbolo = document.createElement('i');
            simbolo.className = 'fa-solid ' + emocion.icono + ' mb-2 text-xl transition-colors '
                + (descartada ? TARJETA_APAGADA_TEXTO
                    : (seleccionada ? TARJETA_ENCIMA_SIMBOLO : TARJETA_SIMBOLO));
            simbolo.setAttribute('aria-hidden', 'true');

            const nombre = document.createElement('p');
            nombre.className = 'text-[13px] font-semibold '
                + (descartada ? TARJETA_APAGADA_TEXTO
                    : (seleccionada ? TARJETA_ENCIMA_TEXTO : 'text-gray-300'));
            nombre.textContent = emocion.nombre;

            tarjeta.append(simbolo, nombre);

            const descartar = document.createElement('button');
            descartar.type = 'button';
            descartar.className = 'absolute right-2 top-2 z-10 flex h-6 w-6 items-center justify-center rounded-control border border-white/10 bg-black/60 text-gray-400 transition-colors hover:border-white/30 hover:text-white';
            descartar.setAttribute('aria-pressed', String(descartada));
            descartar.setAttribute(
                'aria-label',
                (descartada ? 'Recuperar ' : 'Apartar ') + emocion.nombre
            );

            const iconoDescarte = document.createElement('i');
            iconoDescarte.className = 'fa-solid ' + (descartada ? 'fa-rotate-left' : 'fa-eye-slash') + ' text-[10px]';
            iconoDescarte.setAttribute('aria-hidden', 'true');
            descartar.appendChild(iconoDescarte);

            tarjeta.addEventListener('click', () => selectTarget(emocion.id));
            descartar.addEventListener('click', () => toggleFlip(emocion.id));

            celda.append(tarjeta, descartar);
            contenedor.appendChild(celda);
        });
    }

    function toggleFlip(id) {
        playSound('flip');
        estadosTarjetas[id].descartada = !estadosTarjetas[id].descartada;

        if (estadosTarjetas[id].descartada && sospechosoSeleccionado === id) {
            sospechosoSeleccionado = null;
        }

        rectificacionesCount++;
        recordInteractionLatency();
        renderMatrix();
    }

    function selectTarget(id) {
        if (estadosTarjetas[id].descartada) return;

        playSound('click');
        sospechosoSeleccionado = (sospechosoSeleccionado === id) ? null : id;

        rectificacionesCount++;
        recordInteractionLatency();
        renderMatrix();
    }

    function revealNextClue() {
        playSound('click');
        recordInteractionLatency();

        if (pistaActual === 1) {
            pistaActual = 2;
            score -= 25;
            document.getElementById('wrapper-detonante').classList.remove('opacity-25');
            document.getElementById('clue-detonante').textContent = emocionSecreta.detonante;
        } else if (pistaActual === 2) {
            pistaActual = 3;
            score -= 25;
            document.getElementById('wrapper-cognitivo').classList.remove('opacity-25');
            document.getElementById('clue-cognitivo').textContent = emocionSecreta.cognitivo;

            const btnPista = document.getElementById('btn-next-clue');
            btnPista.disabled = true;
            btnPista.className = 'flex-1 cursor-not-allowed rounded-control border border-white/5 bg-white/5 px-4 py-2.5 text-[11px] font-semibold text-gray-600';
        }

        pintarPuntos();
    }

    /* Un latigazo de la pantalla cuando una respuesta es incorrecta. */
    function triggerGlitchInterference() {
        const overlay = document.getElementById('destello-overlay');
        const gamePanel = document.getElementById('master-game-panel');

        overlay.classList.add('activo');
        gamePanel.classList.add('error-tablero');

        setTimeout(() => {
            overlay.classList.remove('activo');
            gamePanel.classList.remove('error-tablero');
        }, 350);
    }

    /*
    |--------------------------------------------------------------------------
    | Comprobación de la respuesta
    |--------------------------------------------------------------------------
    */
    function verifyDiagnosis() {
        if (!sospechosoSeleccionado) {
            playSound('error');
            triggerGlitchInterference();
            avisar('Antes tienes que elegir una emoción del tablero.', 'fallo');
            return;
        }

        recordInteractionLatency();

        if (sospechosoSeleccionado === emocionSecreta.id) {
            clearInterval(gameTimer);
            playSound('success');

            marcarDato('hud-score', 'resuelto');
            avisar(`Correcto. La emoción era ${emocionSecreta.nombre}. Has acertado con las tres pistas.`, 'acierto');

            document.getElementById('btn-next-clue').disabled = true;
            document.getElementById('btn-verdict').disabled = true;

            enviarTelemetriaBackend(true);
        } else {
            playSound('error');
            triggerGlitchInterference();
            erroresCount++;

            score = Math.max(0, score - 20);

            pintarPuntos();
            avisar(`«${emocionSecreta.nombre}» no encaja con lo que dicen las pistas. Mira otra vez.`, 'fallo');

            if (score <= 0) {
                triggerGameOver(`Te has quedado sin puntos. La emoción era ${emocionSecreta.nombre}.`);
            }
        }
    }

    function triggerGameOver(mensaje) {
        clearInterval(gameTimer);
        playSound('error');
        triggerGlitchInterference();

        score = 0;
        pintarPuntos();
        marcarDato('hud-score', 'agotado');
        marcarDato('hud-temp', 'agotado');

        avisar(mensaje, 'fallo');

        document.getElementById('btn-verdict').disabled = true;
        document.getElementById('btn-next-clue').disabled = true;

        enviarTelemetriaBackend(false);
    }

    /*
    |--------------------------------------------------------------------------
    | Envío de la muestra
    |
    | Lo que se guarda es el tiempo que se tardó, cuántas veces se cambió de
    | idea, cuántos intentos fallidos y la respuesta elegida. La emoción
    | secreto es la ETIQUETA de la muestra, no una entrada del modelo.
    |--------------------------------------------------------------------------
    */
    function enviarTelemetriaBackend(isCorrect) {
        if (telemetriaPersistida) return;
        telemetriaPersistida = true;

        const totalDurationMs = Math.round(performance.now() - startTime);

        const avgLatencyMs = latencies.length > 0
            ? Math.round(latencies.reduce((a, b) => a + b, 0) / latencies.length)
            : totalDurationMs;

        const tappingRate = tapTimestamps.length > 1
            ? parseFloat((tapTimestamps.length / (totalDurationMs / 1000)).toFixed(2))
            : 0.5;

        const payload = {
            minijuego_id: 'codigo_anomalo',
            latencia_promedio_ms: avgLatencyMs,
            frecuencia_tapping: tappingRate,
            tiempo_total_ms: totalDurationMs,
            conteo_rectificaciones: rectificacionesCount,
            errores_diagnostico: erroresCount,
            score_final: score,
            emocion_objetivo: emocionSecreta.id,
            emocion_predicha: sospechosoSeleccionado || 'ninguno',
            diagnostico_correcto: isCorrect,
            vector_caracteristicas: [avgLatencyMs, tappingRate, totalDurationMs, rectificacionesCount, score]
        };

        const indicador = document.getElementById('telemetry-live-indicator');

        fetch("{{ route('minijuegos.telemetria') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json().then(data => ({ ok: res.ok, status: res.status, data })))
        .then(({ ok, status, data }) => {
            if (!ok) {
                // Un 4xx/5xx SÍ es una respuesta HTTP válida, así que el .catch()
                // nunca se dispara. Antes un 422 caía en la rama contraria y la
                // interfaz anunciaba que la muestra se había guardado cuando se
                // acababa de perder en silencio.
                const detalle = data.errors
                    ? Object.entries(data.errors).map(([campo, msj]) => `${campo}: ${msj[0]}`).join(' | ')
                    : (data.message || `HTTP ${status}`);

                console.error('Muestra rechazada:', detalle, data);

                indicador.dataset.estado = 'fallo';
                indicador.textContent = `No se ha podido guardar la respuesta de esta partida (${status}).`;
                return;
            }

            indicador.dataset.estado = 'guardado';

            if (data.ia_output) {
                /*
                 * La estimación del modelo NO sustituye al mensaje del acierto.
                 *
                 * Antes, al llegar la respuesta del servidor, el mensaje central
                 * se reescribía entero: el jugador veía «¡has acertado!» y, un
                 * instante después, «el modelo estima que sentías alegría»,
                 * con la estimación de un clasificador que solo ha visto cuánto
                 * ha tardado. Dos frases que se contradicen, y la segunda
                 * pisando la que de verdad respondía a lo que había hecho.
                 *
                 * Ahora la estimación vive en la línea de estado, que es donde
                 * cabe un dato informativo sin pisar el resultado.
                 */
                const coincidencia = Math.round(data.ia_output.confianza);
                indicador.textContent =
                    `Respuesta guardada. Según el tiempo que has tardado y las veces que has cambiado de `
                    + `idea, la estimación automática sería «${data.ia_output.etiqueta}» `
                    + `(${coincidencia} % de coincidencia).`;
            } else {
                indicador.textContent = 'Respuesta guardada. Puedes volver a intentarlo cuando quieras.';
            }
        })
        .catch(err => {
            console.error('Error de red al registrar la partida:', err);

            indicador.dataset.estado = 'fallo';
            indicador.textContent = 'No se ha podido guardar la respuesta: no hay conexión con el servidor.';
        });
    }

    /* Cadencia de pulsaciones. Se mide solo mientras la partida sigue abierta. */
    document.addEventListener('pointerdown', () => {
        if (!telemetriaPersistida) {
            tapTimestamps.push(performance.now());
        }
    });

    document.addEventListener('DOMContentLoaded', initGame);
</script>
@endpush
