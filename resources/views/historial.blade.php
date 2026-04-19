@extends('layouts.app')

@section('title', 'Start-Emotion | Historial')

@section('content')
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black uppercase italic tracking-tighter">
                Registro de <span class="text-neon-cyan">Actividad Emocional</span>
            </h1>
            <p class="text-gray-500 text-[10px] uppercase tracking-[0.4em] mt-1">Logs de sistema // Memoria del usuario</p>
        </div>

        {{-- BOTÓN 1: PURGA TOTAL --}}
        <form action="{{ route('emociones.reiniciar') }}" method="POST" onsubmit="return confirm('¿⚠️ ATENCIÓN: Estás a punto de borrar TODO tu historial. ¿Proceder?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500/10 border border-red-500 text-red-500 px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all shadow-[0_0_15px_rgba(239,68,68,0.2)]">
                Reiniciar Historial
            </button>
        </form>
            <a href="{{ route('emociones.reporte') }}" class="bg-neon-cyan/10 border border-neon-cyan text-neon-cyan px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-neon-cyan hover:text-black transition-all">
            Exportar PDF
            </a>
    </div>

    {{-- FORMULARIO PARA ELIMINACIÓN SELECCIONADA --}}
    <form action="{{ route('emociones.eliminarSeleccionados') }}" method="POST" id="bulk-delete-form">
        @csrf
        <div class="mb-6 flex items-center justify-between bg-white/5 p-4 rounded-2xl border border-white/5 backdrop-blur-sm">
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox" id="select-all" class="w-5 h-5 rounded-lg border-white/10 bg-black/40 text-neon-purple focus:ring-neon-purple transition-all cursor-pointer">
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest group-hover:text-neon-cyan transition-colors">Seleccionar Todo</span>
            </label>

            <button type="submit" id="delete-selected-btn" disabled class="bg-neon-purple/20 border border-neon-purple text-neon-purple px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest opacity-50 cursor-not-allowed hover:bg-neon-purple hover:text-white transition-all">
                Eliminar Seleccionados
            </button>
        </div>

        <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5 backdrop-blur-md shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white/10 text-[10px] tracking-[0.4em] text-gray-400 uppercase">
                    <tr>
                        <th class="px-6 py-5 w-10"></th>
                        <th class="px-6 py-5">Fecha / Hora</th>
                        <th class="px-6 py-5">Estado</th>
                        <th class="px-6 py-5">Nivel de Energía</th>
                        <th class="px-6 py-5">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
                    @forelse($historial as $item)
                    <tr class="hover:bg-white/[0.02] transition-colors group">
                        <td class="px-6 py-4">
                            <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="record-checkbox w-5 h-5 rounded-lg border-white/10 bg-black/40 text-neon-purple focus:ring-neon-purple transition-all cursor-pointer">
                        </td>
                        <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 font-bold uppercase italic tracking-wider {{ $item->getColor() }}">{{ $item->emocion }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 bg-white/10 h-1.5 rounded-full overflow-hidden shadow-[0_0_5px_rgba(34,211,238,0.1)]">
                                    <div class="bg-neon-cyan h-full shadow-[0_0_10px_#22D3EE]" style="width: {{ $item->energia }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-neon-cyan tracking-tighter w-8 text-right">{{ $item->energia }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <button type="button" onclick="confirmDeleteIndividual({{ $item->id }})" class="text-neon-rose text-[9px] uppercase tracking-[0.2em] font-black hover:bg-neon-rose/10 px-3 py-1 rounded-lg border border-transparent hover:border-neon-rose/30 transition-all">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <p class="text-gray-500 text-[10px] uppercase tracking-[0.5em]">Sector de memoria vacío // Sin registros</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Formulario oculto para eliminar uno solo --}}
    <form id="single-delete-form" action="" method="POST" style="display:none;">
        @csrf @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.record-checkbox');
        const deleteBtn = document.getElementById('delete-selected-btn');

        // Lógica para activar/desactivar el botón de borrar seleccionados
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

        // Seleccionar todo
        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                toggleDeleteButton();
            });
        }

        // Al cambiar cualquier checkbox individual
        checkboxes.forEach(cb => {
            cb.addEventListener('change', toggleDeleteButton);
        });
    });

    // Función para eliminar uno solo usando el formulario oculto
    function confirmDeleteIndividual(id) {
        if(confirm('¿Confirmas la eliminación de este registro?')) {
            const form = document.getElementById('single-delete-form');
            form.action = `/emociones/${id}`;
            form.submit();
        }
    }
</script>
@endpush
