@extends('layouts.app_auth')

@section('title', 'S-Emotion | Crear cuenta')

@section('content')

{{-- Misma tarjeta, mismos controles y mismo acento que el inicio de sesión.
     Antes el registro era una tarjeta de cristal con `rounded-[2.5rem]` sobre
     fondo oscuro mientras el login partía la pantalla en dos: el mismo
     formulario con dos treatments distintos. --}}
<section class="surface rounded-panel px-6 py-8 sm:px-8 sm:py-10 shadow-[0_24px_60px_-30px_rgba(0,0,0,0.8)]">

    <div class="mb-7">
        <h1 class="font-orbitron text-2xl font-black tracking-tight adaptive-title">Crear cuenta</h1>
        <p class="text-[13px] text-gray-400 mt-1.5 leading-relaxed">
            Elige el tipo de cuenta que te corresponde. Podrás registrar cómo te
            sientes y verlo con claridad.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-control border border-rose-500/40 bg-rose-500/10 px-4 py-3.5" role="alert">
            <p class="mb-1.5 flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-rose-400">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                Revisa estos campos
            </p>
            <ul class="list-disc space-y-1 pl-5 text-[12px] font-semibold text-rose-300">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('register') }}" method="POST" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="nombre" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                    Nombre y apellidos
                </label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                       @error('nombre') aria-invalid="true" @enderror
                       class="campo w-full rounded-control border bg-black/50 px-4 py-3 text-sm text-white placeholder:text-gray-600 @error('nombre') border-rose-400 @enderror"
                       placeholder="Cómo te llamamos" autocomplete="name" required>
                @error('nombre')
                    <p class="mt-1.5 text-[11px] font-semibold text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="edad" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                    Edad
                </label>
                <input type="number" name="edad" id="edad" value="{{ old('edad') }}" min="13" max="120"
                       @error('edad') aria-invalid="true" @enderror
                       class="campo w-full rounded-control border bg-black/50 px-4 py-3 text-sm text-white placeholder:text-gray-600 @error('edad') border-rose-400 @enderror"
                       placeholder="—" autocomplete="off" required>
                @error('edad')
                    <p class="mt-1.5 text-[11px] font-semibold text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

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
            <label for="rol-selector" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                Tipo de cuenta
            </label>
            {{-- Se usa `change` en vez de `onchange` para no depender de un
                 atributo en línea, y se restauran los valores si el formulario
                 vuelve por un error. --}}
            <div class="relative">
                <select name="rol" id="rol-selector"
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

        {{-- Campo de autorización: aparece para los dos tipos que lo exigen.
             Antes solo se mostraba para administración, así que elegir
             «profesional de psicología» no encontraba el campo y el alta fallaba
             sin explicación visible. --}}
        <div id="admin-key-group" class="hidden">
            <label for="admin_token" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                Código de autorización
            </label>
            <input type="password" name="admin_token" id="admin_token" autocomplete="off"
                   @error('admin_token') aria-invalid="true" @enderror
                   class="campo w-full rounded-control border bg-black/50 px-4 py-3 text-sm text-white placeholder:text-gray-600 @error('admin_token') border-rose-400 @enderror"
                   placeholder="Te lo entrega quien administre la plataforma">
            @error('admin_token')
                <p class="mt-1.5 text-[11px] font-semibold text-rose-400">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-[11px] text-gray-500 leading-relaxed">
                Las cuentas de profesional de psicología y de administración necesitan
                este código. Sin él, la cuenta se creará como estudiante.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="contrasena" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                    Contraseña
                </label>
                <input type="password" name="contrasena" id="contrasena"
                       @error('contrasena') aria-invalid="true" @enderror
                       class="campo w-full rounded-control border bg-black/50 px-4 py-3 text-sm text-white @error('contrasena') border-rose-400 @enderror"
                       autocomplete="new-password" required>
                @error('contrasena')
                    <p class="mt-1.5 text-[11px] font-semibold text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="contrasena_confirmation" class="block mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">
                    Repite la contraseña
                </label>
                <input type="password" name="contrasena_confirmation" id="contrasena_confirmation"
                       class="campo w-full rounded-control border bg-black/50 px-4 py-3 text-sm text-white"
                       autocomplete="new-password" required>
            </div>
        </div>

        <x-boton class="w-full" tamano="grande">
            Crear mi cuenta
        </x-boton>
    </form>

    <p class="mt-7 border-t border-white/5 pt-6 text-center text-[12px] text-gray-400">
        ¿Ya tienes una cuenta?
        <a href="{{ route('login') }}" class="font-semibold text-accent-text transition-colors hover:underline-offset-4 hover:underline">
            Iniciar sesión
        </a>
    </p>
</section>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selector = document.getElementById('rol-selector');
        const keyGroup = document.getElementById('admin-key-group');
        const keyInput = document.getElementById('admin_token');

        // Cualquier tipo distinto de estudiante necesita el código. Se compara
        // en minúsculas para no depender de cómo se escriba el acento en el
        // value de la opción.
        function toggleAdminKey() {
            const necesitaCodigo = selector.value.trim().toLowerCase() !== 'estudiante';
            keyGroup.classList.toggle('hidden', !necesitaCodigo);
            keyInput.required = necesitaCodigo;
        }

        selector.addEventListener('change', toggleAdminKey);
        // Al volver del servidor el selector ya viene marcado: el campo del
        // código tiene que reaparecer sin que la persona tenga que tocar nada.
        toggleAdminKey();
    });
</script>
@endpush
