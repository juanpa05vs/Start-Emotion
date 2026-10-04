@extends('layouts.app')

@section('title', 'Cuentas | S-Emotion')

@section('content')
<div class="max-w-4xl">

    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Cuentas</h1>
            <p class="mt-1.5 text-[13px] text-gray-400">
                Administra el tipo de acceso de cada cuenta y da de baja las que ya no se usen.
            </p>
        </div>

        <p class="text-[12px] text-gray-500">
            <span class="font-mono text-[15px] text-accent-text">{{ $usuarios->count() }}</span>
            {{ \Illuminate\Support\Str::plural('cuenta', $usuarios->count()) }}
        </p>
    </div>

    @if ($usuarios->isEmpty())
        <div class="surface rounded-panel">
            <x-estado-vacio
                icono="fa-users"
                titulo="No hay cuentas registradas"
                mensaje="Cuando alguien cree una cuenta en la aplicación, aparecerá en esta lista." />
        </div>
    @else
        <div class="overflow-hidden rounded-panel border border-white/10 bg-black/40">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-white/10 bg-white/[0.03]">
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Cuenta</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Tipo de acceso</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/5">
                    @foreach ($usuarios as $user)
                        <tr class="transition-colors hover:bg-white/[0.02]">
                            {{-- Identidad de la cuenta. Sin edad y sin correo: no hacen falta
                                 para administrar cuentas y sí describen a la persona. --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-pill border border-white/10 bg-white/5 font-orbitron text-[12px] font-bold text-accent-text">
                                        {{ mb_strtoupper(mb_substr($user->nombre, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-[14px] font-semibold adaptive-title">{{ $user->nombre }}</p>
                                        <p class="mt-0.5 text-[11px] text-gray-500">
                                            Alta el {{ $user->created_at?->format('d/m/Y') }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- El tipo de acceso es el dato central de esta pantalla, así que el
                                 formulario de cambio va en la celda y no en un menú aparte. --}}
                            <td class="px-5 py-4">
                                <form action="{{ route('usuarios.updateRole', $user) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <label class="sr-only" for="rol-{{ $user->id }}">
                                        Tipo de acceso de {{ $user->nombre }}
                                    </label>
                                    <select id="rol-{{ $user->id }}"
                                            name="rol"
                                            onchange="this.form.submit()"
                                            class="campo w-full max-w-[13rem] cursor-pointer rounded-control border bg-black/40 px-3 py-2 text-[13px] text-gray-200 hover:border-white/25">
                                        <option value="estudiante"     {{ trim(strtolower($user->rol ?? '')) === 'estudiante' ? 'selected' : '' }}>Estudiante</option>
                                        <option value="Psicólogo"      {{ ($user->rol ?? '') === 'Psicólogo' ? 'selected' : '' }}>Psicólogo</option>
                                        <option value="Administrador"  {{ trim(strtolower($user->rol ?? '')) === 'administrador' ? 'selected' : '' }}>Administrador</option>
                                    </select>
                                </form>
                            </td>

                            <td class="px-5 py-4 text-right">
                                @if (auth()->id() !== $user->id)
                                    {{-- Eliminar la cuenta borra también su historial clínico. El aviso va
                                         en el texto del diálogo, porque `confirm()` es la última línea
                                         antes de un borrado que no se puede deshacer. --}}
                                    <form action="{{ route('usuarios.destroy', $user) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Se eliminará la cuenta de {{ $user->nombre }} junto con todos sus registros, evaluaciones y partidas. Esta acción no se puede deshacer. ¿Continuar?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                aria-label="Eliminar la cuenta de {{ $user->nombre }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-transparent text-gray-600 transition-colors hover:border-neon-rose/40 hover:bg-neon-rose/10 hover:text-neon-rose">
                                            <i class="fa-solid fa-trash-can text-[11px]" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @else
                                    {{-- La propia cuenta no se puede eliminar desde aquí. --}}
                                    <span class="inline-flex h-9 w-9 items-center justify-center text-gray-700"
                                          title="No puedes eliminar tu propia cuenta">
                                        <i class="fa-solid fa-user-shield text-[11px]" aria-hidden="true"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="mt-5 text-[12px] leading-relaxed text-gray-500">
        Cambiar el tipo de acceso no muestra ni oculta registros ya existentes: para ver
        datos de un estudiante sigue haciendo falta un consentimiento vigente.
    </p>
</div>
@endsection