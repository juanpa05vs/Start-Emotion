@extends('layouts.app')

@section('title', 'Ajustes | S-Emotion')

@section('content')
{{-- El contenedor no lleva `p-*`: el `<main>` de la maqueta ya pone el
     relleno. Con las dos capas, en pantallas anchas el contenido se encoge
     hacia el centro de un hueco que no hace falta. --}}
<div class="max-w-4xl">

    <div class="mb-7">
        <h1 class="font-orbitron text-2xl font-bold tracking-tight adaptive-title">Ajustes</h1>
        <p class="mt-1.5 text-[13px] text-gray-400">
            Tus datos, el color de la aplicación y el modo claro u oscuro.
        </p>
    </div>

    {{-- Los avisos van con el mismo criterio que el resto de la aplicación:
         icono, texto en cuerpo normal y borde de color. Estaban en 10px en
         mayúsculas con `animate-pulse`, que para un «cambios guardados» es
         demasiado: parpadea por confirmación, no por urgencia. --}}
    @if (session('success'))
        <div role="status"
             class="mb-5 flex items-start gap-3 rounded-panel border border-accent/30 bg-accent-soft px-4 py-3.5">
            <i class="fa-solid fa-circle-check mt-0.5 text-[12px] text-accent-text" aria-hidden="true"></i>
            <p class="text-[13px] leading-relaxed text-accent-text">{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
             class="mb-5 rounded-panel border border-rose-500/40 bg-rose-500/10 px-4 py-3.5">
            <p class="mb-1.5 flex items-center gap-2 text-[13px] font-semibold text-rose-300">
                <i class="fa-solid fa-triangle-exclamation text-[12px]" aria-hidden="true"></i>
                No se ha podido guardar
            </p>
            <ul class="ml-6 list-disc space-y-0.5 text-[13px] leading-relaxed text-rose-200">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{--
        Tres módulos: quién eres, cómo se ve la aplicación, y avisar de un
        problema.

        Antes era una rejilla de cuatro bloques que en móvil se convertían en un
        scroll largo, y el scroll acababa donde estaba el formulario de nombre y
        correo, que es lo que más se visita. Cada módulo agrupa lo que se ajusta
        junto: la foto y los datos son quién eres; el color y el modo son cómo se
        ve; el comentario va solo porque no es un ajuste sino un envío.

        «Apariencia» va segundo y no en último lugar a propósito: cambiar el tema
        es lo que más se hace aquí después de cambiar el nombre, y no debe estar
        a dos scrolls de la entrada.
    --}}
    <x-modulos :modulos="[
        ['id' => 'perfil',      'titulo' => 'Tu perfil',  'icono' => 'fa-user'],
        ['id' => 'apariencia',  'titulo' => 'Apariencia', 'icono' => 'fa-palette'],
        ['id' => 'comentarios', 'titulo' => 'Comentarios', 'icono' => 'fa-comment-dots'],
    ]">
        {{-- ── PERFIL ────────────────────────────────────────────────────
             El avatar y los datos en la misma columna: los dos formularios van al
             mismo destino y guardar uno no guarda el otro, así que van separados
             y con su propio botón, pero en la misma pantalla. --}}
        <x-modulo id="perfil" activo>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <section class="surface rounded-panel p-5">
                    <h2 class="sr-only">Tu avatar</h2>

                    <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                        @csrf
                        @method('PATCH')

                        <div class="relative inline-block">
                            <div class="h-24 w-24 overflow-hidden rounded-pill border border-accent/40 bg-black p-0.5">
                                <img src="{{ auth()->user()->avatar
                                        ? asset('storage/' . auth()->user()->avatar)
                                        : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->nombre).'&background=000&color=fff' }}"
                                     id="avatarPreview"
                                     alt="Tu avatar"
                                     class="h-full w-full rounded-pill object-cover">
                            </div>

                            {{-- El `<input type=file>` se manda solo al elegir archivo, así
                                 que el botón no es un adorno: es el único control del
                                 formulario. Sin `aria-label` no tendría nombre accesible,
                                 porque dentro solo hay un icono.

                                 La etiqueta del archivo no lleva `.btn` a propósito: es un
                                 botón circular superpuesto a la imagen, no un rectángulo con
                                 texto, y forzarle el aspecto de `.btn` lo deformaría. Sí
                                 lleva el hundido al pulsar, que es lo que un control
                                 pulsable tiene que hacer. --}}
                            <label for="avatarInput"
                                   class="absolute bottom-0 right-0 flex h-8 w-8 cursor-pointer items-center justify-center rounded-pill border border-accent bg-accent text-accent-ink transition-transform hover:scale-105 active:scale-95">
                                <i class="fa-solid fa-camera text-[11px]" aria-hidden="true"></i>
                            </label>

                            <input type="file" name="avatar" id="avatarInput" class="sr-only" accept="image/*"
                                   onchange="document.getElementById('avatarForm').submit()">
                        </div>
                    </form>

                    <p class="mt-4 text-[14px] font-semibold text-white">{{ auth()->user()->nombre }}</p>
                    <p class="mt-0.5 text-[12px] text-gray-500">{{ auth()->user()->rol }}</p>

                    <p class="mt-3 text-[12px] leading-relaxed text-gray-500">
                        Al elegir una imagen se guarda y se aplica al momento.
                    </p>
                </section>

                <form action="{{ route('perfil.update') }}" method="POST" class="surface rounded-panel p-5 lg:col-span-2">
                    @csrf
                    @method('PATCH')

                    <h2 class="mb-4 border-b border-white/5 pb-3.5 text-[15px] font-semibold adaptive-title">
                        Tus datos
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label for="nombre" class="mb-1.5 block text-[12px] font-medium text-gray-400">Tu nombre</label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre', auth()->user()->nombre) }}" required
                                   autocomplete="name"
                                   class="campo w-full rounded-control border bg-black/50 px-4 py-2.5 text-[14px] text-white">
                        </div>

                        <div>
                            {{-- El rótulo decía «Correo Encriptado» y no lo está: es el
                                 correo tal cual, que además se usa para entrar. --}}
                            <label for="correo" class="mb-1.5 block text-[12px] font-medium text-gray-400">Correo electrónico</label>
                            <input type="email" id="correo" name="correo" value="{{ old('correo', auth()->user()->correo) }}" required
                                   autocomplete="email"
                                   class="campo w-full rounded-control border bg-black/50 px-4 py-2.5 text-[14px] text-white">
                        </div>
                    </div>

                    <x-boton class="mt-5" icono="check">
                        Guardar cambios
                    </x-boton>
                </form>
            </div>
        </x-modulo>

        {{-- ── APARIENCIA ─────────────────────────────────────────────── --}}
        <x-modulo id="apariencia">
            <section class="surface rounded-panel p-5">
                <h2 class="mb-1 flex items-center gap-2 text-[15px] font-semibold adaptive-title">
                    <i class="fa-solid fa-palette text-[12px] text-accent-text" aria-hidden="true"></i>
                    Color de la aplicación
                </h2>
                <p class="mb-4 text-[12px] leading-relaxed text-gray-500">
                    Elige con qué color se destacan los botones y los enlaces.
                </p>

                {{-- Cada opción muestra su propio color como muestra, y no un
                     nombre inventado: «Rosa» no dice qué se va a ver. El nombre va
                     en el color, que además es lo que se compara.

                     `.selector` trae el aspecto y el estado marcado; aquí no hay
                     ninguna clase de estado escrita a mano. --}}
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach (['blue' => 'Azul', 'rose' => 'Rosa', 'amber' => 'Ámbar', 'purple' => 'Morado'] as $valor => $nombre)
                            <button name="tema" value="{{ $valor }}"
                                    class="selector"
                                    aria-pressed="{{ auth()->user()->tema === $valor ? 'true' : 'false' }}">
                                {{ $nombre }}
                            </button>
                        @endforeach
                    </div>
                </form>

                <div class="mt-8 mb-1 flex items-center gap-2 border-t border-white/5 pt-6 text-[15px] font-semibold adaptive-title">
                    <i class="fa-solid fa-circle-half-stroke text-[12px] text-accent-text" aria-hidden="true"></i>
                    Modo claro u oscuro
                </div>
                <p class="mb-4 text-[12px] leading-relaxed text-gray-500">
                    El modo claro se aplica al instante y se recuerda la próxima vez.
                </p>

                {{-- `aria-pressed` en vez de reescribir `className` desde JavaScript.

                     Antes `updateButtonStyles()` sustituía la clase entera de los
                     dos botones al cambiar de modo. Eso duplicaba en JavaScript las
                     clases que ya están en el CSS: cualquier retoque visual en las
                     hojas de estilo se perdía en cuanto se pulsaba un botón, y el
                     estado activo solo se distinguía por un color. --}}
                <div class="grid grid-cols-2 gap-2 sm:max-w-sm">
                    <button type="button" data-modo="dark" class="selector" aria-pressed="false">
                        <i class="fa-solid fa-moon text-[10px]" aria-hidden="true"></i>
                        Oscuro
                    </button>

                    <button type="button" data-modo="light" class="selector" aria-pressed="false">
                        <i class="fa-solid fa-sun text-[10px]" aria-hidden="true"></i>
                        Claro
                    </button>
                </div>
            </section>
        </x-modulo>

        {{-- ── COMENTARIOS ──────────────────────────────────────────────
             El bloque dice lo mismo que la pantalla que los recoge: es el camino
             de vuelta desde «envié algo» hasta «alguien lo va a leer». --}}
        <x-modulo id="comentarios">
            <div class="rounded-panel border border-accent/25 bg-accent-soft p-5">
                <h2 class="mb-1 flex items-center gap-2 text-[15px] font-semibold text-accent-text">
                    <i class="fa-solid fa-comment-dots text-[12px]" aria-hidden="true"></i>
                    Enviar un comentario
                </h2>
                <p class="mb-4 text-[12px] leading-relaxed text-gray-400">
                    ¿Algo no funciona, o te falta alguna opción? Cuéntalo y lo revisamos.
                </p>

                <form action="{{ route('perfil.feedback') }}" method="POST">
                    @csrf
                    <label for="mensaje" class="sr-only">Tu comentario</label>
                    <textarea id="mensaje" name="mensaje" required rows="4"
                              placeholder="Escribe aquí tu comentario (mínimo 3 caracteres)…"
                              class="campo w-full rounded-control border bg-black/40 px-4 py-3 text-[14px] leading-relaxed text-white">{{ old('mensaje') }}</textarea>

                    <x-boton-secundario class="mt-3" icono="paper-plane">
                        Enviar comentario
                    </x-boton-secundario>
                </form>
            </div>
        </x-modulo>
    </x-modulos>
</div>
@endsection

@push('scripts')
<script>
    /* Modo claro u oscuro.

       El estado vive en `aria-pressed` de cada botón y el aspecto sale del
       CSS con `.selector[aria-pressed='true']`. Aquí solo se hace una cosa: poner
       el atributo en su sitio. Reemplazar `className` desde aquí obligaría a
       mantener dos copias de las mismas clases. */
    (function () {
        const aplicarModo = (modo) => {
            const claro = modo === 'light';

            document.documentElement.classList.toggle('light-mode', claro);
            localStorage.setItem('theme', claro ? 'light' : 'dark');

            document.querySelectorAll('[data-modo]').forEach((boton) => {
                boton.setAttribute('aria-pressed', String(boton.dataset.modo === modo));
            });
        };

        document.addEventListener('DOMContentLoaded', function () {
            // El `<head>` ya aplicó el modo guardado antes de pintar, así que
            // los botones arrancan sincronizados con lo que se está viendo.
            const actual = document.documentElement.classList.contains('light-mode') ? 'light' : 'dark';

            document.querySelectorAll('[data-modo]').forEach((boton) => {
                boton.setAttribute('aria-pressed', String(boton.dataset.modo === actual));
                boton.addEventListener('click', () => aplicarModo(boton.dataset.modo));
            });
        });
    })();
</script>
@endpush