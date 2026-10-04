<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidad | S-Emotion</title>

    {{-- Esta página carga `app.css` pero no usa `layouts/app`, así que antes de
         mover el sistema de diseño a `app.css` sus clases `font-orbitron` y
         `bg-accent-soft` no existían para ella: el documento más importante del
         programa se veía con la tipografía por defecto del navegador. --}}
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --neon-accent: #22d3ee; --neon-accent-rgb: 34, 211, 238; }
    </style>
</head>

{{-- Documento legal: no hay `min-h-screen` + `overflow-hidden`, para que se
     pueda leer entero y llegar al final con la rueda o con el dedo. --}}
<body class="min-h-full">

<div class="scanline" aria-hidden="true"></div>

<div class="relative z-10 max-w-3xl mx-auto px-5 sm:px-8 py-12 sm:py-16">

    <nav class="flex items-center justify-between gap-4">
        <a href="{{ route('welcome') }}"
           class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 transition-colors hover:text-accent-text">
            <i class="fa-solid fa-arrow-left text-[10px]" aria-hidden="true"></i>
            Volver al inicio
        </a>
        <span class="font-orbitron text-sm font-black tracking-tighter adaptive-title">
            S<span class="text-accent-text">-</span>EMOTION
        </span>
    </nav>

    <header class="mt-10 pb-10 border-b border-white/10">
        <h1 class="font-orbitron text-3xl sm:text-4xl font-black tracking-tight adaptive-title">
            Política de <span class="text-accent-text">privacidad</span>
        </h1>
        <p class="text-gray-500 text-[13px] mt-3">
            S-Emotion · Universidad · Última actualización: {{ now()->format('d/m/Y') }}
        </p>
    </header>

    {{-- La síntesis va primero y destacada: es lo que alguien necesita saber
        antes de leer las siete secciones. --}}
    <div class="mt-10 rounded-panel border border-accent/30 bg-accent-soft px-6 py-5">
        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-accent-text mb-2">En una frase</p>
        <p class="text-[15px] leading-relaxed text-gray-200">
            Tus datos son tuyos. Se usan para que tú veas cómo estás, y se comparten con un profesional
            de psicología <strong class="text-white">solo si tú lo autorizas</strong> y mientras siga autorizado.
        </p>
    </div>

    {{-- El cuerpo del documento va a 15px con interlínea amplia. Antes era
         `text-sm` en mayúsculas con tracking amplio: el terminal appliqué a un
         texto que tiene que leerse con atención. --}}
    <div class="mt-14 space-y-12 text-[15px] leading-relaxed">

        <section>
            <h2 class="flex items-baseline gap-3 font-orbitron text-lg font-bold tracking-tight adaptive-title mb-4">
                <span class="font-mono text-[11px] text-accent-text">01</span>Qué datos guardamos
            </h2>
            <p class="mb-3 text-gray-300">Guardamos únicamente lo que registras tú:</p>
            <ul class="space-y-2.5 text-gray-400">
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Tu cuenta:</strong> nombre, correo, edad y contraseña (guardada cifrada, nunca en texto legible).</span></li>
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Tus registros diarios:</strong> cómo te sentiste, tu energía, tu estrés y la nota que escribiste.</span></li>
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Tus evaluaciones:</strong> las respuestas que diste en los cuestionarios de SISCO.</span></li>
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Tus partidas del juego:</strong> cuántas jugaste, cuánto tardaste y tu ritmo de respuesta.</span></li>
            </ul>
            <p class="mt-4 text-[13px] text-gray-500">
                No registramos tu ubicación, no leemos tus mensajes, y no usamos herramientas de terceros
                que rastreen tu navegación.
            </p>
        </section>

        <section>
            <h2 class="flex items-baseline gap-3 font-orbitron text-lg font-bold tracking-tight adaptive-title mb-4">
                <span class="font-mono text-[11px] text-accent-text">02</span>Quién puede ver tus datos
            </h2>

            {{-- Antes eran tres tarjetas iguales apiladas. Una fila por rol con
                 filete se lee como una lista de responsabilidades, que es lo
                 que es, y no como tres cajas intercambiables. --}}
            <dl class="border-t border-white/10">
                <div class="py-5 border-b border-white/10">
                    <dt class="text-gray-100 font-bold mb-1.5">Tú.</dt>
                    <dd class="text-[13px] text-gray-500">
                        Siempre tienes acceso completo a todo lo tuyo, desde la pantalla de tu cuenta.
                    </dd>
                </div>

                <div class="py-5 border-b border-white/10">
                    <dt class="text-gray-100 font-bold mb-1.5">Un profesional de psicología, si tú lo autorizas.</dt>
                    <dd class="text-[13px] text-gray-500">
                        El profesional ve <strong class="text-gray-300">solo las categorías que marcaste</strong> y
                        únicamente mientras el consentimiento siga vigente. Puedes cambiar lo que compartes o
                        retirarlo por completo en cualquier momento.
                    </dd>
                </div>

                <div class="py-5 border-b border-white/10">
                    <dt class="text-gray-100 font-bold mb-1.5">El administrador del sistema, con una salvedad.</dt>
                    <dd class="text-[13px] text-gray-500 space-y-2.5">
                        <p>
                            El administrador gestiona las cuentas y la configuración técnica. <strong class="text-gray-300">No
                            ve el contenido de tus registros ni tus evaluaciones</strong>, ni siquiera las pantallas donde
                            un psicólogo las consulta. Administrar el sistema no abre la puerta a los historiales clínicos:
                            para consultar datos de un estudiante se necesita igual un consentimiento vigente.
                        </p>
                        <p>
                            Lo único que el administrador consulta del funcionamiento del sistema son <strong class="text-gray-300">cifras
                            agregadas</strong>: cuántos participantes hay, cuántas partidas se registraron y cómo rindió el modelo.
                            Son números sobre el conjunto, no resultados de personas. Los datos de investigación que se descargan
                            para analizar están separados de tu nombre, tu correo y tu edad.
                        </p>
                        <p>
                            El administrador <strong class="text-gray-300">tampoco usa las pantallas de autoobservación</strong>
                            (inicio, historial, calendario, actividades). No tiene tus registros porque no tiene acceso a ellos:
                            esas pantallas son la herramienta de quien se observa a sí mismo, y su función es administrar cuentas.
                        </p>
                    </dd>
                </div>
            </dl>
        </section>

        <section>
            <h2 class="flex items-baseline gap-3 font-orbitron text-lg font-bold tracking-tight adaptive-title mb-4">
                <span class="font-mono text-[11px] text-accent-text">03</span>Cómo funciona tu consentimiento
            </h2>
            <ol class="space-y-3 text-gray-400 list-decimal list-inside">
                <li>Tu profesional te entrega un código. No existe ninguna forma de que él te "agregue" sin que tú lo aceptes.</li>
                <li>Entras el código en <strong class="text-gray-200">Mis datos</strong> y eliges qué compartir: registros, evaluaciones, partidas, o las que quieras.</li>
                <li>Desde ese momento el profesional ve esa información y solo esa.</li>
                <li>Puedes <strong class="text-gray-200">retirar el acceso cuando quieras</strong>. El efecto es inmediato.</li>
            </ol>
            <p class="mt-4 text-[13px] text-gray-500">
                Cuando retiras el consentimiento, el profesional deja de tener acceso, pero nosotros conservamos
                la constancia de que existió (fechas, no contenido). Esto sirve para que tú puedas auditar
                quién tuvo acceso a tus datos y durante cuánto tiempo.
            </p>
        </section>

        <section>
            <h2 class="flex items-baseline gap-3 font-orbitron text-lg font-bold tracking-tight adaptive-title mb-4">
                <span class="font-mono text-[11px] text-accent-text">04</span>El juego y la evaluación automática
            </h2>
            <p class="mb-3 text-gray-400">
                El sistema analiza tus patrones de uso para mostrarte <strong class="text-gray-200">una orientación</strong>
                general. Esto <strong class="text-gray-200">no es un diagnóstico</strong>: no determina si tienes
                una condición psicológica ni reemplaza una evaluación profesional.
            </p>
            <p class="text-gray-400">
                Los resultados de esta evaluación automática <strong class="text-gray-200">tampoco se comparten
                con tu profesional</strong> salvo que compartas la categoría del juego. Es decir: el análisis
                automático es tuyo por defecto.
            </p>
        </section>

        <section>
            <h2 class="flex items-baseline gap-3 font-orbitron text-lg font-bold tracking-tight adaptive-title mb-4">
                <span class="font-mono text-[11px] text-accent-text">05</span>Tus derechos
            </h2>
            <p class="mb-3 text-gray-300">Puedes ejercer estos derechos en cualquier momento, sin justificarte:</p>
            <ul class="space-y-2.5 text-gray-400">
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Saber</strong> qué compartiste, con quién y desde cuándo.</span></li>
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Cambiar</strong> las categorías que compartes, añadiendo o quitando.</span></li>
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Retirar</strong> el consentimiento y cortar el acceso al instante.</span></li>
                <li class="flex gap-3"><span class="text-accent-text select-none">•</span><span><strong class="text-gray-200">Eliminar</strong> tu cuenta y todos tus datos.</span></li>
            </ul>
            <p class="mt-4 text-[13px] text-gray-500">
                Para los tres primeros no necesitas hablar con nadie: está en tu pantalla de
                <strong class="text-gray-300">Mis datos</strong>. Para el último, escríbenos desde tu perfil.
            </p>
        </section>

        <section>
            <h2 class="flex items-baseline gap-3 font-orbitron text-lg font-bold tracking-tight adaptive-title mb-4">
                <span class="font-mono text-[11px] text-accent-text">06</span>Tu seguridad
            </h2>
            <p class="text-gray-400">
                Tu contraseña se guarda cifrada y nunca se muestra. El acceso a las áreas de professionals está
                protegido con códigos de autorización, y hay límites al número de intentos de acceso para
                bloquear ataques de fuerza bruta.
            </p>
        </section>

        <section>
            <h2 class="flex items-baseline gap-3 font-orbitron text-lg font-bold tracking-tight adaptive-title mb-4">
                <span class="font-mono text-[11px] text-accent-text">07</span>Responsabilidad
            </h2>
            <p class="text-gray-400">
                S-Emotion es una herramienta de apoyo y autoobservación. <strong class="text-gray-200">No sustituye
                la atención psicológica ni psiquiátrica.</strong> Si estás en crisis o tienes pensamientos de
                hacerte daño, busca ayuda profesional de inmediato o contacta a los servicios de emergencia
                de tu ciudad.
            </p>
        </section>
    </div>

    <div class="mt-14 pt-8 border-t border-white/10 flex flex-wrap gap-3">
        @auth
            <x-boton :href="route('privacidad.index')" iconoDerecha="arrow-right">
                Ir a mis datos
            </x-boton>
        @else
            <x-boton :href="route('register')" iconoDerecha="arrow-right">
                Crear mi cuenta
            </x-boton>
        @endauth
        <x-boton-neutro :href="route('welcome')">
            Volver al inicio
        </x-boton-neutro>
    </div>

    <p class="text-[12px] text-gray-600 mt-10">
        Documento redactado como parte de un proyecto académico. Última versión en el repositorio del proyecto:
        <code class="font-mono text-gray-500">PRIVACIDAD.md</code>
    </p>
</div>
</body>
</html>
