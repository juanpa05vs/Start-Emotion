<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start-Emotion | Game On</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;900&display=swap" rel="stylesheet">
</head>
<body class="bg-start-dark text-white flex items-center justify-center min-h-screen overflow-hidden">
    <main class="relative z-10 text-center">
        <h1 class="text-7xl md:text-9xl font-black italic tracking-tighter animate-float">
            <span class="text-white">START</span><br>
            <span class="text-neon-cyan drop-shadow-[0_0_15px_#22D3EE]">EMOTION</span>
        </h1>
        <div class="mt-12">
            <a href="/login" class="px-10 py-4 border-2 border-neon-cyan text-neon-cyan font-bold rounded-full hover:bg-neon-cyan hover:text-black transition-all">
                PRESS START
            </a>
        </div>
    </main>
</body>
</html>
