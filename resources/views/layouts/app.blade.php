<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- [INGENIERÍA] CSRF Token para peticiones AJAX/Forms --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'S-Emotion | TESVB')</title>

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
         * [LÓGICA DE NÚCLEO]
         * Conversión Hex a RGB para efectos de transparencia dinámicos.
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
        $temaActual = $user->tema ?? 'blue';

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
            --neon-cyan: #22d3ee;
            --neon-purple: #a855f7;
            --neon-rose: #f43f5e;

            /* [NUEVO] Abstracción de colores de entorno para soporte de modos */
            --bg-primary: #030712;
            --bg-sidebar: rgba(0, 0, 0, 0.6);
            --border-system: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-muted: #94a3b8;
            --scanline-opacity: 0.02;
        }

        /* [NUEVO] Mutación del ecosistema al Modo Claro */
        html.light-mode {
            --bg-primary: #f1f5f9;
            --bg-sidebar: rgba(255, 255, 255, 0.8);
            --border-system: rgba(15, 23, 42, 0.08);
            --text-primary: #0f172a;
            --text-muted: #475569;
            --scanline-opacity: 0.005;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            cursor: crosshair;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .font-orbitron { font-family: 'Orbitron', sans-serif; }

        /* [ESTÉTICA] Efecto Scanline adaptativo */
        .scanline {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom, transparent 50%, rgba(var(--neon-accent-rgb), var(--scanline-opacity)) 50%);
            background-size: 100% 4px;
            z-index: 9999;
            pointer-events: none;
            opacity: 0.3;
        }

        /* Estilos reactivos de barra lateral */
        aside.sidebar-alpha {
            background-color: var(--bg-sidebar) !important;
            border-color: var(--border-system) !important;
            transition: background-color 0.4s ease, border-color 0.4s ease;
        }

        /* Forzar herencia de color de texto en elementos clave */
        .adaptive-title { color: var(--text-primary) !important; }
        .adaptive-text { color: var(--text-muted) !important; }

        /* Clases de utilidad dinámicas basadas en el tema */
        .text-accent { color: var(--neon-accent); }
        .border-accent { border-color: var(--neon-accent); }
        .bg-accent-soft { background-color: rgba(var(--neon-accent-rgb), 0.1); }
        .shadow-accent { box-shadow: 0 0 15px rgba(var(--neon-accent-rgb), 0.3); }

        /* Scrollbar Personalizada */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--neon-accent); border-radius: 10px; }
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
            <div class="p-8 mb-4">
                <a href="{{ route('dashboard') }}" class="group block relative">
                    <div class="absolute -inset-2 bg-accent-soft blur-xl opacity-0 group-hover:opacity-100 transition-all duration-500"></div>
                    <h2 class="font-orbitron text-2xl font-black tracking-tighter adaptive-title relative">
                        S-<span class="text-accent drop-shadow-[0_0_8px_var(--neon-accent)]">EMOTION</span>
                    </h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="h-[1px] w-8 border-t border-accent opacity-50"></span>
                        <p class="text-[7px] text-accent opacity-60 uppercase tracking-[0.5em] font-black">Control Panel v1.0</p>
                    </div>
                </a>
            </div>

            {{-- NAVEGACIÓN --}}
            <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
                <p class="text-[9px] text-gray-500 uppercase tracking-[0.3em] font-black mb-4 pl-4">Menú de Mando</p>

                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="fa-house-chimney" step="01" label="Inicio" />
                <x-nav-link :href="route('historial.index')" :active="request()->routeIs('historial.*')" icon="fa-microchip" step="02" label="Historial" />
                <x-nav-link :href="route('perfil.calendario')" :active="request()->routeIs('perfil.calendario')" icon="fa-calendar-days" step="03" label="Calendario" />
                <x-nav-link :href="route('perfil.config')" :active="request()->routeIs('perfil.config')" icon="fa-gear" step="04" label="Terminal" sub="Personalización" />

                {{-- SECCIÓN ADMINISTRACIÓN --}}
                @if($user->esAdmin())
                    <div class="pt-8 mt-6 border-t border-white/5">
                        <p class="text-[9px] text-gray-500 uppercase tracking-[0.3em] font-black mb-4 pl-4">Alpha Sector</p>

                        <x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')" icon="fa-users-gear" step="AA" label="Usuarios" />
                        <x-nav-link :href="route('admin.feedback')" :active="request()->routeIs('admin.feedback')" icon="fa-comment-medical" step="FB" label="Monitor Feedback" sub="Mejora Continua" />
                    </div>
                @endif
            </nav>

            {{-- USER PROFILE CARD --}}
            <div class="p-6 mt-auto bg-gradient-to-t from-black/20 to-transparent">
                <div class="bg-white/5 border border-white/10 p-4 rounded-2xl backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <div class="relative h-10 w-10 shrink-0">
                            <span class="animate-ping absolute h-full w-full rounded-full bg-accent opacity-20"></span>
                            @if($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" class="h-10 w-10 rounded-full object-cover border border-accent/50 relative z-10">
                            @else
                                <div class="relative z-10 rounded-full h-10 w-10 bg-accent-soft border border-accent/50 flex items-center justify-center text-accent font-black text-xs font-orbitron">
                                    {{ substr($user->nombre, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col min-w-0">
                            <p class="text-[10px] font-black adaptive-title truncate uppercase">{{ $user->nombre }}</p>
                            <p class="text-[7px] text-accent font-bold tracking-[0.2em] uppercase">Status: Online</p>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full py-2 bg-neon-rose/10 border border-neon-rose/30 text-neon-rose rounded-lg text-[8px] font-black uppercase tracking-[0.3em] hover:bg-neon-rose hover:text-white transition-all active:scale-95">
                            [ TERMINAR_SESIÓN ]
                        </button>
                    </form>
                </div>
            </div>
        </aside>
        @endauth

        {{-- MAIN CONTENT --}}
        <main class="flex-1 p-10 relative">
            <div class="max-w-7xl mx-auto">
                {{-- Alertas del Sistema --}}
                @if(session('success'))
                    <div class="mb-6 p-4 bg-accent-soft border border-accent/30 rounded-xl adaptive-title text-[10px] font-bold uppercase tracking-widest flex items-center shadow-accent animate-pulse">
                        <i class="fa-solid fa-check-double text-accent mr-3 text-sm"></i> {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
