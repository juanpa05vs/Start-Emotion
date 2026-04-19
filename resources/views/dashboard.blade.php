@extends('layouts.app')

@section('title', 'Start-Emotion | Dashboard')

@section('content')
    {{-- 1. NOTIFICACIÓN DE ESTADO (ESTILO NEÓN) --}}
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

    {{-- 2. BIO-HUD (LIVE STATUS) --}}
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

    {{-- ENCABEZADO CON BOTÓN DE EXPORTACIÓN --}}
    <header class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
        <div>
            <h1 class="text-4xl font-black uppercase italic tracking-tight">
                Bienvenido, <span class="text-neon-purple">{{ auth()->user()->nombre }}</span>
            </h1>
            <p class="text-gray-500 text-[10px] mt-1 uppercase tracking-[0.4em]"> SISTEMA OPERATIVO ONLINE
            </p>
        </div>

        {{-- BOTÓN EXPORTAR PDF (IDEA 4) --}}
        <a href="{{ route('emociones.reporte') }}" class="group flex items-center gap-3 bg-white/5 border border-white/10 px-6 py-3 rounded-xl hover:bg-neon-cyan hover:text-black transition-all duration-500">
            <i class="fa-solid fa-file-pdf text-neon-cyan group-hover:text-black transition-colors"></i>
            <span class="text-[10px] font-black uppercase tracking-widest">Generar Reporte Bio-Sync</span>
        </a>
    </header>

    {{-- CARDS PRINCIPALES (IDEA 1: GLASSMORPHISM) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Card: Emoción --}}
        <div class="bg-black/40 backdrop-blur-xl border border-white/10 p-6 rounded-[2rem] shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-neon-purple/10 blur-2xl rounded-full group-hover:bg-neon-purple/20 transition-all"></div>
            <p class="text-gray-500 text-[9px] mb-2 uppercase tracking-[0.3em] font-black">Última Detección</p>
            <h3 class="text-2xl font-black italic uppercase {{ $ultimoRegistro ? $ultimoRegistro->getColor() : 'text-white' }}">
                {{ $ultimoRegistro->emocion ?? 'SIN DATOS' }}
            </h3>
        </div>

        {{-- Card: Energía --}}
        <div class="bg-black/40 backdrop-blur-xl border border-white/10 p-6 rounded-[2rem] shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-neon-cyan/10 blur-2xl rounded-full group-hover:bg-neon-cyan/20 transition-all"></div>
            <p class="text-gray-500 text-[9px] mb-2 uppercase tracking-[0.3em] font-black">Nivel de Energía</p>
            <h3 class="text-3xl font-black italic text-neon-cyan drop-shadow-[0_0_10px_rgba(34,211,238,0.4)]">
                {{ $ultimoRegistro ? $ultimoRegistro->energia . '%' : '0%' }}
            </h3>
        </div>

        {{-- Card: Estrés Calculado (IDEA 3: LÓGICA IA) --}}
        <div class="bg-black/40 backdrop-blur-xl border border-white/10 p-6 rounded-[2rem] shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-neon-rose/10 blur-2xl rounded-full group-hover:bg-neon-rose/20 transition-all"></div>
            <p class="text-gray-500 text-[9px] mb-2 uppercase tracking-[0.3em] font-black">Estrés Estimado</p>
            <h3 class="text-2xl font-black italic text-neon-rose uppercase">
                {{ $ultimoRegistro ? $ultimoRegistro->nivel_estres_estimado . '%' : '---' }}
            </h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        {{-- Gráfica de Tendencia --}}
        <section class="lg:col-span-2 bg-black/40 border border-white/10 p-8 rounded-[2.5rem] backdrop-blur-xl shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-6">
                <i class="fa-solid fa-chart-line text-neon-cyan/20 text-4xl"></i>
            </div>
            <div class="flex justify-between items-center mb-10">
                <h2 class="text-gray-400 text-[10px] uppercase tracking-[0.4em] font-black">Tendencia de Energía</h2>
                <span class="text-[8px] text-neon-cyan/50 uppercase tracking-widest italic font-bold">Monitor Longitudinal</span>
            </div>
            <div class="h-72">
                <canvas id="emocionChart"></canvas>
            </div>
        </section>

        {{-- Motor de Recomendaciones IA --}}
        <section class="bg-black/40 border border-white/10 p-8 rounded-[2.5rem] backdrop-blur-xl flex flex-col items-center justify-center text-center shadow-2xl relative">
            <div class="absolute inset-0 bg-gradient-to-b from-neon-purple/5 to-transparent pointer-events-none"></div>
            <div class="relative mb-8">
                <div class="absolute inset-0 bg-neon-purple/20 blur-[40px] rounded-full animate-pulse"></div>
                <div class="relative w-36 h-36 rounded-full border-2 border-dashed border-neon-purple/30 flex items-center justify-center bg-black/60 shadow-[0_0_30px_rgba(168,85,247,0.1)]">
                    <span class="text-6xl filter drop-shadow-[0_0_15px_rgba(168,85,247,0.8)]">
                        @if($ultimoRegistro)
                            @if($ultimoRegistro->emocion == 'Agotado') 🔋 @elseif($ultimoRegistro->emocion == 'Entusiasta') 🚀 @elseif($ultimoRegistro->emocion == 'Ansioso') ⚡ @elseif($ultimoRegistro->emocion == 'Productivo') 💻 @else 🤖 @endif
                        @else 🦦 @endif
                    </span>
                </div>
            </div>
            <h3 class="text-neon-purple text-[10px] uppercase tracking-[0.5em] font-black mb-4">Análisis Neural</h3>
            <p class="text-[11px] text-gray-300 leading-relaxed italic px-6 font-medium">
                "{{ $ultimoRegistro ? $ultimoRegistro->recomendacion : 'Sincroniza el sistema para iniciar el análisis conductual.' }}"
            </p>
        </section>
    </div>

    {{-- Captura de Sesión --}}
    <section class="bg-black/40 border border-white/10 p-10 rounded-[2.5rem] backdrop-blur-xl shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-neon-cyan to-transparent opacity-30"></div>
        <h2 class="text-neon-cyan text-[10px] tracking-[0.5em] font-black uppercase mb-10 text-center">Protocolo de Entrada de Datos</h2>

        <form action="{{ route('emociones.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 items-end gap-10">
            @csrf
            <div class="md:col-span-1">
                <label class="text-[9px] text-gray-500 block mb-4 uppercase tracking-[0.2em] font-black">Estado Percibido</label>
                <select name="emocion" class="w-full bg-black/80 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-neon-cyan outline-none transition-all appearance-none cursor-pointer font-bold text-xs">
                    <option value="Entusiasta">🚀 Entusiasta</option>
                    <option value="Productivo">💻 Productivo</option>
                    <option value="Agotado">🔋 Agotado</option>
                    <option value="Relajado">☕ Relajado</option>
                    <option value="Ansioso">⚡ Ansioso</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <div class="flex justify-between items-center mb-4">
                    <label class="text-[9px] text-gray-500 uppercase tracking-[0.2em] font-black">Nivel de Energía Vital</label>
                    <span id="energy-val-display" class="text-xl font-black text-neon-cyan italic drop-shadow-[0_0_10px_rgba(34,211,238,0.6)] font-orbitron">
                        50%
                    </span>
                </div>
                <input type="range"
                       name="energia"
                       id="energy-input-slider"
                       min="1"
                       max="100"
                       value="50"
                       class="w-full h-2 bg-white/5 rounded-lg appearance-none cursor-pointer accent-neon-cyan hover:shadow-[0_0_20px_rgba(34,211,238,0.2)] transition-all"
                       oninput="document.getElementById('energy-val-display').innerText = this.value + '%'">
            </div>

            <div class="md:col-span-1">
                <button type="submit" class="w-full bg-neon-cyan/10 border border-neon-cyan text-neon-cyan py-5 rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] hover:bg-neon-cyan hover:text-black transition-all shadow-[0_0_25px_rgba(34,211,238,0.1)] active:scale-95">
                    Sincronizar
                </button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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
                        label: 'Energía %',
                        data: data,
                        borderColor: '#22D3EE',
                        backgroundColor: 'rgba(34, 211, 238, 0.05)',
                        borderWidth: 4,
                        pointBackgroundColor: '#22D3EE',
                        pointBorderColor: '#030712',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                            ticks: { color: '#4b5563', font: { size: 10, family: 'Orbitron' } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#4b5563', font: { size: 10, family: 'Orbitron' } }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
