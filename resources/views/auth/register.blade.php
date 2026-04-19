@extends('layouts.app_auth')

@section('title', 'Start-Emotion | Registro de Operador')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-start-dark p-6 selection:bg-neon-cyan selection:text-black">
    {{-- Card Principal con Estética Cyberpunk --}}
    <div class="bg-white/5 border border-white/10 p-8 md:p-12 rounded-[2.5rem] backdrop-blur-xl w-full max-w-xl shadow-2xl relative overflow-hidden">

        {{-- Decoración de Luces Neón --}}
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-neon-cyan/10 blur-[80px] rounded-full"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-neon-purple/10 blur-[80px] rounded-full"></div>

        <div class="text-center mb-10 relative">
            <h2 class="text-neon-cyan font-black text-3xl tracking-tighter uppercase italic">Nuevo Registro</h2>
            <p class="text-gray-500 text-[10px] tracking-[0.4em] mt-2 font-bold">CAPTURA DE DATOS PERSONALES // PROTOCOLO S-EMOTION</p>
        </div>

        {{-- Alertas de Validación --}}
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-2xl">
                <ul class="list-disc list-inside text-red-400 text-[10px] uppercase font-bold tracking-widest">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-5 relative">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Nombre --}}
                <div>
                    <label class="text-[10px] text-gray-400 uppercase tracking-widest block mb-2 font-black">Nombre Completo</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-neon-cyan outline-none transition-all placeholder:text-gray-700" placeholder="Ej. Juan Pérez" required>
                </div>
                {{-- Edad --}}
                <div>
                    <label class="text-[10px] text-gray-400 uppercase tracking-widest block mb-2 font-black">Edad</label>
                    <input type="number" name="edad" value="{{ old('edad') }}" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-neon-cyan outline-none transition-all placeholder:text-gray-700" placeholder="00" required>
                </div>
            </div>

            {{-- Correo --}}
            <div>
                <label class="text-[10px] text-gray-400 uppercase tracking-widest block mb-2 font-black">Correo Institucional / Personal</label>
                <input type="email" name="correo" value="{{ old('correo') }}" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-neon-cyan outline-none transition-all placeholder:text-gray-700" placeholder="usuario@dominio.com" required>
            </div>

            {{-- Selección de Rol --}}
            <div>
                <label class="text-[10px] text-gray-400 uppercase tracking-widest block mb-2 font-black">Tipo de Cuenta</label>
                <select name="rol" id="rol-selector" onchange="toggleAdminKey()" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-neon-cyan outline-none transition-all cursor-pointer appearance-none">
                    <option value="Usuario">👤 Usuario Estándar</option>
                    <option value="Administrador">🔑 Administrador del Sistema</option>
                </select>
            </div>

            {{-- Campo de Seguridad: Token de Administrador (Oculto por defecto) --}}
            <div id="admin-key-group" class="hidden animate-pulse">
                <label class="text-[10px] text-neon-purple uppercase tracking-widest block mb-2 font-black">Código de Autorización Admin</label>
                <input type="password" name="admin_token" class="w-full bg-black/60 border border-neon-purple/50 rounded-2xl px-5 py-3 text-white focus:border-neon-purple outline-none transition-all shadow-[0_0_15px_rgba(168,85,247,0.1)]" placeholder="Ingrese la clave maestra">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Contraseña --}}
                <div>
                    <label class="text-[10px] text-gray-400 uppercase tracking-widest block mb-2 font-black">Contraseña</label>
                    <input type="password" name="contrasena" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-neon-cyan outline-none transition-all" required>
                </div>
                {{-- Confirmar Contraseña --}}
                <div>
                    <label class="text-[10px] text-gray-400 uppercase tracking-widest block mb-2 font-black">Verificar</label>
                    <input type="password" name="contrasena_confirmation" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-neon-cyan outline-none transition-all" required>
                </div>
            </div>

            {{-- Botón de Acción --}}
            <button type="submit" class="w-full bg-neon-cyan/10 border border-neon-cyan text-neon-cyan py-4 rounded-2xl font-black uppercase tracking-[0.2em] hover:bg-neon-cyan hover:text-black transition-all shadow-[0_0_30px_rgba(34,211,238,0.2)] active:scale-95 mt-4">
                Finalizar Registro
            </button>
        </form>

        <div class="mt-8 text-center relative">
            <p class="text-[10px] text-gray-500 uppercase tracking-widest">¿Ya tienes una cuenta activa?
                <a href="{{ route('login') }}" class="text-neon-cyan hover:underline ml-1 font-black">Iniciar Sesión</a>
            </p>
        </div>
    </div>
</div>

{{-- Script de Lógica de Interfaz --}}
<script>
    function toggleAdminKey() {
        const rol = document.getElementById('rol-selector').value;
        const keyGroup = document.getElementById('admin-key-group');

        if (rol === 'Administrador') {
            keyGroup.classList.remove('hidden');
        } else {
            keyGroup.classList.add('hidden');
        }
    }
</script>
@endsection
