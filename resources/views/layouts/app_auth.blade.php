<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'S-Emotion | Acceso')</title>

    {{-- Las mismas tres fuentes que el panel principal. Antes este layout
         cargaba solo Orbitron y además forzaba `body { font-family: Orbitron }`,
         de modo que el login y el registro se leían con una tipografía de
         display y parecían otra aplicación.

         La lista de pesos es la misma que en `layouts/app.blade.php` a propósito:
         aquí cargaba además el 600, que en el panel no existe. Una diferencia
         que no se ve en el código y solo se manifiesta si alguien pide ese
         peso en el login y no en la aplicación. --}}
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Interceptor temprano: si alguien dejó el programa en modo claro, el
         acceso se abre en modo claro y no hay parpadeo al recargar. --}}
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        } else {
            document.documentElement.classList.remove('light-mode');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- El acento por defecto (cian). Aquí todavía no hay sesión, así que no se
         puede saber qué tema eligió la persona; el panel principal lo inyecta
         desde `layouts/app.blade.php`. --}}
    <style>
        :root { --neon-accent: #22d3ee; --neon-accent-rgb: 34, 211, 238; }
    </style>

    @stack('styles')
</head>
<body class="min-h-full flex flex-col">
    <div class="scanline" aria-hidden="true"></div>

    {{-- Cabecera: marca + vuelta. Fuera de la tarjeta para que el acceso no
         dependa de ella ni la empuje hacia abajo al crecer. --}}
    <header class="relative z-10 flex items-center justify-between px-6 py-5 sm:px-10">
        <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
            <span class="font-orbitron text-lg font-black tracking-tighter adaptive-title">
                S-<span class="text-accent-text">EMOTION</span>
            </span>
            <span class="hidden sm:inline text-[10px] uppercase tracking-[0.3em] text-gray-500 font-bold">
                Acceso
            </span>
        </a>

        <a href="{{ route('welcome') }}"
           class="inline-flex items-center gap-2 text-[11px] font-semibold text-gray-400 hover:text-accent-text transition-colors">
            <i class="fa-solid fa-arrow-left text-[10px]" aria-hidden="true"></i>
            Volver al inicio
        </a>
    </header>

    <main class="relative z-10 flex-1 flex items-center justify-center px-5 pb-14">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </main>

    {{-- Pie: el acceso dice siempre quién puede ver los datos. Es la misma
         promesa que la política de privacidad, puesta donde alguien la lee antes
         de crear su cuenta. --}}
    <footer class="relative z-10 px-5 pb-8 text-center">
        <p class="text-[11px] text-gray-500 leading-relaxed max-w-md mx-auto">
            Tus datos son tuyos. Ningún profesional puede verlos sin que tú lo autorices,
            y puedes retirar el acceso cuando quieras.
        </p>
        <a href="{{ route('privacidad.politica') }}"
           class="inline-flex items-center gap-1.5 mt-2 text-[11px] font-semibold text-accent-text hover:gap-2.5 transition-all">
            <i class="fa-solid fa-shield-halved text-[10px]" aria-hidden="true"></i>
            Leer la política de privacidad
        </a>
    </footer>

    @stack('scripts')
</body>
</html>
