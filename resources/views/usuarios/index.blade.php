@extends('layouts.app')

@section('content')
<div class="p-8">
    {{-- ENCABEZADO DE SECTOR --}}
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="font-orbitron text-2xl font-black text-white uppercase tracking-widest">
                Gestión de <span class="text-neon-rose">Operadores</span>
            </h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="h-[1px] w-8 bg-neon-rose/50"></span>
                <p class="text-[8px] text-gray-500 uppercase tracking-[0.4em] font-bold">Control de Privilegios de Acceso</p>
            </div>
        </div>
        <div class="text-right">
            <span class="text-[10px] text-gray-600 uppercase font-black tracking-tighter">Total Registros: {{ $usuarios->count() }}</span>
        </div>
    </div>

    {{-- TABLA DE DATOS --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-xl shadow-2xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/10">
                    <th class="px-6 py-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Identidad</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Contacto / Bio</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Nivel de Acceso</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($usuarios as $user)
                <tr class="hover:bg-white/[0.02] transition-colors group">
                    {{-- NOMBRE Y EDAD --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-neon-cyan font-black text-xs font-orbitron">
                                {{ substr($user->nombre, 0, 1) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-white group-hover:text-neon-cyan transition-colors">{{ $user->nombre }}</span>
                                <span class="text-[9px] text-gray-500 uppercase">{{ $user->edad }} Años</span>
                            </div>
                        </div>
                    </td>

                    {{-- CORREO --}}
                    <td class="px-6 py-4">
                        <span class="text-xs text-gray-400 font-mono italic">{{ $user->correo }}</span>
                    </td>

                    {{-- 🛡️ BADGE DE ROL DINÁMICO --}}
                    <td class="px-6 py-4 text-center">
                        @php
                            $rolLimpio = trim(strtolower($user->rol));
                            $estiloBadge = match($rolLimpio) {
                                'administrador' => 'border-neon-rose text-neon-rose bg-neon-rose/5 shadow-[0_0_10px_rgba(244,63,94,0.2)]',
                                'jugador'       => 'border-neon-cyan text-neon-cyan bg-neon-cyan/5',
                                default         => 'border-gray-600 text-gray-600 bg-gray-600/5'
                            };
                        @endphp

                        <span class="inline-block px-3 py-1 border rounded-md text-[8px] font-black uppercase tracking-[0.2em] {{ $estiloBadge }}">
                            {{ $user->rol ?? 'Invitado' }}
                        </span>
                    </td>

                    {{-- ACCIONES: GESTIÓN DE RANGO Y ELIMINACIÓN --}}
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end items-center gap-4">
                            {{-- FORMULARIO DE ROL --}}
                            <form action="{{ route('usuarios.updateRole', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="rol" onchange="this.form.submit()"
                                        class="bg-black/40 border border-white/10 text-[9px] font-black uppercase tracking-widest rounded-lg px-2 py-1 focus:border-neon-cyan focus:ring-0 transition-all cursor-pointer
                                        {{ trim(strtolower($user->rol)) == 'administrador' ? 'text-neon-rose border-neon-rose/30' : 'text-neon-cyan border-neon-cyan/30' }}">
                                    <option value="jugador" {{ trim(strtolower($user->rol)) == 'jugador' ? 'selected' : '' }}>[ Nivel: Jugador ]</option>
                                    <option value="Administrador" {{ trim(strtolower($user->rol)) == 'administrador' ? 'selected' : '' }}>[ Nivel: ADMIN ]</option>
                                </select>
                            </form>

                            {{-- FORMULARIO DE ELIMINACIÓN (Módulo de Baja) --}}
                            {{-- [INGENIERÍA]: Verificamos que no sea el mismo usuario autenticado --}}
                            @if(auth()->id() !== $user->id)
                                <form action="{{ route('usuarios.destroy', $user) }}" method="POST" class="inline"
                                      onsubmit="return confirm('⚠️ ALERTA DE SISTEMA: ¿Está seguro de purgar permanentemente al operador {{ $user->nombre }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 bg-neon-rose/5 border border-transparent hover:border-neon-rose/50 rounded-lg text-gray-600 hover:text-neon-rose transition-all opacity-40 hover:opacity-100"
                                            title="Eliminar Operador">
                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                    </button>
                                </form>
                            @else
                                {{-- Bloqueo visual para la propia cuenta --}}
                                <div class="p-2 text-gray-800" title="Tu cuenta principal no puede ser purgada">
                                    <i class="fa-solid fa-user-shield text-[10px]"></i>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- NOTA TÉCNICA --}}
    <div class="mt-6 flex items-center gap-2">
        <div class="h-1 w-1 rounded-full bg-neon-rose animate-pulse"></div>
        <p class="text-[9px] text-gray-600 uppercase tracking-widest">Aviso: Las modificaciones de rango y bajas de usuario afectan la integridad de los reportes históricos.</p>
    </div>
</div>
@endsection
