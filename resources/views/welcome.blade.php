<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S-Emotion | Acompañamiento emocional</title>
    <meta name="description" content="Registra cómo te sientes y compártelo con quien te acompaña, solo cuando tú lo decidas.">

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        } else {
            document.documentElement.classList.remove('light-mode');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --neon-accent: #22d3ee; --neon-accent-rgb: 34, 211, 238; }
    </style>
</head>

{{-- Antes el body llevaba `overflow-hidden` con `min-h-screen`: en cualquier
     pantalla más baja que el contenido, la parte de abajo quedaba cortada sin
     forma de llegar a ella. --}}
<body class="min-h-full flex flex-col">
    <div class="scanline" aria-hidden="true"></div>

    <main class="relative z-10 flex-1 flex items-center px-5 py-14 sm:py-20">
        <div class="w-full max-w-2xl mx-auto">

            {{-- Marca. El guion y el nombre van en el acento, que sale del token
                 y no de un hexadecimal fijo: así el cambio de tema también
                 llega a la portada. --}}
            <div class="flex items-baseline gap-3 mb-10">
                <h1 class="font-orbitron text-4xl sm:text-5xl font-black tracking-tighter adaptive-title">
                    S<span class="text-accent-text">-</span>EMOTION
                </h1>
                <span class="hidden sm:inline text-[10px] uppercase tracking-[0.3em] text-gray-500 font-semibold">
                    Acompañamiento emocional
                </span>
            </div>

            <h2 class="text-2xl sm:text-3xl font-bold leading-snug tracking-tight adaptive-title max-w-xl">
                Registra cómo te sientes y compártelo con quien te acompaña,
                <span class="text-accent-text">solo cuando tú lo decidas.</span>
            </h2>

            <p class="mt-5 text-gray-400 text-[15px] leading-relaxed max-w-xl">
                S-Emotion es una herramienta de acompañamiento emocional. Sirve para
                trabajar con un profesional de psicología, con datos que tú decides
                compartir en cada momento.
            </p>

            {{-- Tres pasos en filas con filete, no en tres tarjetas iguales: la
                 disposición simétrica de "característica, característica,
                 característica" es el patrón que hace que una portada parezca
                 una plantilla.

                 Y tampoco llevan `.tarjeta`: no son pulsables y una portada con
                 tres bloques que se levantan al pasar el ratón parece un catálogo
                 de productos. El movimiento aquí se reserva para lo que de verdad
                 se puede pulsar, que son los dos botones de abajo. --}}
            <dl class="mt-12 border-t border-white/10">
                <div class="flex items-start gap-5 py-5 border-b border-white/10">
                    <dt class="font-mono text-[11px] font-bold text-accent-text pt-0.5 shrink-0 w-8">01</dt>
                    <dd class="text-[14px] text-gray-300 leading-relaxed">
                        Escribes lo que sientes. Queda en tu cuenta, no en un expediente compartido.
                    </dd>
                </div>
                <div class="flex items-start gap-5 py-5 border-b border-white/10">
                    <dt class="font-mono text-[11px] font-bold text-accent-text pt-0.5 shrink-0 w-8">02</dt>
                    <dd class="text-[14px] text-gray-300 leading-relaxed">
                        Conectas con un profesional mediante un código que te da esa persona.
                    </dd>
                </div>
                <div class="flex items-start gap-5 py-5 border-b border-white/10">
                    <dt class="font-mono text-[11px] font-bold text-accent-text pt-0.5 shrink-0 w-8">03</dt>
                    <dd class="text-[14px] text-gray-300 leading-relaxed">
                        Puedes compartir cada bloque por separado, y retirarlo cuando quieras.
                    </dd>
                </div>
            </dl>

            {{-- Entradas. Antes eran dos `rounded-pill` del mismo peso, sin
                 jerarquía: no se distinguía cuál era la principal. --}}
            <div class="mt-11 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                <x-boton :href="route('register')" tamano="grande" iconoDerecha="arrow-right">
                    Crear una cuenta
                </x-boton>
                <x-boton-neutro :href="route('login')" tamano="grande">
                    Ya tengo cuenta
                </x-boton-neutro>
            </div>

            {{-- La promesa de privacidad, antes de la puerta y no escondida en
                 un pie de página. --}}
            <p class="mt-12 border-t border-white/10 pt-6 text-[13px] text-gray-500 leading-relaxed max-w-xl">
                Tus datos son tuyos. Ningún profesional puede verlos sin que tú lo
                autorices, y puedes retirar el acceso cuando quieras.
                <a href="{{ route('privacidad.politica') }}"
                   class="inline-flex items-center gap-1.5 font-semibold text-accent-text transition-colors hover:underline underline-offset-4">
                    <i class="fa-solid fa-shield-halved text-[10px]" aria-hidden="true"></i>
                    Leer la política de privacidad
                </a>
            </p>
        </div>
    </main>
</body>
</html>
