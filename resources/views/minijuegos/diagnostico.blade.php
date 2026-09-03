@extends('layouts.app')

@push('styles')
<style>
    /* 💥 INGENIERÍA DE EFECTO GLITCH (INTERFERENCIA DE TERMINAL) */
    @keyframes glitch-shake {
        0% { transform: translate(0) skew(0deg); }
        20% { transform: translate(-6px, 4px) skew(-3deg); }
        40% { transform: translate(6px, -4px) skew(3deg); filter: contrast(150%) hue-rotate(90deg); }
        60% { transform: translate(-3px, -2px) skew(1deg); }
        80% { transform: translate(4px, 3px) skew(-2deg); filter: invert(10%); }
        100% { transform: translate(0) skew(0deg); }
    }

    /* Clase reactiva que sacude la matriz de juego */
    .terminal-error-glitch {
        animation: glitch-shake 0.35s linear infinite;
        box-shadow: 0 0 40px rgba(244, 63, 94, 0.4) !important;
        border-color: var(--neon-rose) !important;
    }

    /* Capa de destello rojo de emergencia transitorio */
    .glitch-flash-overlay {
        position: fixed;
        inset: 0;
        background: radial-gradient(circle, rgba(244, 63, 94, 0.15) 0%, rgba(0, 0, 0, 0) 80%);
        mix-blend-mode: difference;
        z-index: 9990;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.05s ease;
    }

    .glitch-flash-overlay.active {
        opacity: 1;
    }
</style>
@endpush

@section('content')
{{-- CAPA DE INTERFERENCIA CRÍTICA --}}
<div id="glitch-overlay" class="glitch-flash-overlay"></div>

<div class="p-6 max-w-6xl mx-auto select-none">

    {{-- HUD SUPERIOR DE TELEMETRÍA --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-white/5 pb-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[8px] font-black tracking-widest uppercase bg-cyan-500/10 text-neon-cyan border border-cyan-500/30 font-orbitron">
                    TELEMETRÍA IMPLÍCITA // RANDOM FOREST CLASSIFIER
                </span>
            </div>
            <h1 class="font-orbitron text-2xl font-black adaptive-title uppercase tracking-tighter">
                Análisis de <span class="text-accent">Código Anómalo</span>
            </h1>
            <p class="text-gray-500 text-[9px] uppercase tracking-[0.3em] mt-1 font-semibold">Módulo de Depuración Emocional y Descarte de Variables</p>
        </div>

        <div class="mt-4 md:mt-0 flex gap-4 font-orbitron">
            <div class="bg-black/40 border border-white/10 px-4 py-2 rounded-xl text-center shadow-md min-w-[100px]">
                <p class="text-[7px] text-gray-500 uppercase font-black tracking-widest">Núcleo Temp</p>
                <p id="hud-temp" class="text-sm font-black text-blue-400">0°C</p>
            </div>
            <div class="bg-black/40 border border-white/10 px-5 py-2 rounded-xl text-center shadow-md min-w-[110px]">
                <p class="text-[7px] text-gray-500 uppercase font-black tracking-widest">Integridad</p>
                <p id="hud-score" class="text-sm font-black text-emerald-400">100 PTS</p>
            </div>
        </div>
    </div>

    {{-- BARRA DE PROGRESO DE TEMPERATURA CRÍTICA --}}
    <div class="w-full h-1.5 bg-white/5 rounded-full mb-6 overflow-hidden border border-white/5">
        <div id="temp-bar" class="w-0 h-full bg-gradient-to-r from-blue-500 via-amber-500 to-rose-500 transition-all duration-1000 ease-linear"></div>
    </div>

    <div id="master-game-panel" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start transition-all duration-200">

        {{-- PANEL DE EVIDENCIAS --}}
        <div class="space-y-4 lg:sticky lg:top-24">
            <div class="bg-black/40 border border-white/10 backdrop-blur-xl p-6 rounded-2xl relative overflow-hidden shadow-lg">
                <div class="absolute top-0 right-0 h-1 w-24 bg-accent shadow-[0_0_10px_var(--neon-accent)]"></div>

                <h3 class="font-orbitron text-[10px] font-black uppercase tracking-widest mb-4 flex items-center gap-2 adaptive-title">
                    <i class="fa-solid fa-satellite-dish text-accent animate-pulse"></i> Paquetes de Datos
                </h3>

                <div class="space-y-3">
                    {{-- Capa 1: Fisiológica --}}
                    <div class="p-3 bg-white/5 border border-white/5 rounded-xl">
                        <span class="text-[7px] font-black text-accent tracking-wider uppercase block mb-1">[ CAPA_01 // SÍNTOMAS FISIOLÓGICOS ]</span>
                        <p id="clue-sintomas" class="text-xs adaptive-title leading-relaxed font-medium">Extrayendo datos corporales...</p>
                    </div>

                    {{-- Capa 2: Detonante --}}
                    <div id="wrapper-detonante" class="p-3 bg-white/5 border border-white/5 rounded-xl opacity-25 transition-all duration-500">
                        <span class="text-[7px] font-black text-purple-400 tracking-wider uppercase block mb-1">[ CAPA_02 // REGISTRO DE ENTORNO ]</span>
                        <p id="clue-detonante" class="text-xs text-gray-500 leading-relaxed font-medium">Bloqueado. Requiere descarte parcial...</p>
                    </div>

                    {{-- Capa 3: Cognitiva --}}
                    <div id="wrapper-cognitivo" class="p-3 bg-white/5 border border-white/5 rounded-xl opacity-25 transition-all duration-500">
                        <span class="text-[7px] font-black text-rose-400 tracking-wider uppercase block mb-1">[ CAPA_03 // BUCLE COGNITIVO ]</span>
                        <p id="clue-cognitivo" class="text-xs text-gray-500 leading-relaxed font-medium">Bloqueado. Requiere análisis avanzado...</p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-white/5 flex gap-2">
                    <button id="btn-next-clue" onclick="revealNextClue()"
                            class="flex-1 border border-accent text-accent py-2.5 rounded-xl text-[9px] font-black uppercase tracking-wider hover:bg-accent/10 transition-all font-orbitron">
                        Solicitar Datos
                    </button>
                    <button id="btn-verdict" onclick="verifyDiagnosis()"
                            class="flex-1 bg-accent text-white py-2.5 rounded-xl text-[9px] font-black uppercase tracking-wider hover:shadow-[0_0_15px_var(--neon-accent)] transition-all font-orbitron">
                        Aislar Anomalía
                    </button>
                </div>
            </div>

            <div class="bg-accent/5 border border-accent/10 p-4 rounded-xl text-center shadow-inner">
                <p id="system-feedback" class="text-gray-400 text-[10px] uppercase tracking-wider font-semibold">
                    Analiza los síntomas. Usa el botón superior de las tarjetas para tumbar las opciones falsas.
                </p>
            </div>
        </div>

        {{-- MATRIZ DE EMOCIONES (TABLERO PERSPECTIVA 3D) --}}
        <div class="lg:col-span-2 [perspective:1000px]">
            <div id="emotion-matrix" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 transition-all duration-300">
                {{-- Inyección dinámica por JS --}}
            </div>

            <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3">
                <span id="telemetry-live-indicator" class="text-[8px] font-mono text-gray-500 uppercase tracking-widest font-orbitron">
                    ● LOGGING_ACTIVE // LATENCY & TAPPING CAPTURE
                </span>
                <button onclick="initGame()" class="border border-white/10 text-gray-400 hover:border-white/30 px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all font-orbitron">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Reiniciar Matriz
                </button>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    /*
    |--------------------------------------------------------------------------
    | AUDIO FX ENGINE (Web Audio API nativa)
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
    | BANCO DE CASOS Y ESTADOS CONDUCTUALES (UNIVERSITARIOS)
    |--------------------------------------------------------------------------
    */
    const bancoEmociones = [
        { id: 'ansiedad', nombre: 'Ansiedad', icono: 'fa-bolt-lightning', sintomas: 'Palpitaciones aceleradas, respiración entrecortada superficial y manos frías.', detonante: 'Faltan 2 horas para la entrega del proyecto y el servidor local empieza a tirar Errores 500.', cognitivo: 'Bucle lógico: "No va a funcionar, voy a reprobar todo el parcial por esto y arruinaré mi promedio".' },
        { id: 'ira', nombre: 'Ira', icono: 'fa-fire-flame-curved', sintomas: 'Mandíbula tensa y apretada, calor súbito en el rostro y aceleración del pulso.', detonante: 'Un compañero de equipo borró la rama principal del repositorio de Git y se desconectó.', cognitivo: 'Bucle lógico: "¡Es una falta de respeto total, siempre echan a perder el trabajo ajeno!".' },
        { id: 'frustracion', nombre: 'Frustración', icono: 'fa-triangle-exclamation', sintomas: 'Rigidez muscular severa en hombros, suspiros repetitivos y pesadez mental.', detonante: 'Llevas 6 horas intentando corregir un bug de migración de base de datos sin éxito.', cognitivo: 'Bucle lógico: "Por más que investigo no avanzo nada, tal vez la ingeniería de sistemas no es para mí".' },
        { id: 'melancolia', nombre: 'Melancolía', icono: 'fa-cloud-showers-water', sintomas: 'Desgano motriz generalizado, mirada fija desenfocada y opresión leve en el pecho.', detonante: 'Encontraste una carpeta de fotos viejas de tus primeros semestres presenciales en el TESVB.', cognitivo: 'Bucle lógico: "Esos tiempos eran mucho más felices y sencillos, las cosas se han vuelto muy frías ahora".' },
        { id: 'euforia', nombre: 'Euforia', icono: 'fa-rocket', sintomas: 'Disparo de energía vital al 100%, risas espontáneas e imposibilidad de quedarse quieto.', detonante: 'El director te notifica que tu sistema Start-Emotion fue seleccionado como el mejor del semestre.', cognitivo: 'Bucle lógico: "¡Soy completamente capaz, todo el esfuerzo dio frutos, valió la pena cada desvelo!".' },
        { id: 'estres', nombre: 'Estrés', icono: 'fa-brain', sintomas: 'Dolor punzante de cabeza, ardor y fatiga ocular extrema junto a tics en el párpado.', detonante: 'Saturación simultánea de 3 exámenes pesados, entrega de bitácoras y prácticas en la misma semana.', cognitivo: 'Bucle lógico: "No tengo tiempo ni para respirar, no voy a dar el ancho con tantos entregables".' },
        { id: 'apatia', nombre: 'Apatía', icono: 'fa-eye-slash', sintomas: 'Tono de voz totalmente plano, somnolencia constante y movimientos ralentizados.', detonante: 'Llevar un mes completo repitiendo la misma rutina exacta de código y escuela sin descanso.', cognitivo: 'Bucle lógico: "Qué más da, da igual si entrego o no, de todos modos el resultado va a ser el mismo".' },
        { id: 'culpa', nombre: 'Culpa', icono: 'fa-shield-halved', sintomas: 'Sensación de nudo vacío en el estómago, evitación del entorno y malestar interno.', detonante: 'Te quedaste dormido y faltaste a la exposición en equipo donde tú tenías las diapositivas finales.', cognitivo: 'Bucle lógico: "Es mi culpa que mis compañeros tengan mala nota, soy un pésimo compañero de equipo".' }
    ];

    /*
    |--------------------------------------------------------------------------
    | VARIABLES DE ESTADO Y TELEMETRÍA IMPLÍCITA (BIOMARCADORES)
    |--------------------------------------------------------------------------
    */
    let emocionSecreta = null;
    let sospechosoSeleccionado = null;
    let pistaActual = 1;
    let score = 100;
    let estadosTarjetas = {};
    let gameTimer = null;
    let coreTemp = 0;

    // Métricas de Computación Afectiva
    let startTime = 0;
    let lastEventTime = 0;
    let tapTimestamps = [];
    let latencies = [];
    let rectificacionesCount = 0;
    let erroresCount = 0;
    let telemetriaPersistida = false;

    function initGame() {
        if (gameTimer) clearInterval(gameTimer);

        pistaActual = 1;
        score = 100;
        coreTemp = 0;
        sospechosoSeleccionado = null;
        estadosTarjetas = {};
        telemetriaPersistida = false;

        // Reiniciar métricas científicas
        startTime = performance.now();
        lastEventTime = performance.now();
        tapTimestamps = [];
        latencies = [];
        rectificacionesCount = 0;
        erroresCount = 0;

        emocionSecreta = bancoEmociones[Math.floor(Math.random() * bancoEmociones.length)];

        // Limpieza de HUD
        document.getElementById('hud-score').innerText = `${score} PTS`;
        document.getElementById('hud-score').className = "text-sm font-black text-emerald-400";
        document.getElementById('hud-temp').innerText = "0°C";
        document.getElementById('hud-temp').className = "text-sm font-black text-blue-400";
        document.getElementById('temp-bar').style.width = "0%";

        document.getElementById('btn-next-clue').disabled = false;
        document.getElementById('btn-next-clue').className = "flex-1 border border-accent text-accent py-2.5 rounded-xl text-[9px] font-black uppercase tracking-wider hover:bg-accent/10 transition-all font-orbitron";
        document.getElementById('btn-verdict').disabled = false;

        document.getElementById('wrapper-detonante').classList.add('opacity-25');
        document.getElementById('wrapper-cognitivo').classList.add('opacity-25');

        document.getElementById('clue-sintomas').innerText = emocionSecreta.sintomas;
        document.getElementById('clue-detonante').innerText = "Bloqueado. Requiere descarte parcial en matriz...";
        document.getElementById('clue-cognitivo').innerText = "Bloqueado. Requiere análisis avanzado...";

        document.getElementById('system-feedback').innerText = 'Analiza la Capa 01. Usa el botón de ojo para tirar las pestañas de las emociones falsas.';
        document.getElementById('system-feedback').className = "text-gray-400 text-[10px] uppercase tracking-wider font-semibold";
        document.getElementById('telemetry-live-indicator').innerHTML = `● LOGGING_ACTIVE // LATENCY & TAPPING CAPTURE`;

        // Reloj de Temperatura (60s)
        gameTimer = setInterval(() => {
            coreTemp += 1.66;
            let displayTemp = Math.round(coreTemp);

            document.getElementById('hud-temp').innerText = `${displayTemp}°C`;
            document.getElementById('temp-bar').style.width = `${coreTemp}%`;

            if (displayTemp >= 70) {
                document.getElementById('hud-temp').className = "text-sm font-black text-rose-500 animate-pulse";
            } else if (displayTemp >= 40) {
                document.getElementById('hud-temp').className = "text-sm font-black text-amber-500";
            }

            if (coreTemp >= 100) {
                triggerGameOver("SOBRECARGA: El núcleo del sistema colapsó por exceso de temperatura.");
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
        const container = document.getElementById('emotion-matrix');
        container.innerHTML = '';

        bancoEmociones.forEach(emocion => {
            estadosTarjetas[emocion.id] = estadosTarjetas[emocion.id] || { tumbada: false };
            const isTumbada = estadosTarjetas[emocion.id].tumbada;
            const isSeleccionada = sospechosoSeleccionado === emocion.id;

            let clasesTarjeta = "bg-black/40 border border-white/10 backdrop-blur-xl p-4 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer relative h-28 select-none transition-all duration-500 [transform-style:preserve-3d] group ";

            if (isTumbada) {
                clasesTarjeta += "[transform:rotateX(-80deg)] opacity-10 border-gray-800 pointer-events-none-override";
            } else if (isSeleccionada) {
                clasesTarjeta += "border-accent bg-accent/10 shadow-[0_0_15px_rgba(34,211,238,0.2)] scale-102";
            } else {
                clasesTarjeta += "hover:border-white/30 hover:scale-[1.02]";
            }

            const plantilla = `
                <div class="${clasesTarjeta}" onclick="selectTarget('${emocion.id}')">
                    <button onclick="toggleFlip(event, '${emocion.id}')"
                            class="absolute top-2 right-2 h-5 w-5 rounded-md bg-white/5 border border-white/10 flex items-center justify-center text-[9px] text-gray-500 hover:text-white hover:border-white/30 transition-all z-30 [backface-visibility:hidden]">
                        <i class="fa-solid ${isTumbada ? 'fa-arrow-up text-accent' : 'fa-eye-slash'}"></i>
                    </button>

                    <i class="fa-solid ${emocion.icono} text-xl ${isSeleccionada ? 'text-accent animate-pulse' : 'text-gray-400 group-hover:text-white'} mb-2 transition-colors [backface-visibility:hidden]"></i>
                    <p class="font-orbitron text-[10px] font-black uppercase tracking-wider ${isSeleccionada ? 'text-accent' : 'adaptive-title'} [backface-visibility:hidden]">${emocion.nombre}</p>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', plantilla);
        });
    }

    function toggleFlip(event, id) {
        event.stopPropagation();
        playSound('flip');
        estadosTarjetas[id].tumbada = !estadosTarjetas[id].tumbada;

        if (estadosTarjetas[id].tumbada && sospechosoSeleccionado === id) {
            sospechosoSeleccionado = null;
        }

        rectificacionesCount++;
        recordInteractionLatency();
        renderMatrix();
    }

    function selectTarget(id) {
        if (estadosTarjetas[id].tumbada) return;
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
            document.getElementById('clue-detonante').innerText = emocionSecreta.detonante;
        } else if (pistaActual === 2) {
            pistaActual = 3;
            score -= 25;
            document.getElementById('wrapper-cognitivo').classList.remove('opacity-25');
            document.getElementById('clue-cognitivo').innerText = emocionSecreta.cognitivo;

            document.getElementById('btn-next-clue').disabled = true;
            document.getElementById('btn-next-clue').className = "flex-1 bg-white/5 border border-white/5 text-gray-600 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-wider cursor-not-allowed font-orbitron";
        }

        document.getElementById('hud-score').innerText = `${score} PTS`;
        if (score <= 50) document.getElementById('hud-score').className = "text-sm font-black text-rose-500 animate-pulse";
    }

    // TRIGGER GLITCH EFFECT (INTERFERENCIA DE PANTALLA)
    function triggerGlitchInterference() {
        const overlay = document.getElementById('glitch-overlay');
        const gamePanel = document.getElementById('master-game-panel');

        overlay.classList.add('active');
        gamePanel.classList.add('terminal-error-glitch');

        setTimeout(() => {
            overlay.classList.remove('active');
            gamePanel.classList.remove('terminal-error-glitch');
        }, 350);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICACIÓN DE DIAGNÓSTICO Y PERSISTENCIA DE TELEMETRÍA
    |--------------------------------------------------------------------------
    */
    function verifyDiagnosis() {
        if (!sospechosoSeleccionado) {
            playSound('error');
            triggerGlitchInterference();
            document.getElementById('system-feedback').innerText = "ERROR DE DIAGNÓSTICO: Selecciona un sospechoso antes de ejecutar el aislamiento.";
            document.getElementById('system-feedback').className = "text-rose-500 text-[10px] uppercase tracking-wider font-black";
            return;
        }

        recordInteractionLatency();

        if (sospechosoSeleccionado === emocionSecreta.id) {
            clearInterval(gameTimer);
            playSound('success');

            document.getElementById('system-feedback').innerText = `¡DIAGNÓSTICO EXITOSO! Anomalía resuelta. Identificaste correctamente la ${emocionSecreta.nombre.toUpperCase()}. Sincronizando inferencia Random Forest...`;
            document.getElementById('system-feedback').className = "text-emerald-400 text-[10px] uppercase tracking-widest font-black border border-emerald-500/20 p-2 rounded-lg bg-emerald-500/5";

            document.getElementById('btn-next-clue').disabled = true;
            document.getElementById('btn-verdict').disabled = true;

            document.getElementById('hud-score').innerText = `CONCLUIDO // ${score} PTS`;
            document.getElementById('hud-score').className = "text-sm font-black text-emerald-400 shadow-accent";

            enviarTelemetriaBackend(true);
        } else {
            // Error en la detección
            playSound('error');
            triggerGlitchInterference();
            erroresCount++;

            score -= 20;
            if (score < 0) score = 0;

            document.getElementById('hud-score').innerText = `${score} PTS`;
            document.getElementById('system-feedback').innerText = `FALSO POSITIVO: El sospechoso "${sospechosoSeleccionado.toUpperCase()}" no encaja con los registros biológicos.`;
            document.getElementById('system-feedback').className = "text-rose-500 text-[10px] uppercase tracking-wider font-black";

            if (score <= 0) {
                triggerGameOver(`SISTEMA CORRUPTO: Agotaste la integridad de la matriz. La anomalía era: ${emocionSecreta.nombre.toUpperCase()}.`);
            }
        }
    }

    function triggerGameOver(message) {
        clearInterval(gameTimer);
        playSound('error');
        triggerGlitchInterference();

        document.getElementById('system-feedback').innerText = message;
        document.getElementById('system-feedback').className = "text-rose-500 text-[10px] uppercase tracking-wider font-black border border-rose-500/20 p-2 rounded-lg bg-rose-500/5";

        document.getElementById('btn-verdict').disabled = true;
        document.getElementById('btn-next-clue').disabled = true;

        document.getElementById('hud-score').innerText = "FAILED // 0 PTS";
        document.getElementById('hud-score').className = "text-sm font-black text-rose-500 animate-pulse";

        enviarTelemetriaBackend(false);
    }

    /*
    |--------------------------------------------------------------------------
    | DISPATCH ASÍNCRONO DE TELEMETRÍA AL BACKEND (LARAVEL API + RANDOM FOREST)
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

        fetch("{{ route('minijuegos.telemetria') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.ia_output) {
                const ia = data.ia_output;
                document.getElementById('telemetry-live-indicator').innerHTML = `
                    <span class="text-emerald-400 font-bold">
                        ● RANDOM_FOREST_ACTIVE // PREDICCIÓN IA: [ ${ia.prediccion.toUpperCase()} ] // CERTEZA: ${ia.confianza}% // LATENCIA: ${avgLatencyMs}ms
                    </span>
                `;

                document.getElementById('system-feedback').innerHTML = `
                    <span class="text-accent font-bold font-orbitron">[ INFERENCIA IA COMPLETADA ]</span>
                    El ensamble de Bosques Aleatorios clasificó tu comportamiento como <strong>${ia.prediccion.toUpperCase()}</strong> con <strong>${ia.confianza}%</strong> de certidumbre matemática.
                `;
            } else {
                document.getElementById('telemetry-live-indicator').innerHTML = `
                    <span class="text-emerald-400 font-bold">● BIO-DATA PERSISTIDA // ID: #${data.id} // LATENCIA: ${avgLatencyMs}ms // CADENCIA: ${tappingRate} taps/s</span>
                `;
            }
        })
        .catch(err => {
            console.error("Error al registrar telemetría:", err);
        });
    }

    // Escuchador global para medir la cadencia motriz (Tapping Rate)
    document.addEventListener('pointerdown', () => {
        if (!telemetriaPersistida) {
            tapTimestamps.push(performance.now());
        }
    });

    document.addEventListener('DOMContentLoaded', initGame);
</script>
@endpush
