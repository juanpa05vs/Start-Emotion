<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- [INGENIERÍA] CSRF Token para peticiones AJAX/Forms --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- El nombre del proyecto aparecía en el título de varias pantallas con
         dos formas distintas: quien navega por pestañas o marcadores veía
         «Start-Emotion» en unas y «S-Emotion» en otras. --}}
    <title>@yield('title', 'S-Emotion | Acompañamiento emocional')</title>

    {{-- RECURSOS EXTERNOS --}}
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- [MODO LUMINOSO]: Interceptor temprano para evitar el parpadeo oscuro al recargar --}}
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        } else {
            document.documentElement.classList.remove('light-mode');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        /**
         * Inyección del color de acento elegido por la persona.
         *
         * Este es el ÚNICO trabajo que hace el layout con el aspecto de la
         * aplicación: convertir el nombre del tema en un color y su versión
         * RGB. Todo lo demás —tipografías, superficies, radios, modo claro,
         * movimiento reducido— vive en `resources/css/app.css`, para que las
         * pantallas que no usan este layout (portada, acceso, política de
         * privacidad) tengan lo mismo sin duplicar nada.
         *
         * Este bloque va después de `@vite`, así que gana al valor por defecto
         * de `:root` por orden de cascada.
         */
        if (!function_exists('hexToRgb')) {
            function hexToRgb($hex) {
                $hex = str_replace("#", "", $hex);
                if(strlen($hex) == 3) {
                    $r = hexdec(substr($hex,0,1).substr($hex,0,1));
                    $g = hexdec(substr($hex,1,1).substr($hex,1,1));
                    $b = hexdec(substr($hex,2,1).substr($hex,2,1));
                } else {
                    $r = hexdec(substr($hex,0,2));
                    $g = hexdec(substr($hex,2,2));
                    $b = hexdec(substr($hex,4,2));
                }
                return "$r, $g, $b";
            }
        }

        $user = auth()->user();
        $temaActual = $user?->tema ?? 'blue';

        $accentColor = [
            'blue'   => '#22d3ee', // Neon Cyan
            'rose'   => '#f43f5e', // Neon Rose
            'amber'  => '#fbbf24', // Amber Alert
            'purple' => '#a855f7'  // Void Purple
        ][$temaActual] ?? '#22d3ee';

        $rgbValue = hexToRgb($accentColor);
    @endphp

    <style>
        :root {
            --neon-accent: {{ $accentColor }};
            --neon-accent-rgb: {{ $rgbValue }};
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen selection:bg-accent-soft selection:text-white overflow-x-hidden">
    <div class="scanline"></div>

    <div class="flex relative z-10">
        {{-- SIDEBAR --}}
        @auth
        <aside class="sidebar-alpha w-72 border-r min-h-screen backdrop-blur-2xl sticky top-0 h-screen flex flex-col shadow-[20px_0_50px_-20px_rgba(0,0,0,0.5)]">

            {{-- LOGO SECTOR --}}
            <div class="px-6 pt-6 pb-5 mb-3">
                {{-- Va a la pantalla de inicio de cada tipo de cuenta, no siempre
                     a `dashboard`: ese panel es del estudiante y el administrador
                     recibiría un 403 al hacer clic en el logo. --}}
                <a href="{{ $user ? route($user->rutaDeInicio()) : route('dashboard') }}" class="group block relative">
                    {{-- El nombre del producto sí lleva Orbitron: es el único
                         elemento de la barra que actúa como título. --}}
                    <h2 class="relative font-orbitron text-xl font-black tracking-tighter adaptive-title">
                        S-<span class="text-accent-text">EMOTION</span>
                    </h2>

                    {{-- El tipo de cuenta va en Rajdhani, no en Orbitron: es una
                         etiqueta informativa de 10-11px, y una fuente de display a ese
                         tamaño pierde los contornos. --}}
                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500">
                        {{ $user?->tiposDeCuenta()[0] ?? 'Panel' }}
                    </p>
                </a>
            </div>

            {{-- NAVEGACIÓN --}}
            <nav class="flex-1 px-4 space-y-2 overflow-y-auto">

                {{-- AUTOOBSERVACIÓN: solo para quien usa la herramienta para
                     observarse. El administrador administra cuentas y el
                     psicólogo atiende; ninguno de los dos tiene un "Menú de
                     Mando" propio, y mostrarles pantallas que no van a usar
                     invita a confundir qué se puede consultar desde dónde. --}}
                @if ($user && $user->esEstudiante())
                    <p class="mb-2.5 pl-5 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Menú de Mando</p>

                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="fa-house-chimney" step="01" label="Inicio" />

                    <x-nav-link :href="route('historial.index')" :active="request()->routeIs('historial.*')" icon="fa-microchip" step="02" label="Historial" />

                    {{-- MÓDULO PSICOMÉTRICO (INSTRUMENTO SISCO) --}}
                    <x-nav-link :href="route('psicometria.create')" :active="request()->routeIs('psicometria.*')" icon="fa-brain" step="03" label="Evaluación SISCO" sub="Cuestionario" />

                    <x-nav-link :href="route('perfil.calendario')" :active="request()->routeIs('perfil.calendario')" icon="fa-calendar-days" step="04" label="Calendario" />

                    <x-nav-link :href="route('minijuegos.index')" :active="request()->routeIs('minijuegos.*')" icon="fa-gamepad" step="05" label="Actividades" sub="Minijuegos" />

                    {{-- El enlace que materializa el derecho del estudiante a
                         ver y retirar lo que comparte. --}}
                    <x-nav-link :href="route('privacidad.index')" :active="request()->routeIs('privacidad.*')" icon="fa-shield-halved" step="06" label="Mis datos" sub="Privacidad" />
                @endif

                {{-- CUENTA PROPIA: fuera del bloque anterior a propósito. Cambiar
                     la contraseña o el tema es administrar la propia cuenta y le
                     sirve a los tres tipos; si esto quedara dentro, el
                     administrador se quedaría sin poder cambiar su clave. --}}
                <div class="{{ $user && $user->esEstudiante() ? '' : 'pt-8 mt-6 border-t border-white/5' }}">
                    <p class="mb-2.5 pl-5 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Cuenta</p>

                    <x-nav-link :href="route('perfil.config')" :active="request()->routeIs('perfil.config')" icon="fa-gear" step="07" label="Mi cuenta" sub="Ajustes" />
                </div>

                {{-- ESPACIO DEL PROFESIONAL --}}
                @if ($user && $user->esProfesional())
                    <div class="pt-8 mt-6 border-t border-white/5">
                        <p class="mb-2.5 pl-5 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Atención</p>

                        <x-nav-link :href="route('psicologia.pacientes')" :active="request()->routeIs('psicologia.pacientes')" icon="fa-user-group" step="P1" label="Mis estudiantes" sub="Con consentimiento" />

                        <x-nav-link :href="route('psicologia.invitaciones')" :active="request()->routeIs('psicologia.invitaciones')" icon="fa-key" step="P2" label="Invitaciones" />
                    </div>
                @endif

                {{-- SECCIÓN ADMINISTRACIÓN --}}
                @if($user && $user->esAdmin())
                    <div class="pt-8 mt-6 border-t border-white/5">
                        <p class="mb-2.5 pl-5 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Administración</p>

                        <x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')" icon="fa-users-gear" step="AA" label="Cuentas" />

                        {{-- VALIDACIÓN DEL MODELO --}}
                        <x-nav-link :href="route('admin.validacion')" :active="request()->routeIs('admin.validacion*')" icon="fa-chart-pie" step="VC" label="Validación" sub="Métricas del modelo" />

                        <x-nav-link :href="route('admin.feedback')" :active="request()->routeIs('admin.feedback')" icon="fa-comment-medical" step="FB" label="Comentarios" sub="Mejora continua" />
                    </div>
                @endif
            </nav>

            {{-- USER PROFILE CARD --}}
            <div class="mt-auto px-5 pb-5 pt-3">
                <div class="surface rounded-panel p-4">
                    <div class="flex items-center gap-3">
                        <div class="relative h-10 w-10 shrink-0">
                            <span class="animate-ping absolute inset-0 rounded-pill bg-accent opacity-20" aria-hidden="true"></span>
                            @if($user && $user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" alt="" class="relative h-10 w-10 rounded-pill object-cover border border-accent/50">
                            @else
                                <div class="relative flex h-10 w-10 items-center justify-center rounded-pill border border-accent/50 bg-accent-soft font-orbitron text-sm font-bold text-accent-text">
                                    {{ mb_strtoupper(mb_substr($user->nombre ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col min-w-0">
                            {{-- El nombre ya no se fuerza en mayúsculas: los nombres
                                 propios en mayúsculas se leen peor y aquí no hay
                                 ninguna razón de sistema. Se sustituye el
                                 «Status: Online», que era ruido de terminal sin
                                 información, por los tipos de cuenta reales. --}}
                            <p class="text-[13px] font-semibold adaptive-title truncate">{{ $user->nombre ?? 'Usuario' }}</p>
                            <p class="text-[11px] font-medium text-gray-500 truncate">{{ collect($user?->tiposDeCuenta() ?? [])->join(' · ') }}</p>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="mt-4">
                        @csrf
                        {{-- Neutro y no `x-boton-peligro`: cerrar sesión no es una
                             acción destructiva, y este botón está en la barra
                             lateral de todas las pantallas. Ponerlo en rojo lo
                             convertía en el único elemento de color de la
                             pantalla, y apartaba la atención de la acción que sí
                             importa. Antes iba con `border-rose-500/40` y
                             `text-rose-400` escritos a mano, con `10px` en
                             mayúsculas: en modo claro daba 2.65:1, y es la única
                             salida de la sesión. --}}
                        <x-boton-neutro type="submit" class="w-full" icono="arrow-right-from-bracket">
                            Terminar sesión
                        </x-boton-neutro>
                    </form>
                </div>
            </div>
        </aside>
        @endauth

        {{-- MAIN CONTENT --}}
        <main class="flex-1 px-5 py-7 sm:px-8 sm:py-9 relative">
            <div class="max-w-7xl mx-auto">

                {{-- Avisos. Antes `animate-pulse` los hacía parpadear sin parar y
                     el texto iba en mayúsculas de 10px. Los dos van en el mismo
                     sitio porque aquí es donde se anuncia un error. --}}
                @if(session('success'))
                    <div class="mb-6 flex items-start gap-3 rounded-panel border border-accent/30 bg-accent-soft px-4 py-3.5" role="status">
                        <i class="fa-solid fa-circle-check text-accent-text text-sm mt-0.5" aria-hidden="true"></i>
                        <p class="text-[13px] font-semibold adaptive-title leading-relaxed">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 flex items-start gap-3 rounded-panel border border-rose-500/40 bg-rose-500/10 px-4 py-3.5" role="alert">
                        <i class="fa-solid fa-triangle-exclamation text-rose-400 text-sm mt-0.5" aria-hidden="true"></i>
                        <p class="text-[13px] font-semibold text-rose-200 leading-relaxed">{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
