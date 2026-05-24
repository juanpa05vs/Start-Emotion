@extends('layouts.app')

@section('title', 'Start-Emotion | Dashboard')

@section('content')
    {{-- NOTIFICACIÓN DE ESTADO --}}
    @if (session('status'))
        <div class="fixed top-24 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-4 pointer-events-none">
            <div class="bg-neon-cyan/90 backdrop-blur-md border border-white/20 text-black px-6 py-4 rounded-2xl shadow-[0_0_30px_rgba(34,211,238,0.5)] animate-bounce flex items-center justify-between pointer-events-auto">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚡</span>
                    <span class="font-black text-[10px] uppercase tracking-widest">{{ session('status') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-black/50 hover:text-black font-bold text-lg">✕</button>
            </div>
        </div>
    @endif

    {{-- BIO-HUD (LIVE STATUS) --}}
    <div class="fixed top-26 right-10 z-50 pointer-events-none md:pointer-events-auto">
        <div class="bg-black/60 backdrop-blur-xl border border-white/10 p-4 rounded-2xl shadow-2xl border-l-4 {{ $ultimoRegistro ? $ultimoRegistro->getStressStatus()['color'] : 'border-neon-cyan' }}">
            <div class="flex items-center space-x-4">
                <div class="relative flex h-3 w-3">
                    <span class="{{ $ultimoRegistro ? $ultimoRegistro->getStressStatus()['pulse'] : 'animate-pulse' }} absolute inline-flex h-full w-full rounded-full bg-neon-cyan opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-neon-cyan"></span>
                </div>
                <div>
                    <p class="text-[8px] text-gray-500 uppercase tracking-[0.3em]">BIO-SYNC LIVE</p>
                    <h4 class="text-[10px] font-black tracking-widest {{ $ultimoRegistro ? $ultimoRegistro->getStressStatus()['color'] : 'text-neon-cyan' }}">
                        STATUS: {{ $ultimoRegistro ? $ultimoRegistro->getStressStatus()['label'] : 'ESPERANDO SEÑAL' }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    {{-- ENCABEZADO --}}
    <header class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-black uppercase italic tracking-tight adaptive-title">
                Bienvenido, <span class="text-accent">{{ auth()->user()->nombre }}</span>
            </h1>
            <p class="text-gray-500 text-[10px] mt-1 uppercase tracking-[0.4em]">SISTEMA OPERATIVO ONLINE</p>
        </div>
        <a href="{{ route('emociones.reporte') }}" class="group flex items-center gap-3 bg-white/5 border border-white/10 px-6 py-3 rounded-xl hover:bg-neon-cyan hover:text-black transition-all duration-500 w-full md:w-auto justify-center">
            <i class="fa-solid fa-file-pdf text-neon-cyan group-hover:text-black transition-colors"></i>
            <span class="text-[10px] font-black uppercase tracking-widest">Generar Reporte Bio-Sync</span>
        </a>
    </header>

    {{-- CARDS PRINCIPALES --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-black/40 backdrop-blur-xl border border-white/10 p-6 rounded-[2rem] shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-neon-purple/10 blur-2xl rounded-full"></div>
            <p class="text-gray-500 text-[9px] mb-2 uppercase tracking-[0.3em] font-black">Última Detección</p>
            <h3 class="text-2xl font-black italic uppercase text-accent truncate">
                {{ $ultimoRegistro ? $ultimoRegistro->emocion : 'SIN DATOS' }}
            </h3>
        </div>
        <div class="bg-black/40 backdrop-blur-xl border border-white/10 p-6 rounded-[2rem] shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-neon-cyan/10 blur-2xl rounded-full"></div>
            <p class="text-gray-500 text-[9px] mb-2 uppercase tracking-[0.3em] font-black">Amplitud / Intensidad</p>
            <h3 class="text-3xl font-black italic text-neon-cyan drop-shadow-[0_0_10px_rgba(34,211,238,0.4)] font-orbitron">
                {{ $ultimoRegistro ? $ultimoRegistro->energia . '%' : '0%' }}
            </h3>
        </div>
        <div class="bg-black/40 backdrop-blur-xl border border-white/10 p-6 rounded-[2rem] shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-neon-rose/10 blur-2xl rounded-full"></div>
            <p class="text-gray-500 text-[9px] mb-2 uppercase tracking-[0.3em] font-black">Estrés Estimado</p>
            <h3 class="text-2xl font-black italic text-neon-rose uppercase font-orbitron">
                {{ $ultimoRegistro ? $ultimoRegistro->nivel_estres_estimado . '%' : '---' }}
            </h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <section class="lg:col-span-2 bg-black/40 border border-white/10 p-8 rounded-[2.5rem] backdrop-blur-xl shadow-2xl relative overflow-hidden">
            <div class="h-72"><canvas id="emocionChart"></canvas></div>
        </section>
        <section class="bg-black/40 border border-white/10 p-8 rounded-[2.5rem] backdrop-blur-xl flex flex-col items-center justify-center text-center shadow-2xl relative">
            <h3 class="text-neon-purple text-[13px] uppercase tracking-[0.5em] font-black mb-4">Análisis Neural</h3>
            <p class="text-[13px] text-gray-300 leading-relaxed italic px-6 font-medium">
                "{{ $ultimoRegistro ? $ultimoRegistro->recomendacion : 'Sincroniza el sistema para iniciar el análisis conductual.' }}"
            </p>
        </section>
    </div>

    {{-- INTERFAZ COMPLETA DE REGISTRO INTEGRADO (9 EMOCIONES) --}}
    <section class="bg-black/40 border border-white/10 p-8 rounded-[2.5rem] backdrop-blur-xl shadow-2xl">
        <h2 class="text-neon-cyan text-[16px] tracking-[0.5em] font-black uppercase mb-8 text-center">Registro de Telemetría Emocional</h2>

        <form action="{{ route('emociones.store') }}" method="POST" class="space-y-8">
            @csrf

            {{-- EL TABLERO MAESTRO DE 9 BOTONES --}}
            <div>
                <label class="text-[12px] text-gray-500 block mb-4 uppercase tracking-[0.2em] font-black">1. Foco del Núcleo (Selecciona tu estado analítico)</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="felicidad" class="sr-only peer" onchange="updateSliderLabels('positivo')" required>
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-cyan-400 peer-checked:bg-cyan-400/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-face-smile text-lg text-gray-500 peer-checked:text-cyan-400 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Felicidad</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="entusiasta" class="sr-only peer" onchange="updateSliderLabels('positivo')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-cyan-400 peer-checked:bg-cyan-400/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-rocket text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Entusiasta</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="productivo" class="sr-only peer" onchange="updateSliderLabels('positivo')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-cyan-400 peer-checked:bg-cyan-400/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-code text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Productivo</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="relajado" class="sr-only peer" onchange="updateSliderLabels('neutral')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-500/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-mug-hot text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Relajado</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="ansioso" class="sr-only peer" onchange="updateSliderLabels('alerta')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-amber-400 peer-checked:bg-amber-400/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-bolt text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Ansioso</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="tristeza" class="sr-only peer" onchange="updateSliderLabels('baja')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-face-sad-tear text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Tristeza</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="melancolia" class="sr-only peer" onchange="updateSliderLabels('baja')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-purple-500 peer-checked:bg-purple-500/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-cloud-showers-water text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Melancolía</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="agotado" class="sr-only peer" onchange="updateSliderLabels('baja')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-gray-500 peer-checked:bg-gray-500/20 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-battery-empty text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Agotado</span>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="emocion" value="ira" class="sr-only peer" onchange="updateSliderLabels('alerta')">
                        <div class="bg-black/40 border border-white/10 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-all duration-300 peer-checked:border-neon-rose peer-checked:bg-neon-rose/10 group-hover:border-white/20 h-22">
                            <i class="fa-solid fa-fire-flame-curved text-lg text-gray-500 group-hover:text-white transition-colors"></i>
                            <span class="font-orbitron text-[9px] font-black uppercase tracking-wider mt-1.5 block adaptive-title">Ira</span>
                        </div>
                    </label>

                </div>
            </div>

            {{-- SLIDER DE INTENSIDAD RECONFIGURABLE CON CONTEX-COPY --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                <div class="md:col-span-2 bg-black/20 p-5 rounded-2xl border border-white/5 shadow-inner">
                    <div class="flex justify-between items-center mb-2">
                        <label id="slider-title-label" class="text-[13px] text-gray-500 uppercase tracking-[0.2em] font-black">2. Amplitud de la Señal (Intensidad)</label>
                        <span id="energy-val-display" class="text-xl font-black text-neon-cyan italic font-orbitron drop-shadow-[0_0_10px_rgba(34,211,238,0.5)]">50%</span>
                    </div>
                    <input type="range" name="energia" id="energy-input-slider" min="1" max="100" value="50"
                           class="w-full h-2 bg-white/5 rounded-lg appearance-none cursor-pointer accent-neon-cyan transition-all"
                           oninput="document.getElementById('energy-val-display').innerText = this.value + '%'">

                    {{-- Micro-copys dinámicos de escala --}}
                    <div class="flex justify-between text-[8px] text-gray-600 uppercase font-black tracking-widest mt-2 px-1">
                        <span id="slider-min-label">Impacto Mínimo</span>
                        <span id="slider-max-label">Amplitud Crítica</span>
                    </div>
                </div>

                <div class="bg-black/20 p-5 rounded-2xl border border-white/5 h-[98px]">
                    <label class="text-[13px] text-gray-500 block mb-2 uppercase tracking-[0.2em] font-black">3. Entorno de Actividad</label>
                    <select name="contexto" class="w-full bg-black/80 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:border-neon-cyan outline-none font-bold text-xs cursor-pointer">
                        <option value="General">🌐 Ámbito General</option>
                        <option value="Exámenes">📝 Periodo de Exámenes</option>
                        <option value="Proyecto">💻 Desarrollo de Proyecto</option>
                        <option value="Clases">🏫 Horario de Clases</option>
                    </select>
                </div>
            </div>

            {{-- TEXTO NLP --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-3">
                    <label class="text-[13px] text-gray-500 block mb-2 uppercase tracking-[0.2em] font-black">4. Bitácora Cognitiva (Observaciones analizadas por el Diccionario Neural)</label>
                    <input type="text" name="observaciones" placeholder="Ej: Presión extrema por el examen difícil del TESVB y ocupo entregar el código hoy..."
                           class="w-full bg-black/60 border border-white/10 rounded-xl px-5 py-3.5 text-xs text-white placeholder-gray-600 focus:border-neon-cyan outline-none font-medium">
                </div>
                <div class="md:col-span-1">
                    <button type="submit" class="w-full bg-neon-cyan/10 border border-neon-cyan text-neon-cyan py-3.5 rounded-xl font-black uppercase text-xs tracking-[0.3em] hover:bg-neon-cyan hover:text-black transition-all shadow-[0_0_25px_rgba(34,211,238,0.1)] active:scale-95">
                        Sincronizar
                    </button>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // MOTOR JAVASCRIPT: Reconfiguración semántica del Deslizador en Caliente
    function updateSliderLabels(type) {
        const title = document.getElementById('slider-title-label');
        const minL = document.getElementById('slider-min-label');
        const maxL = document.getElementById('slider-max-label');

        if (type === 'alerta') {
            title.innerText = "2. Grado de Alerta / Sobrecarga Cognitiva";
            minL.innerText = "Sintomatología Leve";
            maxL.innerText = "Crisis / Descontrol Total";
        } else if (type === 'baja') {
            title.innerText = "2. Nivel de Atenuación / Bloqueo Afectivo";
            minL.innerText = "Desgano Pasajero";
            maxL.innerText = "Apatía Absoluta / Melancolía Severa";
        } else if (type === 'positivo') {
            title.innerText = "2. Fuerza del Impulso / Nivel de Enfoque";
            minL.innerText = "Optimismo Sutil";
            maxL.innerText = "Plena Fluidez / Estado de Flujo (Flow)";
        } else {
            title.innerText = "2. Amplitud de Estabilización / Contención";
            minL.innerText = "Calma Inicial";
            maxL.innerText = "Relajación Profunda";
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('emocionChart');
        if (ctx) {
            const labels = {!! json_encode(auth()->user()->emociones()->latest()->take(7)->get()->pluck('created_at')->map->format('d/m')->reverse()->values()) !!};
            const data = {!! json_encode(auth()->user()->emociones()->latest()->take(7)->get()->pluck('energia')->reverse()->values()) !!};

            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Intensidad %',
                        data: data,
                        borderColor: '#22D3EE',
                        backgroundColor: 'rgba(34, 211, 238, 0.05)',
                        borderWidth: 4,
                        pointBackgroundColor: '#22D3EE',
                        pointBorderColor: '#030712',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false }, ticks: { color: '#4b5563', font: { size: 10, family: 'Orbitron' } } },
                        x: { grid: { display: false }, ticks: { color: '#4b5563', font: { size: 10, family: 'Orbitron' } } }
                    }
                }
            });
        }
    });
</script>
@endpush
