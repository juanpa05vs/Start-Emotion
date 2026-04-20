@extends('layouts.app')

@section('content')
<div class="p-8 max-w-5xl mx-auto">
    {{-- ENCABEZADO --}}
    <h1 class="font-orbitron text-3xl font-black text-white mb-2 uppercase tracking-tighter">
        Configuración de <span class="text-accent">Terminal</span>
    </h1>
    <p class="text-gray-500 text-[10px] uppercase tracking-[0.3em] mb-6">Personalización de Interfaz y Datos de Operador</p>

    {{-- [SENSORES DE ESTADO]: Bloque de Alertas para Feedback visual --}}
    @if (session('success'))
        <div class="mb-6 p-4 bg-accent/20 border border-accent text-accent text-[10px] font-black uppercase tracking-widest rounded-xl animate-pulse">
            <i class="fa-solid fa-check-double mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500 text-red-500 text-[10px] font-black uppercase tracking-widest rounded-xl">
            <ul>
                @foreach ($errors->all() as $error)
                    <li><i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- COLUMNA IZQUIERDA: IDENTIDAD Y TEMAS --}}
        <div class="space-y-6">

            {{-- AVATAR --}}
            <div class="bg-black/40 border border-white/10 backdrop-blur-xl p-6 rounded-2xl text-center relative overflow-hidden group">
                <div class="absolute inset-0 bg-accent/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                    @csrf
                    @method('PATCH')

                    <div class="relative inline-block">
                        <div class="h-32 w-32 rounded-full border-2 border-accent p-1 overflow-hidden bg-black shadow-[0_0_25px_rgba(0,0,0,0.5)]">
                            <img src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->nombre).'&background=000&color=fff' }}"
                                 id="avatarPreview"
                                 alt="Avatar" class="h-full w-full rounded-full object-cover transition-transform group-hover:scale-110">
                        </div>

                        <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/*" onchange="document.getElementById('avatarForm').submit()">

                        <button type="button" onclick="document.getElementById('avatarInput').click()"
                                class="absolute bottom-0 right-0 bg-accent text-white p-2 rounded-full text-xs hover:scale-110 transition-all shadow-[0_0_15px_var(--neon-accent)]">
                            <i class="fa-solid fa-camera"></i>
                        </button>
                    </div>
                </form>

                <h2 class="mt-4 text-white font-bold tracking-tight">{{ auth()->user()->nombre }}</h2>
                <p class="text-[9px] text-accent uppercase font-black tracking-[0.2em] mt-1">{{ auth()->user()->rol }}</p>
            </div>

            {{-- SELECCIÓN DE TEMA --}}
            <div class="bg-black/40 border border-white/10 backdrop-blur-xl p-6 rounded-2xl">
                <h3 class="text-white text-[10px] font-black uppercase mb-4 tracking-widest flex items-center gap-2">
                    <i class="fa-solid fa-palette text-accent"></i> Atmósfera de Sistema
                </h3>
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['blue' => 'Cyber Blue', 'rose' => 'Neon Rose', 'amber' => 'Amber Alert', 'purple' => 'Void Purple'] as $val => $label)
                            <button name="tema" value="{{ $val }}"
                                    class="h-10 rounded-lg border {{ auth()->user()->tema == $val ? 'border-accent bg-accent/20 text-white shadow-[0_0_10px_var(--neon-accent)]' : 'border-white/10 text-gray-500 hover:border-white/30' }} text-[9px] font-bold uppercase transition-all">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>

        {{-- COLUMNA DERECHA: CREDENCIALES Y FEEDBACK --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- FORMULARIO DE DATOS --}}
            <form action="{{ route('perfil.update') }}" method="POST" class="bg-black/40 border border-white/10 backdrop-blur-xl p-8 rounded-2xl relative">
                @csrf
                @method('PATCH')

                <h3 class="text-white text-[10px] font-black uppercase mb-6 tracking-widest border-b border-white/5 pb-4">Sincronizar Identidad</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[9px] text-gray-500 uppercase font-black ml-1">Nombre de Operador</label>
                        <input type="text" name="nombre" value="{{ auth()->user()->nombre }}" required
                               class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-accent focus:ring-0 transition-all outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[9px] text-gray-500 uppercase font-black ml-1">Correo Encriptado</label>
                        <input type="email" name="correo" value="{{ auth()->user()->correo }}" required
                               class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-accent focus:ring-0 transition-all outline-none">
                    </div>
                </div>

                <button type="submit" class="mt-8 bg-accent text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-[0.2em] hover:shadow-[0_0_20px_var(--neon-accent)] hover:scale-[1.02] transition-all">
                    Guardar Cambios en Base de Datos
                </button>
            </form>

            {{-- PROTOCOLO DE MEJORA --}}
            <div class="bg-accent/5 border border-accent/20 p-8 rounded-2xl">
                <h3 class="text-accent text-[10px] font-black uppercase mb-2 tracking-widest flex items-center gap-2">
                    <i class="fa-solid fa-comment-medical"></i> Protocolo de Mejora
                </h3>
                <p class="text-gray-400 text-[9px] mb-6 uppercase tracking-wider">¿Alguna anomalía o sugerencia técnica para la Terminal?</p>

                <form action="{{ route('perfil.feedback') }}" method="POST">
                    @csrf
                    {{-- [REPARACIÓN]: Cambiamos el name="comentario" por name="mensaje" para que el controlador lo reciba --}}
                    <textarea name="mensaje" required placeholder="Escriba aquí su reporte (mínimo 3 caracteres)..." rows="3"
                              class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-accent focus:ring-0 transition-all outline-none placeholder:text-gray-700">{{ old('mensaje') }}</textarea>

                    <button type="submit" class="mt-4 border border-accent text-accent px-6 py-2 rounded-lg text-[9px] font-black uppercase hover:bg-accent hover:text-white transition-all shadow-[0_0_10px_rgba(var(--neon-accent),0.1)]">
                        Transmitir Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
