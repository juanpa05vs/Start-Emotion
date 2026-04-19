<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Start-Emotion | Acceso')</title>

    {{-- Importamos estilos y fuentes del proyecto --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background-color: #0d0d0d; /* Fondo oscuro del sistema */
        }
    </style>
    @stack('styles')
</head>
<body class="bg-start-dark text-white min-h-screen selection:bg-neon-purple selection:text-white">

    {{-- Contenedor principal para las vistas de Auth --}}
    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
