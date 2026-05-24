@extends('layouts.app')

@section('title', 'Start-Emotion | Historial')

@section('content')
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black uppercase italic tracking-tighter adaptive-title">
                Registro de <span class="text-accent">Actividad Emocional</span>
            </h1>
            <p class="text-gray-500 text-[10px] uppercase tracking-[0.4em] mt-1">Logs de sistema // Memoria del usuario</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <form action="{{ route('emociones.reiniciar') }}" method="POST" onsubmit="return confirm('¿⚠️ ATENCIÓN: Estás a punto de borrar TODO tu historial. ¿Proceder?') " class="w-full sm:w-auto">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-neon-rose/10 border border-neon-rose text-neon-rose px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-neon-rose hover:text-white transition-all shadow-[0_0_15px_rgba(244,63,94,0.2)]">
                    Reiniciar Historial
                </button>
            </form>
            <a href="{{ route('emociones.reporte') }}" class="w-full sm:w-auto text-center bg-white/5 border border-white/10 text-gray-400 px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-neon-cyan hover:text-black hover:border-neon-cyan transition-all">
                <i class="fa-solid fa-file-pdf mr-1.5"></i> Exportar PDF
            </a>
        </div>
    </div>

    <form action="{{ route('emociones.eliminarSeleccionados') }}" method="POST" id="bulk-delete-form">
        @csrf
        <div class="mb-6 flex items-center justify-between bg-black/40 p-4 rounded-2xl border border-white/5 backdrop-blur-sm shadow-md">
            <label class="flex items-center gap-3 cursor-pointer group select-none">
                <input type="checkbox" id="select-all" class="w-5 h-5 rounded-lg border-white/10 bg-black/40 text-neon-purple focus:ring-neon-purple transition-all">
                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest group-hover:text-neon-cyan">Seleccionar Todo el Sector</span>
            </label>
            <button type="submit" id="delete-selected-btn" disabled class="bg-neon-purple/20 border border-neon-purple text-neon-purple px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest opacity-50 cursor-not-allowed hover:bg-neon-purple hover:text-white transition-all">
                Purgar Selección
            </button>
        </div>

        <div class="overflow-hidden rounded-3xl border border-white/10 bg-black/40 backdrop-blur-md shadow-2xl overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead class="bg-white/5 text-[10px] tracking-[0.4em] text-gray-500 uppercase font-black border-b border-white/5">
                    <tr>
                        <th class="px-6 py-5 w-12 text-center"></th>
                        <th class="px-6 py-5">Fecha / Hora</th>
                        <th class="px-6 py-5">Foco del Núcleo</th>
                        <th class="px-6 py-5">Intensidad / Amplitud</th>
                        <th class="px-6 py-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
                    @forelse($historial as $item)
                        @php
                            $badgeStyle = [
                                'felicidad'   => 'border-cyan-500/30 text-cyan-400 bg-cyan-500/5',
                                'entusiasta'  => 'border-cyan-400/30 text-cyan-300 bg-cyan-400/5',
                                'productivo'  => 'border-cyan-600/40 text-cyan-200 bg-cyan-600/5',
                                'relajado'    => 'border-emerald-500/30 text-emerald-400 bg-emerald-500/5',
                                'ansioso'     => 'border-amber-500/30 text-amber-400 bg-amber-500/5',
                                'tristeza'    => 'border-blue-500/30 text-blue-400 bg-blue-500/5',
                                'melancolia'  => 'border-purple-500/30 text-purple-400 bg-purple-500/5',
                                'agotado'     => 'border-gray-500/30 text-gray-400 bg-gray-500/5',
                                'ira'         => 'border-neon-rose/30 text-neon-rose bg-neon-rose/10',
                            ][$item->emocion] ?? 'border-white/10 text-white bg-white/5';

                            $barColor = [
                                'felicidad', 'entusiasta', 'productivo' => 'bg-neon-cyan shadow-[0_0_10px_#22D3EE]',
                                'relajado'   => 'bg-emerald-500 shadow-[0_0_10px_#10B981]',
                                'ansioso'    => 'bg-amber-400 shadow-[0_0_10px_#FBBF24]',
                                'tristeza'   => 'bg-blue-500 shadow-[0_0_10px_#3B82F6]',
                                'melancolia' => 'bg-neon-purple shadow-[0_0_10px_#A855F7]',
                                'agotado'    => 'bg-gray-500 shadow-[0_0_10px_#6B7280]',
                                'ira'        => 'bg-neon-rose shadow-[0_0_10px_#F43F5E]',
                            ][$item->emocion] ?? 'bg-neon-cyan';
                        @endphp

                        {{-- FILA PRINCIPAL DE LOGS --}}
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="px-6 py-5 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="record-checkbox w-5 h-5 rounded-lg border-white/10 bg-black/40 text-neon-purple focus:ring-neon-purple transition-all">
                            </td>
                            <td class="px-6 py-5 text-gray-500 font-mono text-xs">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border font-orbitron inline-flex items-center gap-1.5 {{ $badgeStyle }}">
                                    {{ $item->emocion }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center space-x-4 max-w-xs">
                                    <div class="flex-1 bg-white/5 h-1.5 rounded-full overflow-hidden border border-white/5">
                                        <div class="h-full {{ $barColor }}" style="width: {{ $item->energia }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-black tracking-widest font-orbitron w-10 text-right text-gray-400">{{ $item->energia }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-right flex justify-end gap-2">
                                {{-- [NUEVO] BOTÓN DESPLEGABLE INTERACTIVO --}}
                                <button type="button" onclick="toggleTelemetryPayload({{ $item->id }})" class="text-neon-cyan text-[9px] uppercase tracking-[0.2em] font-black hover:bg-neon-cyan/10 px-3 py-1.5 rounded-xl border border-transparent hover:border-neon-cyan/20 transition-all">
                                    [ Telemetría ]
                                </button>
                                <button type="button" onclick="confirmDeleteIndividual({{ $item->id }})" class="text-neon-rose text-[9px] uppercase tracking-[0.2em] font-black hover:bg-neon-rose/10 px-3 py-1.5 rounded-xl border border-transparent hover:border-neon-rose/20 transition-all">
                                    [ Purgar ]
                                </button>
                            </td>
                        </tr>

                        {{-- [NUEVO] SUB-PANEL OCULTO (CONTENEDOR DE ENTORNO Y BITÁCORA COGNITIVA) --}}
                        <tr id="payload-row-{{ $item->id }}" class="hidden bg-black/60 border-l-2 border-accent transition-all">
                            <td colspan="5" class="px-8 py-5">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 font-medium">

                                    {{-- Visualizador de Entorno --}}
                                    <div class="p-3 bg-white/[0.02] border border-white/5 rounded-xl">
                                        <span class="text-[7px] font-black text-neon-purple tracking-wider uppercase block mb-1 font-orbitron">[ SUB_ENV // ENTORNO DETECTADO ]</span>
                                        <p class="text-xs text-white flex items-center gap-2">
                                            <i class="fa-solid fa-cube text-neon-purple text-[10px]"></i>
                                            {{ $item->contexto ?? 'Ámbito General / Sin Especificar' }}
                                        </p>
                                    </div>

                                    {{-- Visualizador de Bitácora Cognitiva (NLP) --}}
                                    <div class="md:col-span-2 p-3 bg-white/[0.02] border border-white/5 rounded-xl">
                                        <span class="text-[7px] font-black text-neon-cyan tracking-wider uppercase block mb-1 font-orbitron">[ COGNITIVE_LOG // BITÁCORA DE TEXTO ]</span>
                                        <p class="text-xs text-gray-300 italic leading-relaxed">
                                            "{{ $item->observaciones ?? 'No se registraron notas de pensamiento en esta sincronización.' }}"
                                        </p>
                                    </div>

                                    {{-- Extra: Recomendación Ensamblada Completa --}}
                                    <div class="md:col-span-3 p-3 bg-accent-soft border border-accent/10 rounded-xl">
                                        <span class="text-[7px] font-black text-accent tracking-wider uppercase block mb-1 font-orbitron">[ NEURAL_OUTPUT // RESOLUCIÓN RECOMENDADA ]</span>
                                        <p class="text-xs text-accent font-sans leading-relaxed">
                                            {{ $item->recomendacion }}
                                        </p>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <p class="text-gray-500 text-[10px] uppercase tracking-[0.5em] font-black">Sector de memoria vacío // Sin registros de telemetría</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <form id="single-delete-form" action="" method="POST" style="display:none;">
        @csrf @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
    // MOTOR JAVASCRIPT: Despliegue dinámico de cargas de datos de sub-fila
    function toggleTelemetryPayload(id) {
        const payloadRow = document.getElementById(`payload-row-${id}`);
        if(payloadRow) {
            payloadRow.classList.toggle('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.record-checkbox');
        const deleteBtn = document.getElementById('delete-selected-btn');

        function toggleDeleteButton() {
            const checkedCount = document.querySelectorAll('.record-checkbox:checked').length;
            if (checkedCount > 0) {
                deleteBtn.disabled = false;
                deleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                deleteBtn.disabled = true;
                deleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleDeleteButton();
            });
        }
        checkboxes.forEach(cb => cb.addEventListener('change', toggleDeleteButton));
    });

    function confirmDeleteIndividual(id) {
        if(confirm('¿Confirmas la remoción permanente de este registro?')) {
            const form = document.getElementById('single-delete-form');
            form.action = '/emociones/' + id;
            form.submit();
        }
    }
</script>
@endpush
