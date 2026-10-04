@extends('layouts.app_auth')

@section('title', 'S-Emotion | Iniciar sesión')

@section('content')

{{-- Un solo lenguaje visual para las dos pantallas de acceso. El login tenía
     un panel oscuro y un panel blanco, mientras que el registro usaba una
     tarjeta de cristal: dos tratamiento para el mismo formulario. Ahora las dos
     son la misma tarjeta, con los mismos controles y el mismo acento. --}}
<section class="surface rounded-panel px-6 py-8 sm:px-8 sm:py-10 shadow-[0_24px_60px_-30px_rgba(0,0,0,0.8)]">

    <div class="mb-7">
        <h1 class="font-orbitron text-2xl font-black tracking-tight adaptive-title">Iniciar sesión</h1>
        <p class="text-[13px] text-gray-400 mt-1.5 leading-relaxed">
            Entra con tu correo y contraseña para ver cómo has estado.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 flex items-start gap-2.5 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-3">
            <i class="fa-solid fa-circle-check text-emerald-400 text-xs mt-0.5" aria-hidden="true"></i>
            <p class="text-[12px] font-semibold text-emerald-300 leading-relaxed">{{ session('status') }}</p>
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="correo" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                Correo electrónico
            </label>
            <input type="email" name="correo" id="correo" value="{{ old('correo') }}"
                   @error('correo') aria-invalid="true" @enderror
                   class="campo w-full rounded-control border bg-black/50 px-4 py-3 text-sm text-white placeholder:text-gray-600 @error('correo') border-rose-400 @enderror"
                   placeholder="nombre@dominio.com" autocomplete="email" required>
            @error('correo')
                <p class="mt-1.5 text-[11px] font-semibold text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="contrasena" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                Contraseña
            </label>
            <div class="relative">
                <input type="password" name="contrasena" id="contrasena"
                       @error('contrasena') aria-invalid="true" @enderror
                       class="campo w-full rounded-control border bg-black/50 px-4 py-3 pr-12 text-sm text-white placeholder:text-gray-600 @error('contrasena') border-rose-400 @enderror"
                       placeholder="••••••••" autocomplete="current-password" required>

                {{-- Iconografía de FontAwesome como en el resto del programa. Antes
                     era el emoji 👁️, que se veía distinto en cada sistema
                     operativo y rompía la retícula. --}}
                <button type="button" id="togglePassword" aria-label="Mostrar la contraseña"
                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-gray-500 transition-colors hover:text-accent-text">
                    <i class="fa-solid fa-eye text-xs" aria-hidden="true"></i>
                </button>
            </div>
            @error('contrasena')
                <p class="mt-1.5 text-[11px] font-semibold text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="rol" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                Tipo de acceso
            </label>
            <div class="relative">
                {{-- No es decorativo: si el tipo no corresponde a la cuenta, la
                     sesión se cierra (ver AuthController). Por eso están los tres
                     tipos reales y no un campo libre. --}}
                <select name="rol" id="rol"
                        @error('rol') aria-invalid="true" @enderror
                        class="campo w-full appearance-none rounded-control border bg-black/50 px-4 py-3 pr-10 text-sm text-white @error('rol') border-rose-400 @enderror">
                    <option value="estudiante" @selected(old('rol', 'estudiante') === 'estudiante')>Soy estudiante</option>
                    <option value="Psicólogo" @selected(old('rol') === 'Psicólogo')>Soy profesional de psicología</option>
                    <option value="Administrador" @selected(old('rol') === 'Administrador')>Administro el sistema</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-gray-500">
                    <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i>
                </div>
            </div>
            @error('rol')
                <p class="mt-1.5 text-[11px] font-semibold text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <x-boton class="w-full" tamano="grande">
            Entrar al sistema
        </x-boton>
    </form>

    <p class="mt-7 border-t border-white/5 pt-6 text-center text-[12px] text-gray-400">
        ¿No tienes cuenta?
        <a href="{{ route('register') }}" class="font-semibold text-accent-text transition-colors hover:underline-offset-4 hover:underline">
            Crear una cuenta
        </a>
    </p>
</section>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const campo = document.getElementById('contrasena');
        const boton = document.getElementById('togglePassword');
        const icono = boton.querySelector('i');

        boton.addEventListener('click', function () {
            const visible = campo.getAttribute('type') === 'text';

            campo.setAttribute('type', visible ? 'password' : 'text');
            icono.classList.toggle('fa-eye', visible);
            icono.classList.toggle('fa-eye-slash', !visible);
            boton.setAttribute('aria-label', visible ? 'Mostrar la contraseña' : 'Ocultar la contraseña');
        });
    });
</script>
@endpush
