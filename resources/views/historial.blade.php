@extends('layouts.app')

@section('title', 'Historial | S-Emotion')

@section('content')
    <div class="mb-7 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">
                <span class="text-accent-text">Historial</span> de registros
            </h1>
            <p class="mt-1 text-[13px] leading-relaxed text-gray-500">
                Cada anotación que has hecho, con su emoción, su intensidad y la nota que dejaste.
            </p>
        </div>

        {{-- «Reiniciar» borra TODO el historial sin excepción. Por eso lleva el
             contorno rojo y la advertencia: no es un botón más de la cabecera,
             es la acción que se pierde si se pulsa sin querer. El aviso repite
             el alcance en el `confirm` para que la consequence sea explícita
             antes de aceptar, no después. --}}
        <div class="flex w-full flex-wrap items-center gap-2 md:w-auto">
            <form action="{{ route('emociones.reiniciar') }}" method="POST" class="w-full sm:w-auto"
                  onsubmit="return confirm('Vas a borrar TODAS tus anotaciones, sin dejar ninguna. Esto no se puede deshacer. ¿Continuar?')">
                @csrf @method('DELETE')
                <x-boton-peligro class="w-full sm:w-auto" icono="trash-can">
                    Borrar todo
                </x-boton-peligro>
            </form>

            {{-- Antes era un enlace con toda la apariencia de un botón escrita a mano:
                 mismo relleno, mismo filete, misma tipografía en minúsculas, y
                 por tanto el mismo mantenimiento que cualquier otro botón de la
                 aplicación. Ahora es un `x-boton-secundario` y hereda el
                 movimiento y el foco. --}}
            <x-boton-secundario
                :href="route('emociones.reporte')"
                class="w-full sm:w-auto"
                icono="file-arrow-down">
                Descargar informe
            </x-boton-secundario>
        </div>
    </div>

    <form action="{{ route('emociones.eliminarSeleccionados') }}" method="POST" id="bulk-delete-form">
        @csrf
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-panel border border-white/10 bg-black/30 px-4 py-3">
            <label class="group flex cursor-pointer select-none items-center gap-3">
                {{-- La casilla se pinta con el acento de la persona, no con el
                     morado de marca: es un control de formulario, no un dato. --}}
                <input type="checkbox" id="select-all"
                       class="h-4 w-4 shrink-0 rounded-control border-white/20 bg-black accent-accent">
                <span class="text-[12px] font-semibold text-gray-400 transition-colors group-hover:text-accent-text">
                    Seleccionar todo
                </span>
            </label>

            {{-- Cuenta de lo que hay marcado ahora mismo. «Borrar seleccionadas»
                 es una acción que borra N cosas: sin este número, quien pulsa
                 no sabe si va a borrar una anotación o veinte. --}}
            <p class="order-last w-full text-[11px] text-gray-500 md:order-none md:w-auto">
                <span id="seleccion-actual">0 seleccionadas</span>
            </p>

            {{-- `enabled:` en vez de cambiar la clase desde JS: el botón arranca
                 deshabilitado y solo cobra su aspecto real cuando hay algo
                 marcado, en vez de verse apagado y tener que adivinar por qué. --}}
            {{-- Sin el prop `inactivo`: ese prop también fija `pointer-events-none`
                 en la clase, y aquí el atributo lo quita y pone JavaScript según
                 cuántas casillas estén marcadas. `disabled` a secas sí, para que
                 el botón nazca apagado. El aspecto deshabilitado lo aporta
                 `.btn:disabled` en `app.css`. --}}
            <x-boton-peligro type="submit" id="delete-selected-btn" disabled icono="trash-can">
                Borrar seleccionadas
            </x-boton-peligro>
        </div>

        {{-- La tabla tiene ancho mínimo, así que el contenedor es el que desplaza
             en pantallas estrechas. --}}
        <div class="overflow-x-auto rounded-panel border border-white/10 bg-black/40">
            <table class="w-full min-w-[700px] border-collapse text-left">
                <thead class="border-b border-white/10 bg-white/[0.03]">
                    <tr>
                        <th scope="col" class="w-12 px-6 py-3.5 text-center">
                            <span class="sr-only">Seleccionar</span>
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Fecha y hora</th>
                        <th scope="col" class="px-6 py-3.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Emoción</th>
                        <th scope="col" class="px-6 py-3.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Intensidad</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
                    @forelse($historial as $item)
                        @php
                            /* Color de la emoción. Aquí el color NO es decoración:
                               es la información que se está leyendo. Por eso estas
                               clases no pasan por el token de acento — si «felicidad»
                               tomara el color del tema, dos lecturas distintas
                               representarían la misma emoción y un histórico ya
                               impreso dejaría de poder compararse.

                               El esquema está completo por intención. Cian para
                               energía alta, verde para calma, ámbar para tensión,
                               azul para tristeza, gris para cansancio. No se
                               usan rojo y verde juntos como único par de
                               «bien/mal»: se distinguen por tono y por posición,
                               no solo por matiz, y quien no distinga los colores
                               sigue leyendo bien las palabras. */
                            $badgeStyle = [
                                'felicidad'   => 'border-cyan-500/30 text-cyan-300 bg-cyan-500/5',
                                'entusiasta'  => 'border-cyan-500/30 text-cyan-300 bg-cyan-500/5',
                                'productivo'  => 'border-cyan-500/30 text-cyan-300 bg-cyan-500/5',
                                'relajado'    => 'border-emerald-500/30 text-emerald-400 bg-emerald-500/5',
                                'ansioso'     => 'border-amber-500/30 text-amber-400 bg-amber-500/5',
                                'tristeza'    => 'border-blue-500/30 text-blue-400 bg-blue-500/5',
                                'melancolia'  => 'border-purple-500/30 text-purple-400 bg-purple-500/5',
                                'agotado'     => 'border-gray-500/30 text-gray-400 bg-gray-500/5',
                                'ira'         => 'border-neon-rose/30 text-neon-rose bg-neon-rose/10',
                            ][$item->emocion] ?? 'border-white/10 text-gray-300 bg-white/5';

                            $barColor = [
                                'felicidad', 'entusiasta', 'productivo' => 'bg-cyan-400',
                                'relajado'   => 'bg-emerald-400',
                                'ansioso'    => 'bg-amber-400',
                                'tristeza'   => 'bg-blue-400',
                                'melancolia' => 'bg-purple-400',
                                'agotado'    => 'bg-gray-400',
                                'ira'        => 'bg-neon-rose',
                            ][$item->emocion] ?? 'bg-gray-500';
                        @endphp

                        {{-- FILA PRINCIPAL DE LOGS --}}
                        <tr class="group transition-colors hover:bg-white/[0.02]">
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                                       class="record-checkbox h-4 w-4 shrink-0 rounded-control border-white/20 bg-black accent-accent">
                            </td>

                            <td class="px-6 py-4">
                                <span class="block text-[13px] text-gray-300">{{ $item->created_at->translatedFormat('j \d\e F') }}</span>
                                <span class="mt-0.5 block font-mono text-[11px] text-gray-500">{{ $item->created_at->format('H:i') }}</span>
                            </td>

                            <td class="px-6 py-4">
                                {{-- La etiqueta lleva ICONO además del color: el tono es la
                                     información, pero quien no distinga los colores
                                     tiene que poder leer la palabra. --}}
                                <span class="inline-flex items-center gap-1.5 rounded-control border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $badgeStyle }}">
                                    {{ $item->emocion }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex max-w-[14rem] items-center gap-3">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-pill border border-white/5 bg-white/5">
                                        <div class="h-full {{ $barColor }}" style="width: {{ $item->energia }}%"></div>
                                    </div>
                                    <span class="w-11 text-right text-[12px] tabular-nums text-gray-400">{{ $item->energia }}%</span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                {{-- Un icono con `aria-expanded` dice si el detalle está
                                     abierto o cerrado. El texto entre corchetes
                                     («[ Telemetría ]») no lo decía: era decorado de
                                     terminal, no un estado. --}}
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            onclick="toggleTelemetryPayload({{ $item->id }})"
                                            aria-expanded="false"
                                            aria-controls="payload-row-{{ $item->id }}"
                                            class="inline-flex items-center gap-1.5 rounded-control border border-transparent px-2.5 py-1.5 text-[11px] font-semibold text-gray-400 transition-colors hover:border-accent/30 hover:bg-accent/10 hover:text-accent-text">
                                        <i class="fa-solid fa-chevron-down text-[9px]" aria-hidden="true"></i>
                                        Detalle
                                    </button>

                                    <button type="button" onclick="confirmDeleteIndividual({{ $item->id }})"
                                            aria-label="Borrar esta anotación"
                                            class="inline-flex items-center gap-1.5 rounded-control border border-transparent px-2.5 py-1.5 text-[11px] font-semibold text-neon-rose transition-colors hover:border-neon-rose/30 hover:bg-neon-rose/10">
                                        <i class="fa-solid fa-trash-can text-[10px]" aria-hidden="true"></i>
                                        Borrar
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Detalle desplegable: contexto, nota y lectura. --}}
                        <tr id="payload-row-{{ $item->id }}" class="hidden bg-black/60">
                            <td colspan="5" class="px-6 py-5">
                                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                                    <div class="rounded-control border border-white/5 bg-white/[0.02] p-3.5">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">
                                            Contexto
                                        </p>
                                        <p class="flex items-center gap-2 text-[13px] text-white">
                                            <i class="fa-solid fa-cube text-[10px] text-accent-text" aria-hidden="true"></i>
                                            {{ $item->contexto ?? 'Sin especificar' }}
                                        </p>
                                    </div>

                                    <div class="rounded-control border border-white/5 bg-white/[0.02] p-3.5 md:col-span-2">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">
                                            Nota
                                        </p>
                                        <p class="text-[13px] leading-relaxed text-gray-300">
                                            {{ $item->observaciones ?? 'No dejaste ninguna nota en esta anotación.' }}
                                        </p>
                                    </div>

                                    <div class="rounded-control border border-accent/15 bg-accent-soft p-3.5 md:col-span-3">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-accent-text">
                                            Lectura de este estado
                                        </p>
                                        <p class="text-[13px] leading-relaxed text-accent-text">
                                            {{ $item->recomendacion }}
                                        </p>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Estado vacío. No es decorativo: es la respuesta a
                             «¿por qué no hay nada aquí?». Antes eran dos líneas
                             de texto suelto dentro de una celda, sin icono ni
                             siguiente paso, y no se distinguía de un error. --}}
                        <tr>
                            <td colspan="5" class="px-6 py-4">
                                <x-estado-vacio
                                    icono="fa-feather-pointed"
                                    titulo="Todavía no hay registros"
                                    mensaje="Cuando anotes cómo te has sentido, aparecerá aquí con su fecha y su nivel de energía."
                                    accion="Registrar cómo me siento"
                                    :url="route('dashboard')" />
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
    /* Despliega el detalle de una anotación.

       El botón lleva `aria-expanded` y la flecha gira, para que se sepa si el
       detalle está abierto o cerrado sin leer el texto. Antes el rótulo era
       siempre el mismo —da igual si estaba abierto o cerrado— y quien abría la
       fila no recibía ninguna confirmación. */
    function toggleTelemetryPayload(id) {
        const fila = document.getElementById(`payload-row-${id}`);
        if (!fila) return;

        const boton = document.querySelector(`[aria-controls="payload-row-${id}"]`);
        const abierta = fila.classList.toggle('hidden') === false;

        if (boton) {
            boton.setAttribute('aria-expanded', String(abierta));
            boton.querySelector('i').style.transform = abierta ? 'rotate(180deg)' : '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all');
        const checkboxes = [...document.querySelectorAll('.record-checkbox')];
        const deleteBtn = document.getElementById('delete-selected-btn');

        /* El botón se habilita y deshabilita con el atributo `disabled`, y su
           aspecto sale de `.btn:disabled` en `app.css`. No hace falta que
           JavaScript le quite y poner clases a mano: si el CSS y el atributo se
           desincronizan, el botón aparenta estar disponible cuando no lo está. */
        const sincronizarBorrado = () => {
            const marcadas = checkboxes.filter(c => c.checked).length;
            deleteBtn.disabled = marcadas === 0;

            const spoken = document.getElementById('seleccion-actual');
            if (spoken) spoken.textContent = marcadas === 1 ? '1 seleccionada' : `${marcadas} seleccionadas`;
        };

        selectAll?.addEventListener('change', function () {
            checkboxes.forEach(c => { c.checked = this.checked; });
            sincronizarBorrado();
        });

        checkboxes.forEach(c => c.addEventListener('change', sincronizarBorrado));

        /* Al abrir la tabla no hay nada marcado, así que se deja explícito en
           lugar de depender del estado inicial del botón. */
        sincronizarBorrado();
    });

    function confirmDeleteIndividual(id) {
        if (confirm('¿Borrar esta anotación? No se puede deshacer.')) {
            document.getElementById('single-delete-form').action = '{{ url('/emociones') }}/' + id;
            document.getElementById('single-delete-form').submit();
        }
    }
</script>
@endpush
