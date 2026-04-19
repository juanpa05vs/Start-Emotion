@extends('layouts.app_auth')

@section('title', 'Start-Emotion | Acceso al Sistema')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-start-dark p-6">
    {{-- Contenedor Principal: Estilo Cyberpunk --}}
    <div class="bg-white/5 border border-white/10 rounded-[2.5rem] backdrop-blur-xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col md:flex-row">

        {{-- Panel Lateral Izquierdo (Diseño Identidad Visual) --}}
        <div class="md:w-5/12 bg-gradient-to-br from-neon-purple to-neon-rose p-10 flex flex-col justify-center text-white relative">
            {{-- Decoración sutil --}}
            <div class="absolute top-0 left-0 w-full h-full bg-black/10 pointer-events-none"></div>

            <span class="bg-white/20 self-start px-3 py-1 rounded-full text-[10px] font-bold mb-6 tracking-widest uppercase relative z-10">EmotionPlay</span>
            <h2 class="text-4xl font-black italic leading-tight mb-4 tracking-tighter relative z-10">Iniciar sesión</h2>
            <p class="text-sm opacity-80 leading-relaxed mb-8 relative z-10">
                Accede al sistema con tus credenciales para consultar el panel y los módulos de análisis conductual.
            </p>
            <ul class="space-y-3 text-[10px] font-bold uppercase tracking-widest opacity-90 relative z-10">
                <li class="flex items-center gap-2"><span>✓</span> Acceso por roles</li>
                <li class="flex items-center gap-2"><span>✓</span> Gestión centralizada</li>
                <li class="flex items-center gap-2"><span>✓</span> Interfaz Laravel con Blade</li>
            </ul>
        </div>

        {{-- Panel de Formulario --}}
        <div class="md:w-7/12 bg-white p-10 flex flex-col justify-center">
            <div class="mb-8">
                {{-- CORRECCIÓN: Ahora es un enlace funcional --}}
                <a href="{{ route('welcome') }}" class="text-neon-purple text-xs font-bold mb-1 hover:underline inline-block transition-all active:scale-95">
                    ← Volver al inicio
                </a>
                <h3 class="text-2xl font-black text-gray-900 uppercase">Acceso al sistema</h3>
                <p class="text-gray-500 text-xs mt-1">Ingresa tus datos para continuar.</p>
            </div>

            {{-- Mensajes de Status (Capa de Lógica: Feedback) --}}
            @if (session('status'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-xs rounded-lg animate-pulse">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Campo: Correo --}}
                <div>
                    <label class="text-[10px] text-gray-400 font-black uppercase tracking-widest block mb-2">Correo electrónico</label>
                    <input type="email" name="correo" value="{{ old('correo') }}"
                        class="w-full bg-gray-50 border @error('correo') border-red-500 @else border-gray-200 @enderror rounded-xl px-5 py-3 text-gray-900 focus:ring-2 focus:ring-neon-purple outline-none transition-all placeholder:text-gray-300"
                        placeholder="usuario@dominio.com" required>
                    @error('correo')
                        <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Campo: Contraseña --}}
                <div>
                    <label class="text-[10px] text-gray-400 font-black uppercase tracking-widest block mb-2">Contraseña</label>
                    <input type="password" name="contrasena"
                        class="w-full bg-gray-50 border @error('contrasena') border-red-500 @else border-gray-200 @enderror rounded-xl px-5 py-3 text-gray-900 focus:ring-2 focus:ring-neon-purple outline-none transition-all placeholder:text-gray-300"
                        placeholder="••••••••" required>
                </div>

                {{-- Campo: Tipo de Acceso (Sincronizado con ABP) --}}
                <div>
                    <label class="text-[10px] text-gray-400 font-black uppercase tracking-widest block mb-2">Tipo de acceso</label>
                    <div class="relative">
                        <select name="rol" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-5 py-3 text-gray-900 focus:ring-2 focus:ring-neon-purple outline-none transition-all appearance-none cursor-pointer">
                            <option value="Usuario">Usuario</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400">
                            ▼
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-neon-purple text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-opacity-90 transition-all shadow-lg active:scale-95">
                    Entrar al sistema
                </button>
            </form>

            <div class="mt-8 text-center text-[10px]">
                <p class="text-gray-500">¿No tienes cuenta?
                    <a href="{{ route('register') }}" class="text-neon-purple font-black hover:underline uppercase">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
