<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * El tema vive entero en `resources/css/app.css`. No hay tokens repartidos por
 * las plantillas ni un archivo de tema aparte, y esa concentración es
 * justamente lo que la vuelve frágil: un comentario mal cerrado en ese archivo
 * no da error de compilación, se come en silencio el bloque que va detrás y los
 * estilos desaparecen de la pantalla sin que nada se entere.
 *
 * Pasó de verdad. Al escribir los botones, un `/*` de apertura se perdió al
 * corregir una línea: `.btn--neutro` quedó dentro de un trozo de CSS basura, el
 * navegador lo descartó sin avisar, y el botón «Terminar sesión» —la única
 * salida de la sesión— se quedó sin filete y con el color heredado. Nada falló,
 * ni `npm run build` ni las 153 pruebas, porque ninguna miraba el CSS.
 *
 * Estas pruebas miran el CSS. Son baratas y no dependen de un navegador.
 */
class HojaDeEstiloTest extends TestCase
{
    /** Reglas que la aplicación da porinexistentes si desaparecen. */
    private const REGLAS = [
        '.btn{',
        '.btn--principal{',
        '.btn--secundario{',
        '.btn--neutro{',
        '.btn--peligro{',
        '.btn--grande{',
        '.btn--pequeno{',
        '.btn:disabled',
        '.escala-likert',
        '.dato-estado[data-nivel=',
        '.aviso-estado[data-estado=',
        '.linea-estado[data-estado=',
        '.rounded-panel{',
        '.rounded-control{',
        '.rounded-pill{',
        'html.light-mode',
        // El sistema de movimiento. Va en la misma lista y por el mismo motivo
        // que el resto: son reglas cuyo efecto es invisible cuando faltan, y
        // por eso nadie se avisa. Un botón sin transición sigue siendo un botón
        // —solo que nunca se levanta— y eso no lo delata ninguna otra prueba.
        '.tarjeta{',
        '.tarjeta:hover',
        '.selector{',
        '.selector[aria-pressed=true]',
        ':where(.campo)',
        '.campo:focus-visible',
        '.campo--hundido',
        '.dato{',
        '.dato--acento',
        '.dato--neutro',
        '.dato--alerta',
        '.dato--vacio',
        '.dato-rotulo{',
        '.dato-tarjeta{',
        '.modulo-pestana{',
        '.modulo-pestana[aria-selected=true]',
        '.intensidad{',
        '.intensidad:focus-visible',
        '.intensidad::-webkit-slider-thumb',
        '.intensidad::-moz-range-thumb',
        '.intensidad-nivel{',
        '.intensidad-nivel[data-nivel=bajo]',
        '.intensidad-nivel[data-nivel=medio]',
        '.intensidad-nivel[data-nivel=alto]',
        '@keyframes latido',
        '@media (prefers-reduced-motion:reduce)',
        // Las entradas de bloque. Igual que el movimiento bajo el cursor, una
        // entrada que falta no rompe nada: el bloque simplemente aparece de
        // golpe. Por eso conviene tenerla en la misma lista que el resto.
        '@keyframes entrada-sube',
        '.aparece,.aparece-grupo>*{',
        '.aparece-grupo',
        '@media (prefers-reduced-motion:no-preference)',
        // Los medidores de datos de la ETAPA 3: las barras que crecen de cero a
        // su valor y el fundido uniforme de la matriz. Igual que el resto, si
        // faltan no rompen nada y por eso conviene tenerlas vigiladas.
        '@keyframes medidor-crece',
        '@keyframes fundido',
        '.medidor-relleno',
        '.fundido{',
        // La ETAPA 4: la respuesta de la opción de emoción, el realce de la fila
        // marcada, el detalle plegable y el menú lateral. Como el resto, son
        // reglas cuyo efecto desaparece en silencio si se pierden.
        '.opcion-emocion',
        '.nav-enlace',
        '[data-plegable]',
        // La ETAPA 5: la cascada y las acciones de la portada. Como el resto,
        // son reglas cuyo efecto desaparece en silencio si se pierden.
        '.acciones-portada',
        '.enlace-portada',
        // El cajón lateral responsive (ETAPA 6, H-5b): el cajón, la cabecera
        // móvil y el velo. Si alguna se pierde, la barra se queda fuera de su
        // sitio o encima del contenido, y ninguna otra prueba mira el CSS.
        '.menu-lateral',
        '.menu-cabecera{',
        '.menu-velo{',
    ];

    private function fuente(): string
    {
        $ruta = base_path('resources/css/app.css');

        $this->assertFileExists($ruta, 'Falta la hoja de estilos del tema.');

        return (string) file_get_contents($ruta);
    }

    /**
     * Todas las plantillas Blade, sin repetir.
     *
     * Lo que importa es que entre TODO. Se hacía con dos `glob`, para bajar a los
     * subdirectorios con el segundo, porque `glob` no es recursivo y un solo
     * asterisco se queda en un nivel.
     *
     * El problema es que en `glob` los dos asteriscos no significan «cualquier
     * profundidad», sino exactamente lo mismo que uno: los dos patrones son de un
     * nivel y de dos, y ninguno baja a la raíz, que es donde está
     * `resources/views/dashboard.blade.php`. De 31 plantillas, esta lista veía
     * 28. Se escapaban `dashboard.blade.php`, `historial.blade.php` y
     * `welcome.blade.php`, y con ellas las nueve guardas de esta clase, que
     * llevaban tiempo dando «todo correcto» sin haberlas abierto.
     *
     * Por cierto, el asterisco doble seguido de barra no se puede escribir
     * literal dentro de este comentario: cierra el bloque antes de tiempo. Le
     * pasa a cualquiera que escriba el patrón para explicar el fallo.
     *
     * `File::allFiles()` sí baja de verdad, y no depende de contar niveles a mano.
     *
     * @return array<int, string>
     */
    private function plantillas(): array
    {
        $archivos = array_map(
            fn ($archivo) => $archivo->getPathname(),
            File::allFiles(resource_path('views'), fn ($archivo) => $archivo->getExtension() === 'php')
        );

        $this->assertNotEmpty($archivos, 'No se encontraron plantillas Blade.');

        return array_values($archivos);
    }

    private function compilada(): string
    {
        $manifiesto = public_path('build/manifest.json');

        if (! file_exists($manifiesto)) {
            $this->markTestSkipped('Falta public/build/manifest.json. Ejecuta `npm run build` antes de las pruebas.');
        }

        $entradas = json_decode((string) file_get_contents($manifiesto), true);
        $css = $entradas['resources/css/app.css']['file'] ?? null;

        $this->assertNotNull($css, 'El manifiesto no apunta a una hoja de estilos.');

        $ruta = public_path('build/'.$css);

        $this->assertFileExists($ruta, 'La hoja compilada que nombra el manifiesto no existe. Ejecuta `npm run build`.');

        return (string) file_get_contents($ruta);
    }

    /**
     * Los comentarios de la fuente están cerrados.
     *
     * Va sobre el archivo y no sobre la salida porque el daño ocurre en la
     * fuente: si el `/*` falta, el comentario deja de ser un comentario.
     */
    public function test_los_comentarios_de_la_fuente_estan_cerrados(): void
    {
        $texto = $this->fuente();

        $longitud = strlen($texto);
        $posicion = 0;
        $linea = 1;
        $abiertos = 0;
        $sospechosos = [];

        while ($posicion < $longitud) {
            $abre = strpos($texto, '/*', $posicion);
            $cierra = strpos($texto, '*/', $posicion);

            // Avanza el contador de líneas hasta el próximo hecho que importa,
            // para poder decir DÓNDE está el problema y no solo que lo hay.
            if ($abre !== false && ($cierra === false || $abre < $cierra)) {
                $abiertos++;
                $posicion = $abre + 2;
            } elseif ($cierra !== false) {
                if ($abiertos === 0) {
                    $sospechosos[] = 'Línea '.$linea.': hay un `*/` que no cierra nada.';
                } else {
                    $abiertos--;
                }

                $posicion = $cierra + 2;
            } else {
                break;
            }

            $linea += substr_count($texto, "\n", $posicion > 2 ? $posicion - 2 : 0, 2);
        }

        if ($abiertos > 0) {
            $sospechosos[] = 'Quedan '.$abiertos.' comentario(s) sin cerrar. Todo lo que venga '
                .'después se descarta sin avisar, y el navegador no dice nada.';
        }

        $this->assertSame([], $sospechosos, implode("\n", $sospechosos));
    }

    /** Ninguna regla del tema se pierde al compilar. */
    public function test_las_reglas_del_tema_llegan_al_navegador(): void
    {
        $css = $this->compilada();
        $faltan = [];

        foreach (self::REGLAS as $regla) {
            if (! str_contains($css, $regla)) {
                $faltan[] = $regla;
            }
        }

        $this->assertSame(
            [],
            $faltan,
            "Estas reglas no están en la hoja compilada:\n  - ".implode("\n  - ", $faltan)
        );
    }

    /**
     * Los botones usan el componente, no la clase escrita a mano.
     *
     * El componente es lo que garantiza que un `<button>` dentro de un
     * formulario sea de tipo `submit`, que el icono lleve `aria-hidden` y que
     * el estado deshabilitado se marque en el atributo y en el estilo a la vez.
     * Reintroducir la clase a mano rompe las tres cosas sin que nada falle.
     *
     * Quedan fuera tres familias que no son botones de acción y por eso no
     * usan el componente: los botones de icono (cuadrados, con `aria-label` y
     * sin texto), los controles segmentados del selector de tema, y las
     * acciones terciarias de fila, que son transparentes hasta que se les pasa
     * el ratón por encima. Por eso la regla busca un RELLENO o una tipografía
     * de acción sin prefijo de variante, que es la marca de lo que se
     * escribía a mano antes.
     */
    public function test_ninguna_vista_escribe_la_apariencia_de_un_boton_a_mano(): void
    {
        $archivos = $this->plantillas();

        // El `(?<![\w:-])` deja fuera `hover:bg-accent`, `sm:bg-accent` y
        // `aria-pressed:border-accent`: solo cuenta lo que el botón es, no lo
        // que le pasa al pasarle el ratón por encima.
        $apariencia = '/(?<![\w:-])(?:bg-(?:accent|black|white|rose|emerald|amber|cyan|purple|blue)|text-accent-ink|text-accent-text|uppercase)/';

        $infractores = [];

        foreach ($archivos as $archivo) {
            $contenido = (string) file_get_contents($archivo);

            if (! preg_match_all('/<button\b[^>]*>/', $contenido, $coincidencias)) {
                continue;
            }

            foreach ($coincidencias[0] as $etiqueta) {
                // Los botones del propio componente llevan `{{ $clases }}`.
                if (str_contains($etiqueta, '{{')) {
                    continue;
                }

                if (! preg_match('/class="([^"]*)"/', $etiqueta, $clase)) {
                    continue;
                }

                if (! preg_match($apariencia, $clase[1])) {
                    continue;
                }

                $infractores[] = basename($archivo).': '.trim(preg_replace('/\s+/', ' ', $etiqueta));
            }
        }

        $this->assertSame(
            [],
            $infractores,
            "Estos botones escriben su propia apariencia en vez de usar `x-boton`:\n  - "
            .implode("\n  - ", $infractores)
        );
    }

    /**
     * El movimiento se declara una vez, no dos.
     *
     * `.btn`, `.tarjeta`, `.campo`, `.selector`, `.modulo-pestana` y
     * `.dato-tarjeta` comparten una única declaración `transition` con las seis
     * propiedades. Cuando además cada bloque traía la suya, aparecía el fallo
     * más traicionero de todo el sistema: `.btn:active { transform: scale(0.99) }`
     * anulaba el `scale(0.95)` común porque las dos reglas tienen la misma
     * especificidad y ganaba la última. El botón se hundía, pero con un hundido
     * distinto del que decía la hoja de estilo, y el escorzo se notaba justo en
     * el gesto más rápido de la interfaz.
     *
     * La comprobación es sobre la fuente: lo que se duplica es la declaración,
     * no el efecto final.
     */
    public function test_el_movimiento_compartido_no_se_declara_dos_veces(): void
    {
        // Los comentarios se van antes de leer los selectores. Cada bloque de
        // esta hoja va precedido de un comentario largo, y si se deja ahí, el
        // «selector» que captura la búsqueda es el propio comentario con `.btn`
        // pegado al final: la comparación falla y la guarda no ve nada. Una
        // prueba que no falla cuando el defecto está puesto no es una guarda.
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $bloques, PREG_SET_ORDER);

        $infractores = [];
        $compartidos = ['.btn', '.tarjeta', '.campo', '.selector', '.modulo-pestana', '.dato-tarjeta'];

        foreach ($bloques as [, $selector, $cuerpo]) {
            if (! str_contains($cuerpo, 'transition')) {
                continue;
            }

            // Las listas de selectores no cuentan: ahí la transición va una vez
            // para las seis clases, que es justo lo que esta prueba exige.
            if (substr_count($selector, ',') > 0) {
                continue;
            }

            $selector = trim($selector);

            foreach ($compartidos as $clase) {
                $esLaMisma = $selector === $clase
                    || str_starts_with($selector, $clase.':')
                    || str_starts_with($selector, $clase.'--')
                    || str_starts_with($selector, $clase.'[aria-')
                    || str_starts_with($selector, $clase.' ');

                if ($esLaMisma) {
                    $infractores[] = $selector.' vuelve a declarar `transition`, y esa clase ya la tiene en la lista compartida.';
                }
            }
        }

        $this->assertSame(
            [],
            $infractores,
            "El movimiento se ha declarado dos veces:\n  - ".implode("\n  - ", $infractores)
        );
    }

    /**
     * El movimiento se apaga cuando el sistema pide menos animación.
     *
     * Bajar la duración de la transición a cero NO es lo mismo que quitar el
     * movimiento: el botón seguiría apareciendo desplazado dos píxeles y un 2 %
     * más grande, solo que de golpe. Quien tiene activado «reducir movimiento» en
     * el sistema ha pedido que las cosas no se muevan, no que se muevan de
     * pronto.
     *
     * Por eso la prueba busca el `transform: none`, y no una duración a cero.
     */
    public function test_el_movimiento_se_apaga_del_todo_si_se_pide_menos_animacion(): void
    {
        $css = $this->fuente();

        if (! preg_match('/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{/', $css, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('No hay ninguna regla para `prefers-reduced-motion: reduce` en la hoja de estilos.');
        }

        $resto = substr($css, $m[0][1]);
        $fin = strpos($resto, "\n}");
        $bloque = $fin === false ? $resto : substr($resto, 0, $fin);

        $faltan = [];

        foreach (['.btn:hover', '.btn:active', '.tarjeta:hover', '.modulo-pestana:hover', '.selector:hover', '.nav-enlace:hover'] as $estado) {
            if (! preg_match('/'.preg_quote($estado, '/').'[^{}]*\{[^{}]*transform:\s*none/', $bloque)) {
                $faltan[] = $estado.' no anula su `transform` dentro del bloque de movimiento reducido.';
            }
        }

        $this->assertSame([], $faltan, implode("\n", $faltan));
    }

    /**
     * Ninguna vista declara el foco de un campo por su cuenta.
     *
     * Antes quince campos repetían `transition-colors focus:border-accent
     * focus:outline-none`, y el efecto práctico era que cada campo escrito a
     * mano era un sitio donde el anillo de foco se había borrado. El
     * `.campo` existe para que eso no vuelva: el anillo se dibuja con `outline`,
     * se ve aunque el ancestro tenga `overflow: hidden`, y el filete se tiñe de
     * acento en vez de desaparecer.
     *
     * `focus:outline-none` sin más es el síntoma: un campo sin anillo de foco
     * no se puede usar con teclado, y no hay ninguna otra prueba que lo note.
     */
    public function test_ninguna_vista_quita_el_anillo_de_foco_a_mano(): void
    {
        $archivos = $this->plantillas();

        $infractores = [];

        foreach ($archivos as $archivo) {
            $lineas = file($archivo, FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lineas as $indice => $linea) {
                // Los comentarios Blade hablan del problema y lo mencionan de
                // verdad; mirarlos daría falsos positivos por docstrings.
                $limpia = trim(preg_replace('/\{\{--.*?--\}\}/s', '', $linea) ?? '');

                if ($limpia === '' || str_contains($limpia, '{{--') || str_contains($limpia, '--}}')) {
                    continue;
                }

                if (preg_match('/focus:outline-none/', $limpia)) {
                    $infractores[] = basename($archivo).':'.($indice + 1).'  '.$limpia;
                }
            }
        }

        $this->assertSame(
            [],
            $infractores,
            "Estos sitios quitan el anillo de foco a mano en vez de usar `.campo`:\n  - "
            .implode("\n  - ", $infractores)
        );
    }

    /**
     * Un grupo de módulos siempre trae su estado inicial en el marcado.
     *
     * `<x-modulos>` no pinta nada si solo hay un módulo, porque un panel con una
     * sola pestaña es un panel con un título de más. Y cada grupo declara
     * exactamente un `<x-modulo activo>`: si no, quien llegue sin JavaScript no
     * ve nada, y si fueran dos, ve los dos.
     */
    public function test_los_grupos_de_modulos_declaran_un_solo_panel_activo(): void
    {
        $archivos = $this->plantillas();

        // El propio componente no se mira a sí mismo.
        $archivos = array_filter(
            $archivos,
            fn ($ruta) => ! in_array(basename($ruta), ['modulos.blade.php', 'modulo.blade.php'], true)
        );

        $grupos = 0;
        $infractores = [];

        foreach ($archivos as $archivo) {
            $contenido = (string) file_get_contents($archivo);

            preg_match_all('/<x-modulos\b(.*?)>(.*?)<\/x-modulos>/s', $contenido, $coincidencias, PREG_SET_ORDER);

            foreach ($coincidencias as $grupo) {
                $grupos++;

                preg_match_all('/<x-modulo\b[^>]*>/', $grupo[2], $paneles);

                $activos = array_filter(
                    $paneles[0],
                    fn ($p) => (bool) preg_match('/\bactivo\b/', $p)
                );

                if (count($activos) !== 1) {
                    $infractores[] = basename($archivo).' · '.trim(preg_replace('/\s+/', ' ', $grupo[1]))
                        .' declara '.count($activos).' panel(es) `activo` y hace falta exactamente 1.';
                }
            }
        }

        $this->assertGreaterThan(0, $grupos, 'No se ha encontrado ningún grupo de módulos: el componente no se está usando.');
        $this->assertSame([], $infractores, implode("\n", $infractores));
    }

    /**
     * El anillo de foco no vuelve al acento puro.
     *
     * `--neon-accent` es un color para superficies oscuras. Sobre el panel de
     * papel de modo claro da 1.65:1, y el umbral de un indicador de foco es 3:1.
     * Medido en el navegador: con el acento puro, recorrer la aplicación con
     * teclado en modo claro no dejaba ver dónde estaba el control.
     *
     * `--color-accent-text` es el mismo acento ya mezclado hacia la tinta en
     * claro, así que en oscuro el anillo sigue siendo el cian de siempre y en
     * claro pasa a dar 6.34:1. Es el mismo remapeo que ya hacen el texto, los
     * bordes y los fondos del tema.
     *
     * Nadie lo va a notar si vuelve a cambiarse: el anillo se sigue viendo, solo
     * que peor. Por eso está en una prueba y no en una nota.
     */
    public function test_el_anillo_de_foco_no_usa_el_acento_puro(): void
    {
        $css = $this->fuente();

        preg_match_all('/([^{}]*focus[^{}]*)\{([^{}]*)\}/', $css, $bloques, PREG_SET_ORDER);

        $infractores = [];

        foreach ($bloques as [, $selector, $cuerpo]) {
            // Los comentarios hablan del problema y lo nombran de verdad.
            $selector = trim(preg_replace('#/\*.*?\*/#s', '', $selector) ?? '');
            $cuerpo = trim(preg_replace('#/\*.*?\*/#s', '', $cuerpo) ?? '');

            if ($selector === '' || ! str_contains($cuerpo, 'outline')) {
                continue;
            }

            if (str_contains($cuerpo, 'var(--neon-accent)')) {
                $infractores[] = $selector.' dibuja el anillo con `--neon-accent`, que en modo claro no llega a 3:1.';
            }
        }

        $this->assertSame(
            [],
            $infractores,
            "El anillo de foco se ha vuelto a pintar con el acento puro:\n  - ".implode("\n  - ", $infractores)
        );
    }

    /**
     * La pista de la barra se pinta con un degradado, y nada la aplana por debajo.
     *
     * La barra de intensidad es el único `input[type="range"]` de la aplicación y
     * su tramo recorrido lo dibuja un `background-image` con el corte en
     * `--avance`. El modo claro tenía su propia regla para esa pista:
     * `background-color: #cbd5e1 !important`. Ese `!important` sobre el fondo no
     * la estorbaba: la anulaba. El degradado se pinta ENCIMA del fondo, así que
     * en modo claro el tramo recorrido se veía a medias y el color de acento no
     * llegaba a la pantalla.
     *
     * El fallo no se ve en `npm run build`, no lo ve ninguna otra prueba y en
     * modo oscuro es invisible: solo aparece al cambiar de tema, y entonces
     * parece un retoque de estilo y no un error. La causa concreta es el
     * `!important`, así que lo que se busca es exactamente eso.
     */
    public function test_nada_aplana_el_relleno_de_la_barra_con_un_important_de_fondo(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $bloques, PREG_SET_ORDER);

        $infractores = [];

        foreach ($bloques as [, $selector, $cuerpo]) {
            $selector = trim($selector);

            // `input:not([type="range"])` NOMBRA la barra para dejarle fuera. Una
            // regla así no le pone nada encima, y contarla sería un falso positivo
            // que obliga a la prueba a distinguir un caso que no ocurre.
            if (str_contains($selector, ':not(')) {
                continue;
            }

            if (! str_contains($selector, '[type=range]') && ! str_contains($selector, '[type="range"]')) {
                continue;
            }

            if (preg_match('/background(?:-color|-image)?\s*:[^;}]*!important/', $cuerpo)) {
                $infractores[] = $selector.' pone el fondo de la pista con `!important`, y eso aplana el degradado de lo recorrido en modo claro.';
            }
        }

        $this->assertSame(
            [],
            $infractores,
            "El relleno de la barra se ha vuelto a tapar:\n  - ".implode("\n  - ", $infractores)
        );
    }

    /**
     * El widget de la barra no lleva manejadores en línea.
     *
     * El número y la palabra del nivel se actualizaban con un `oninput` escrito a
     * mano en la barra, y los rótulos que cambian con la emoción con un
     * `onchange` en cada opción. Se sustituyen por `conectarIntensidad()` en
     * `resources/js/app.js`, y el motivo no es el orden: un atributo en línea no
     * sobrevive a una CSP con `script-src 'self'`, no se puede probar, y obliga a
     * que el comportamiento se lea en un atributo de tres líneas en lugar de en el
     * archivo que lo tiene todo junto.
     *
     * Lo que se comprueba es SOLO el widget, no las quince vistas que usan
     * manejadores en línea —confirmaciones de borrado, recargas, el juego—, porque
     * eso sí es otro trabajo y aquí no se ha tocado.
     *
     * Se mira cada etiqueta que lleva un marcador del widget —la barra, la cifra,
     * la palabra, el contenedor y las nueve opciones de emoción— y se busca un
     * `on…=` cualquiera dentro. El patrón anterior era `oninput="…slider…"`, que
     * solo reconocía el código exacto que se había quitado: con cualquier otro
     * manejador en línea la prueba pasaba sin ver nada.
     *
     * Que el guion esté CONECTADO no se comprueba aquí. Una versión de esta prueba
     * raspaba el cuerpo de `iniciar()` buscando la llamada, y no servía para nada:
     * comentar la línea deja el texto dentro del cuerpo, así que la comprobaba sin
     * ver nada. Eso lo hace `IntensidadBarraTest`, que importa `app.js` y mueve la
     * barra de verdad.
     */
    public function test_el_widget_de_la_barra_no_tiene_manejadores_en_linea(): void
    {
        $dashboard = (string) file_get_contents(resource_path('views/dashboard.blade.php'));

        // Cada etiqueta de apertura que lleva un marcador del widget, MÁS la
        // propia barra. La barra es el caso importante y no tiene marcador: lo
        // tiene el `<div>` que la envuelve, así que buscar solo `data-intensidad`
        // la dejaba fuera y la guarda pasaba con un `oninput` puesto en ella.
        preg_match_all('/<[a-z][^>]*\bdata-(?:intensidad|escala)[a-z-]*[^>]*>/i', $dashboard, $marcadas);
        preg_match_all('/<input\b[^>]*\btype="range"[^>]*>/i', $dashboard, $barras);

        $marcadas[0] = array_merge($marcadas[0], $barras[0]);

        $this->assertGreaterThan(
            2,
            count($marcadas[0]),
            'No se han encontrado las etiquetas de la barra: el widget se ha movido o ha cambiado de marcadores.'
        );

        $infractores = [];

        foreach ($marcadas[0] as $etiqueta) {
            if (preg_match('/\son[a-z]+\s*=/i', $etiqueta, $atributo)) {
                $infractores[] = trim($atributo[0]).' → '.trim((string) preg_replace('/\s+/', ' ', $etiqueta));
            }
        }

        $this->assertSame(
            [],
            $infractores,
            "El widget de la barra se mueve desde la plantilla en vez de desde `conectarIntensidad()`:\n  - "
            .implode("\n  - ", $infractores)
        );

        // Y que nadie vuelva a llamar a la función que se quitó. Si alguien
        // compone `onchange="updateSliderLabels(…)"` sin darse cuenta, el
        // navegador lanza un ReferenceError en cada cambio de emoción y el rótulo
        // se queda congelado. Un ReferenceError en la consola no sale en ninguna
        // prueba.
        $infractores = [];

        foreach ($this->plantillas() as $archivo) {
            if (str_contains((string) file_get_contents($archivo), 'updateSliderLabels')) {
                $infractores[] = basename($archivo);
            }
        }

        $this->assertSame(
            [],
            $infractores,
            "Estas plantillas vuelven a llamar a `updateSliderLabels()`, que ya no existe:\n  - ".implode("\n  - ", $infractores)
        );
    }

    /**
     * El latido no baja de la opacidad que deja el texto por encima de 3:1.
     *
     * `@keyframes latido` late con opacidad, y esa opacidad ES el contraste del
     * texto mientras late. Bajarla más no hace el aviso más discretito: lo hace
     * ilegible. Estuvo en 0.55, que dejaba la palabra rose-400 en 2.73 sobre la
     * superficie oscura, por debajo del 3.1 que la WCAG concede siquiera al texto
     * grande.
     *
     * La cifra no sale de un cálculo: el umbral está medido sobre los colores que
     * la hoja declara, en los dos modos, y 0.8 es el valor más bajo que lo
     * respeta. Lo que se vigila aquí es que nadie la vuelva a bajar sin que esta
     * prueba se queje, que es lo que había pasado: bajarla es cambiar el color de
     * un texto sin tocar ningún color.
     *
     * Esta prueba mira la fuente y no el navegador, así que no mide el contraste:
     * vigila el número del que depende. Quien quiera el número medido lo tiene en
     * el comentario del propio `@keyframes latido`.
     */
    public function test_el_latido_no_apaga_el_texto_que_avisa(): void
    {
        $css = $this->fuente();

        // El cierre del bloque se busca como una llave al principio de una línea, no
        // como la primera llave que aparece. El cuerpo de un keyframe tiene llaves
        // propias en cada paso (`50% { opacity: .8; }`), así que `(.*?)\}` se
        // paraba en la del primer paso y solo veía `opacity: 1`: el valle le salía
        // 1 y la guarda pasaba con el 0.55 puesto. Se vio al falsificarla.
        $this->assertSame(
            1,
            preg_match('/@keyframes\s+latido\s*\{(.*?)^\}/ms', $css, $bloque),
            'No se ha encontrado el cuerpo de `@keyframes latido` en la hoja.'
        );

        preg_match_all('/opacity:\s*([\d.]+)/', $bloque[1], $opacidades);

        // Menos de dos no es que el keyframe sea raro: es que el cuerpo está mal
        // leído. Un keyframe de opacidad tiene al menos el pico y el valle.
        $this->assertGreaterThanOrEqual(
            2,
            count($opacidades[1]),
            'Se han encontrado '.count($opacidades[1]).' opacidades en `@keyframes latido`. Se esperaba al menos dos, una por paso del ciclo.'
        );

        $valle = min(array_map('floatval', $opacidades[1]));

        $this->assertSame(
            1.0,
            max(array_map('floatval', $opacidades[1])),
            'El latido debería subir a opacidad 1 en algún punto del ciclo: si el máximo no es 1, el texto parpadea entre dos valores y ya no es un latido.'
        );

        $this->assertGreaterThanOrEqual(
            0.8,
            $valle,
            'El latido baja a '.$valle.' de opacidad, y por debajo de 0.8 el texto que late deja de cumplir 3:1. Si es a propósito, hay que rehacer el cálculo del comentario de `@keyframes latido`.'
        );
    }

    /**
     * La escalera de diez peldaños y sus tres tonos no cambian por accidente.
     *
     * Son el contenido de un control, y eso no es una razón para que se puedan
     * retocar sin que nadie lo decida. Además están en un archivo que ninguna otra
     * prueba abre: `resources/js/app.js` no se mira en ningún sitio, y aquí no hay
     * un ejecutor de pruebas de JavaScript en `package.json` que lo cubriera.
     *
     * La comprobación es sobre la fuente y no sobre el comportamiento, que es
     * menos de lo que sería: si la escalera cambia de longitud, el cálculo del
     * índice sigue repartiendo bien el rango y nadie se entera. Lo que sí atrapa
     * esta prueba es el cambio sin decisión, que es justo lo que pasa con una
     * lista de palabras: nadie la nota hasta que alguien la ha cambiado dos
     * veces.
     */
    public function test_los_diez_peldaños_de_la_escala_no_cambian_sin_que_alguien_lo_decida(): void
    {
        $ruta = base_path('resources/js/app.js');

        $this->assertFileExists($ruta, 'Falta resources/js/app.js.');

        $js = (string) file_get_contents($ruta);

        // Los comentarios se van antes de buscar: nombran los tonos y las
        // palabras al explicarlos, y un acierto dentro de un comentario no cuenta.
        $js = (string) preg_replace('#/\*.*?\*/#s', '', $js);

        $palabras = $this->tablaJs($js, 'NIVELES_INTENSIDAD');
        $tonos = $this->tablaJs($js, 'TONOS_INTENSIDAD');

        $this->assertSame([
            'Mínima', 'Muy baja', 'Baja', 'Moderada baja', 'Media',
            'Media alta', 'Moderada alta', 'Alta', 'Muy alta', 'Máxima',
        ], $palabras, 'La escalera de nivel ha cambiado sin que nadie lo decidiera.');

        $this->assertCount(count($palabras), $tonos, 'Cada peldaño necesita un tono: hay '.count($palabras).' palabras y '.count($tonos).' tonos.');

        // Tres escalones y no diez colores. Diez tonos en diez peldaños de diez
        // puntos no se distinguen entre sí y rompen el acento único de la
        // aplicación; el alto es el único que late, y por eso es el único que se
        // escribe con un nombre propio en el CSS.
        $this->assertSame(
            ['bajo', 'medio', 'alto'],
            array_values(array_unique($tonos)),
            'Los escalones de tono han cambiado sin que nadie lo decidiera.'
        );

        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        foreach (array_unique($tonos) as $tono) {
            // La fuente escribe el valor entre comillas; el minificador de la
            // hoja publicada las quita. Se aceptan las dos formas.
            $conComillas = ".intensidad-nivel[data-nivel='".$tono."']";
            $sinComillas = '.intensidad-nivel[data-nivel='.$tono.']';

            if (! str_contains($css, $conComillas) && ! str_contains($css, $sinComillas)) {
                $this->fail('El tono `'.$tono.'` lo decide el guion pero no tiene regla en la hoja de estilos: sale sin color.');
            }
        }
    }

    /** Las palabras de un array plano de JavaScript, en su orden. */
    private function tablaJs(string $js, string $constante): array
    {
        $patron = '/const\s+'.$constante.'\s*=\s*\[(.*?)\]/s';

        if (! preg_match($patron, $js, $m)) {
            $this->fail('No se encuentra el array `'.$constante.'` en resources/js/app.js.');
        }

        preg_match_all("/'([^']*)'/", $m[1], $entradas);

        return $entradas[1];
    }

    /**
     * Las cuatro variantes de botón se usan.
     *
     * Un componente que nadie usa es código muerto; uno que se usa una sola vez
     * probablemente sobra. Esta prueba no obliga a usar las cuatro, avisa de que
     * la escala se ha ido por la derecha en algún sentido u otro.
     */
    public function test_la_escala_de_botones_no_crece_sin_que_alguien_lo_vea(): void
    {
        $css = $this->fuente();

        preg_match_all('/^\.btn--([a-z-]+)\s*\{/m', $css, $coincidencias);

        // `grande` y `pequeno` no son variantes de color, son medidas: se
        // combinan con cualquiera de las otras y no compiten con ellas.
        $medidas = ['grande', 'pequeno'];

        $variantes = array_values(array_diff(
            array_unique($coincidencias[1]),
            $medidas
        ));

        sort($variantes);

        $this->assertSame(
            ['neutro', 'peligro', 'principal', 'secundario'],
            $variantes,
            'La lista de variantes de botón ha cambiado sin que nadie lo decidiera: '.implode(', ', $variantes)
        );

        // Y las medidas son exactamente dos, por el mismo motivo.
        $medidasDefinidas = array_values(array_intersect(array_unique($coincidencias[1]), $medidas));

        sort($medidasDefinidas);

        $this->assertSame(
            $medidas,
            $medidasDefinidas,
            'Las medidas de botón han cambiado sin que nadie lo decidiera: '.implode(', ', $medidasDefinidas)
        );
    }

    /**
     * Una entrada solo mueve opacidad y desplazamiento.
     *
     * Es la misma disciplina que el resto del movimiento: nada que fuerce
     * recálculo de maquetación en cada fotograma. Un `height`, un `width` o un
     * `filter` en el keyframe de entrada empuja el contenido de al lado y hace
     * que el bloque no «aparezca», sino que «empuje», y con veinte bloques en
     * cascada la página entera tiembla.
     */
    public function test_las_entradas_solo_mueven_opacidad_y_desplazamiento(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        $this->assertSame(
            1,
            preg_match('/@keyframes\s+entrada-sube\s*\{(.*?)^\}/ms', $css, $bloque),
            'No se ha encontrado el cuerpo de `@keyframes entrada-sube`.'
        );

        preg_match_all('/([a-z-]+)\s*:/', $bloque[1], $propiedades);

        $permitidas = ['opacity', 'transform'];

        $this->assertGreaterThanOrEqual(2, count($propiedades[1]), 'El keyframe de entrada apenas declara propiedades.');

        $this->assertSame(
            [],
            array_values(array_diff(array_unique($propiedades[1]), $permitidas)),
            'El keyframe de entrada anima propiedades que no son opacidad o desplazamiento, y eso re-maqueta el contenido vecino en cada fotograma.'
        );
    }

    /**
     * La entrada no se activa salvo que se acepte movimiento.
     *
     * No es que se apague después: el bloque entero va dentro de
     * `prefers-reduced-motion: no-preference`, así que quien ha pedido menos
     * movimiento no ve un fotograma intermedio. La prueba lo fija porque es
     * fácil sacar la regla de la consulta al añadir una utilidad nueva, y
     * entonces quien pidió menos movimiento vuelve a recibir la cascada.
     */
    public function test_las_entradas_solo_se_activan_si_se_acepta_movimiento(): void
    {
        $css = $this->fuente();

        if (! preg_match('/@media\s*\(prefers-reduced-motion:\s*no-preference\)\s*\{/', $css, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('Las entradas no están dentro de `prefers-reduced-motion: no-preference`.');
        }

        $resto = substr($css, $m[0][1]);
        $fin = strpos($resto, "\n}");
        $bloque = $fin === false ? $resto : substr($resto, 0, $fin);

        $this->assertStringContainsString('.aparece', $bloque, 'La utilidad `.aparece` no está dentro de la consulta `no-preference`.');
        $this->assertStringContainsString('.aparece-grupo', $bloque, 'La utilidad `.aparece-grupo` no está dentro de la consulta `no-preference`.');
    }

    /**
     * La cascada de un grupo se detiene en el duodécimo hijo.
     *
     * Un grupo puede ser una tabla de treinta filas. Si cada fila esperase su
     * turno, la última entraría casi un segundo y medio después de la primera y
     * la tabla no se podría leer hasta entonces. El desfase solo tiene sentido
     * en los primeros bloques, los que el ojo sigue a la vez: a partir del
     * decimotercero vuelve a cero y todas las filas restantes entran juntas.
     */
    public function test_la_cascada_de_un_grupo_se_detiene_en_doce(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        for ($k = 1; $k <= 12; $k++) {
            $this->assertSame(
                1,
                preg_match('/\.aparece-grupo\s*>\s*:nth-child\('.$k.'\)\s*\{\s*--paso:\s*'.($k - 1).'\s*;?\s*\}/', $css),
                'Al hijo número '.$k.' de un grupo le toca el paso '.($k - 1).' y no lo tiene.'
            );
        }

        $this->assertSame(
            1,
            preg_match('/\.aparece-grupo\s*>\s*:nth-child\(n\+13\)\s*\{\s*--paso:\s*0\s*;?\s*\}/', $css),
            'La cascada no se reinicia a partir del hijo decimotercero: una tabla larga tardaría una eternidad en quedar legible.'
        );

        $this->assertSame(
            0,
            preg_match('/\.aparece-grupo\s*>\s*:nth-child\(14\)\s*\{/', $css),
            'Hay un peldaño número 14 en la cascada: la escalera se ha estirado sin que nadie lo decidiera.'
        );
    }

    /**
     * La entrada rellena hacia atrás, no hacia delante.
     *
     * `backwards` mantiene oculto el bloque mientras le toca el turno y, al
     * terminar, lo devuelve a su estado natural. Con `forwards` o `both`, el
     * `transform: none` del último fotograma se quedaría pegado y anularía el
     * levantamiento al pasar el ratón: la animación de entrada habría roto el
     * hover de cada tarjeta y cada botón que la llevase. Ese fallo no lo ve
     * ninguna otra prueba.
     */
    public function test_la_entrada_no_deja_pegado_el_desplazamiento(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        preg_match_all('/animation:\s*entrada-sube\s+([^;]+);/', $css, $m);

        $this->assertGreaterThanOrEqual(
            2,
            count($m[1]),
            'No se han encontrado las dos utilidades que usan `entrada-sube` (`.aparece` y `.aparece-grupo > *`).'
        );

        foreach ($m[1] as $corta) {
            $this->assertStringContainsString('backwards', $corta, 'La entrada no rellena hacia atrás: el bloque quedaría invisible mientras espera su turno.');
            $this->assertStringNotContainsString('both', $corta, 'La entrada usa `both` y dejaría pegado el `transform` final, rompiendo el hover.');
            $this->assertStringNotContainsString('forwards', $corta, 'La entrada usa `forwards` y dejaría pegado el `transform` final, rompiendo el hover.');
        }
    }

    /**
     * Con movimiento reducido, la entrada se apaga del todo.
     *
     * La consulta `no-preference` ya impide que la animación llegue a
     * activarse, pero esta regla es la red de seguridad: devuelve el bloque a
     * su estado natural —visible— sin fotograma intermedio, y cubre el caso de
     * un navegador que ignore la consulta. Es análoga a la regla que anula el
     * movimiento bajo el cursor.
     */
    public function test_las_entradas_se_apagan_del_todo_si_se_pide_menos_animacion(): void
    {
        $css = $this->fuente();

        if (! preg_match('/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{/', $css, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('No hay ninguna regla para `prefers-reduced-motion: reduce` en la hoja de estilos.');
        }

        $resto = substr($css, $m[0][1]);
        $fin = strpos($resto, "\n}");
        $bloque = $fin === false ? $resto : substr($resto, 0, $fin);

        $this->assertSame(
            1,
            preg_match('/\.aparece[^{]*\{[^{}]*animation:\s*none/', $bloque),
            'Con movimiento reducido, las entradas no se anulan del todo: el contenido podría quedar a medio aparecer.'
        );
    }

    /**
     * Un medidor solo mueve `transform`, y la matriz solo opacidad.
     *
     * Es la misma disciplina que el resto del movimiento: nada que re-maquete el
     * contenido vecino en cada fotograma. Un medidor que creciera animando su
     * `width` empujaría la cifra que tiene al lado; por eso el relleno mide
     * siempre el carril entero y lo que se desplaza es el bloque.
     *
     * El fundido de la matriz es solo opacidad a propósito: la matriz se lee de
     * un vistazo y ninguno de sus valores puede esperar su turno para ser
     * legible.
     */
    public function test_los_medidores_solo_mueven_transform_y_la_matriz_opacidad(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        $this->assertSame(
            1,
            preg_match('/@keyframes\s+medidor-crece\s*\{(.*?)^\}/ms', $css, $medidor),
            'No se ha encontrado el cuerpo de `@keyframes medidor-crece`.'
        );

        preg_match_all('/([a-z-]+)\s*:/', $medidor[1], $propiedadesMedidor);

        $this->assertSame(
            [],
            array_values(array_diff(array_unique($propiedadesMedidor[1]), ['transform'])),
            'El medidor anima propiedades que no son `transform`, y eso re-maqueta la cifra que tiene al lado.'
        );

        $this->assertSame(
            1,
            preg_match('/@keyframes\s+fundido\s*\{(.*?)^\}/ms', $css, $fundido),
            'No se ha encontrado el cuerpo de `@keyframes fundido`.'
        );

        preg_match_all('/([a-z-]+)\s*:/', $fundido[1], $propiedadesFundido);

        $this->assertSame(
            [],
            array_values(array_diff(array_unique($propiedadesFundido[1]), ['opacity'])),
            'El fundido de la matriz anima algo que no es opacidad: sus columnas no deben moverse al aparecer.'
        );
    }

    /**
     * Los medidores y el fundido solo se activan si se acepta movimiento.
     */
    public function test_los_medidores_solo_se_animan_si_se_acepta_movimiento(): void
    {
        $css = $this->fuente();

        if (! preg_match('/@media\s*\(prefers-reduced-motion:\s*no-preference\)\s*\{/', $css, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('Los medidores no están dentro de `prefers-reduced-motion: no-preference`.');
        }

        $resto = substr($css, $m[0][1]);
        $fin = strpos($resto, "\n}");
        $bloque = $fin === false ? $resto : substr($resto, 0, $fin);

        $this->assertStringContainsString('.medidor-relleno', $bloque, 'El medidor `.medidor-relleno` no está dentro de `no-preference`.');
        $this->assertStringContainsString('.fundido', $bloque, 'El fundido `.fundido` no está dentro de `no-preference`.');
    }

    /**
     * Con movimiento reducido, el medidor y el fundido se apagan del todo.
     */
    public function test_los_medidores_se_apagan_del_todo_si_se_pide_menos_animacion(): void
    {
        $css = $this->fuente();

        if (! preg_match('/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{/', $css, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('No hay ninguna regla para `prefers-reduced-motion: reduce` en la hoja de estilos.');
        }

        $resto = substr($css, $m[0][1]);
        $fin = strpos($resto, "\n}");
        $bloque = $fin === false ? $resto : substr($resto, 0, $fin);

        $this->assertSame(
            1,
            preg_match('/\.medidor-relleno[^{]*\{[^{}]*animation:\s*none/', $bloque),
            'Con movimiento reducido, el medidor no se anula del todo: la barra podría quedarse a medio crecer.'
        );

        $this->assertSame(
            1,
            preg_match('/\.fundido[^{]*\{[^{}]*animation:\s*none/', $bloque),
            'Con movimiento reducido, el fundido de la matriz no se anula del todo.'
        );
    }

    /**
     * Las barras de datos crecen desde `--valor` y ya no animan su ancho.
     *
     * El número que se ve al lado sigue siendo exactamente el mismo; lo único que
     * cambia es cómo se pinta el relleno. Se comprueba sobre las dos vistas que
     * tienen barras de datos (historial y distribución), y se vigila que no
     * vuelva el `style="width: …%"`, que es el patrón que re-maquetaba.
     */
    public function test_las_barras_de_datos_usan_el_medidor_y_no_el_ancho(): void
    {
        $historial = (string) file_get_contents(resource_path('views/historial.blade.php'));
        $validacion = (string) file_get_contents(resource_path('views/admin/validacion.blade.php'));

        $this->assertStringContainsString('medidor-relleno', $historial, 'La barra de energía del historial no usa `.medidor-relleno`.');
        $this->assertStringContainsString('--valor:', $historial, 'La barra de energía del historial no recibe su valor por `--valor`.');

        $this->assertStringContainsString('medidor-relleno', $validacion, 'La barra de distribución no usa `.medidor-relleno`.');
        $this->assertStringContainsString('--valor:', $validacion, 'La barra de distribución no recibe su valor por `--valor`.');

        $this->assertStringNotContainsString('style="width: {{ $item->energia }}%"', $historial, 'La barra de energía volvió a animar su ancho.');
        $this->assertStringNotContainsString('style="width: {{ $porcentaje }}%"', $validacion, 'La barra de distribución volvió a animar su ancho.');
    }

    /**
     * La matriz aparece de una vez, sin escalonar sus filas.
     *
     * Se leen valores; un desfase por fila retrasa la lectura de las últimas y no
     * aporta nada. La matriz usa `.fundido` (solo opacidad, sin desfase) y no la
     * cascada `.aparece-grupo`, que sí sigue usándose en la lista de distribución.
     */
    public function test_la_matriz_aparece_de_una_vez_sin_escalonar(): void
    {
        $validacion = (string) file_get_contents(resource_path('views/admin/validacion.blade.php'));

        $this->assertStringContainsString('class="fundido divide-y', $validacion, 'La matriz de confusión ya no aparece con el fundido uniforme.');
        $this->assertStringNotContainsString('class="aparece-grupo divide-y', $validacion, 'La matriz de confusión ha vuelto a escalonar sus filas.');
        $this->assertStringContainsString('class="aparece-grupo space-y-2"', $validacion, 'La lista de distribución perdió su cascada.');
    }

    /**
     * El gráfico respeta el movimiento reducido y configura su curva.
     *
     * La gráfica se dibuja una vez al abrir su módulo; la preferencia se consulta
     * en ese momento y no al cargar. Si falta la comprobación, quien ha pedido
     * menos movimiento ve igualmente la línea trazándose punto a punto.
     */
    public function test_el_grafico_configura_su_animacion_y_respeta_el_movimiento_reducido(): void
    {
        $dashboard = (string) file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertStringContainsString('prefers-reduced-motion: reduce', $dashboard, 'El gráfico no consulta la preferencia de movimiento.');
        $this->assertStringContainsString('animation: animar', $dashboard, 'El gráfico no condiciona su animación a la preferencia de movimiento.');
        $this->assertStringContainsString("easing: 'easeOutQuart'", $dashboard, 'El gráfico no declara su curva de transición.');
        $this->assertStringContainsString('delay: (contexto)', $dashboard, 'El gráfico no escalona el trazado de sus puntos.');
    }

    /**
     * La cuenta atrás conserva su lógica y solo suaviza el pintado del ancho.
     *
     * Se mantiene `width` y no se pasa a `transform`: el relleno lleva un degradado
     * y un `scaleX` lo comprimiría. La transición no toca el valor, la precisión
     * ni la duración del juego, que siguen calculándose igual en `pintarReloj`.
     */
    public function test_la_cuenta_atras_conserva_su_ancho_y_solo_suaviza_el_pintado(): void
    {
        $diagnostico = (string) file_get_contents(resource_path('views/minijuegos/diagnostico.blade.php'));

        $this->assertStringContainsString('medidor-progreso', $diagnostico, 'La cuenta atrás no usa la transición discreta del medidor de progreso.');
        $this->assertStringContainsString('barra.style.width', $diagnostico, 'La cuenta atrás dejó de pintarse por `width`: la lógica del juego ha cambiado.');

        // La barra de la cuenta atrás no debe pasar a transformarse: deformaría
        // su degradado. Se comprueba sobre la propia línea del marcado.
        $this->assertSame(
            1,
            preg_match('/id="temp-bar"\s*\n?\s*class="[^"]*medidor-progreso[^"]*"[\s\S]*?style="width: 100%"/', $diagnostico),
            'La barra de la cuenta atrás ha cambiado de técnica: debe conservar su `width` inicial.'
        );
    }

    /**
     * La emoción marcada se distingue de las demás sin mover la rejilla.
     *
     * El color del estado marcado ya lo aporta Tailwind con `peer-checked:`. La
     * ETAPA 4 añade la respuesta de tamaño, y lo hace con `transform` para no
     * re-maquetar las nueve cajas, además de encender el icono. Las dos
     * transiciones se miden con el reloj común, no con un valor suelto.
     */
    public function test_la_opcion_de_emocion_responde_con_el_sistema(): void
    {
        $css = $this->fuente();

        $this->assertSame(
            1,
            preg_match('/\.opcion-emocion:has\(input:checked\)\s*>\s*span\s*\{[^{}]*transform:\s*scale\(/', $css),
            'La opción de emoción marcada no se separa con un `transform`.'
        );

        $this->assertSame(
            1,
            preg_match('/\.opcion-emocion\s*>\s*span\s*\{[^{}]*var\(--movimiento-duracion\)/', $css),
            'La opción de emoción no mide su transición con la duración del sistema.'
        );

        $this->assertSame(
            1,
            preg_match('/\.opcion-emocion:has\(input:checked\)\s*>\s*span\s*>\s*i\s*\{[^{}]*opacity:\s*1/', $css),
            'El icono de la emoción marcada no se enciende del todo.'
        );
    }

    /**
     * La fila marcada del historial se distingue también por el fondo.
     *
     * El cuadro ya cambia de color al marcarse, pero en una fila de cinco
     * columnas la marca se pierde de vista. El realce sale de `:has()`, sobre el
     * mismo `checked` que ya conoce el lector de pantalla.
     */
    public function test_la_fila_marcada_del_historial_se_realza(): void
    {
        $css = $this->fuente();

        $this->assertSame(
            1,
            preg_match('/tbody tr:has\(\.record-checkbox:checked\)\s*\{/', $css),
            'La fila marcada del historial no recibe un realce propio.'
        );
    }

    /**
     * El detalle del historial se pliega dentro del sistema de movimiento.
     *
     * La fila ya no se muestra con un simple `hidden`: el botón lleva los datos
     * que conectan la animación y el guion central la resuelve reutilizando la
     * misma duración y curva. La guarda vigila las tres piezas —los datos en la
     * vista, la función en el guion y su llamada al arrancar— y que no quede un
     * manejador de plegado en línea.
     */
    public function test_el_detalle_del_historial_se_pliega_con_el_sistema(): void
    {
        $vista = (string) file_get_contents(resource_path('views/historial.blade.php'));
        $guion = (string) file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('data-plegable', $vista, 'El botón de detalle no conecta con el sistema de plegado.');
        $this->assertStringContainsString('data-plegable-cuerpo', $vista, 'El cuerpo plegable no está marcado para poder medir su altura.');
        $this->assertStringNotContainsString('toggleTelemetryPayload', $vista, 'El historial conserva un manejador de plegado propio.');

        $this->assertStringContainsString('function conectarPlegables', $guion, 'El guion no define la conexión de paneles plegables.');
        $this->assertStringContainsString('sinMovimiento()', $guion, 'El plegado no consulta la preferencia de movimiento.');
        $this->assertStringContainsString("classList.add('hidden')", $guion, 'El plegado no conmuta la clase `hidden` que usa el marcado.');
        $this->assertStringContainsString('conectarPlegables();', $guion, 'El plegado no se conecta al arrancar.');
    }

    /**
     * La escala Likert mide su transición con el mismo reloj que el resto.
     *
     * Antes usaba `0.15s ease`, una duración propia: el mismo control respondía
     * a un ritmo que no era el de ningún otro. La guarda lo comprueba sobre la
     * fuente, para que no vuelva a colarse un valor suelto.
     */
    public function test_la_escala_likert_usa_el_movimiento_compartido(): void
    {
        $css = $this->fuente();

        $this->assertSame(
            1,
            preg_match('/\.escala-likert label\s*\{[^{}]*var\(--movimiento-duracion\)/', $css),
            'La escala Likert no usa la duración del sistema.'
        );

        $this->assertSame(
            0,
            preg_match('/\.escala-likert label\s*\{[^{}]*0\.15s/', $css),
            'La escala Likert conserva su duración suelta de 0.15s.'
        );
    }

    /**
     * La portada entra en cascada y sus acciones usan el sistema de movimiento.
     *
     * La entrada de los seis bloques reutiliza `.aparece-grupo`, que ya vive
     * dentro de `prefers-reduced-motion: no-preference` y se anula por completo
     * con movimiento reducido: no es un sistema aparte. La microinteracción de
     * la flecha se escribe sobre el propio icono, con `transform` y solo bajo
     * `hover: hover`; su transición NO empieza por `.btn` —si lo hiciera, esta
     * clase tendría un reloj propio y la guarda del movimiento compartido lo
     * denunciaría—. Y el enlace de privacidad dejó `transition-colors` de
     * Tailwind para medirse con los mismos tokens.
     */
    public function test_la_portada_usa_el_sistema_de_movimiento_compartido(): void
    {
        $vista = (string) file_get_contents(resource_path('views/welcome.blade.php'));
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $this->fuente());

        $this->assertStringContainsString(
            'aparece-grupo',
            $vista,
            'La portada perdió la cascada de entrada: falta `aparece-grupo` en el contenedor.'
        );

        $this->assertStringContainsString(
            'acciones-portada',
            $vista,
            'Las acciones de la portada no están agrupadas para la microinteracción del CTA.'
        );

        $this->assertStringContainsString(
            'enlace-portada',
            $vista,
            'El enlace de privacidad no usa la clase que lo mide con el sistema.'
        );

        $this->assertStringNotContainsString(
            'transition-colors',
            $vista,
            'La portada conserva una transición suelta de Tailwind en vez de los tokens del sistema.'
        );

        // La flecha se adelanta con `transform`, dentro de `hover: hover`.
        $this->assertSame(
            1,
            preg_match('/@media\s*\(hover:\s*hover\)\s*\{[^{}]*\.acciones-portada\s+\.btn--principal:hover\s*>\s*i:last-child\s*\{[^{}]*transform:\s*translateX\(3px\)/', $css),
            'La flecha del CTA no se adelanta con `transform` dentro de la consulta de puntero.'
        );

        // Y se anula del todo con movimiento reducido.
        if (! preg_match('/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{/', $css, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('No hay ninguna regla para `prefers-reduced-motion: reduce` en la hoja de estilos.');
        }

        $resto = substr($css, $m[0][1]);
        $fin = strpos($resto, "\n}");
        $bloque = $fin === false ? $resto : substr($resto, 0, $fin);

        $this->assertSame(
            1,
            preg_match('/\.acciones-portada[^{}]*\{[^{}]*transform:\s*none/', $bloque),
            'Con movimiento reducido, la flecha del CTA no se anula del todo y saltaría tres píxeles de golpe.'
        );
    }

    /**
     * El latido tiene un solo ritmo, escrito una vez.
     *
     * La cuenta atrás de puntos del juego y el escalón «alto» de la barra de
     * intensidad laten igual. Antes cada regla repetía el ritmo a mano, de modo
     * que ajustarlo exigía tocar dos sitios que podían divergir. Ahora hay dos
     * tokens y las dos reglas los referencian. El comentario que explica el
     * cambio no menciona el valor numérico a propósito: contarlo aquí daría un
     * falso positivo con el propio texto que documenta la decisión.
     */
    public function test_el_latido_comparte_un_unico_ritmo(): void
    {
        $css = $this->fuente();

        $this->assertSame(
            1,
            substr_count($css, '1.6s'),
            'El ritmo del latido está suelto en más de un sitio: debería vivir solo en el token `--movimiento-latido`.'
        );

        $this->assertStringContainsString(
            '--movimiento-latido: 1.6s;',
            $css,
            'No se encuentra el token `--movimiento-latido`.'
        );

        $this->assertSame(
            2,
            substr_count($css, 'var(--movimiento-latido) var(--movimiento-latido-curva) infinite'),
            'Las dos reglas que laten no referencian el mismo token de ritmo.'
        );
    }

    /**
     * No queda código de animación sin uso.
     *
     * `@keyframes glow` y `@keyframes float` —con sus tokens `--animate-glow-pulse`
     * y `--animate-float`— y la utilidad `text-outline` estaban definidos pero no
     * los usaba ninguna vista ni un solo guion, y Tailwind no los emitía en el CSS
     * compilado. Se han retirado; esta guarda evita que vuelvan en silencio. De
     * paso fija que las animaciones que SÍ se usan siguen en el build, para que
     * una limpieza futura no se lleve por delante una viva.
     */
    public function test_no_queda_codigo_de_animacion_sin_uso(): void
    {
        $css = $this->fuente();

        $muertos = [
            '@keyframes glow',
            '@keyframes float',
            '--animate-glow-pulse',
            '--animate-float',
            '@utility text-outline',
            '.animate-float',
        ];

        foreach ($muertos as $muerto) {
            $this->assertStringNotContainsString($muerto, $css, 'Sigue en la hoja el código sin uso: '.$muerto);
        }

        $compilada = $this->compilada();

        foreach (['.animate-glow-pulse', '@keyframes glow', '@keyframes float', '.text-outline'] as $muerto) {
            $this->assertStringNotContainsString($muerto, $compilada, 'Sigue en el CSS compilado el código sin uso: '.$muerto);
        }

        foreach (['@keyframes latido', '@keyframes entrada-sube', '@keyframes medidor-crece', '@keyframes fundido', '.animate-pulse', '.animate-ping'] as $vivo) {
            $this->assertStringContainsString($vivo, $compilada, 'Desapareció del build una animación que sí se usa: '.$vivo);
        }
    }

    /**
     * La tarjeta del minijuego acota las propiedades que anima.
     *
     * Traía la transición implícita de todas las propiedades. La tarjeta solo
     * cambia cuatro —`transform`, `opacity`, `background-color` y
     * `border-color`—, así que se declaran explícitas: los colores y los bordes
     * siguen animándose y lo único que desaparece es el barrido de propiedades
     * que nadie cambia.
     */
    public function test_la_tarjeta_del_minijuego_acota_sus_propiedades(): void
    {
        $diagnostico = (string) file_get_contents(resource_path('views/minijuegos/diagnostico.blade.php'));

        $this->assertStringNotContainsString(
            'transition-all',
            $diagnostico,
            'La tarjeta del minijuego volvió a la transición implícita de todas las propiedades.'
        );

        $this->assertStringContainsString(
            'transition-[transform,opacity,background-color,border-color]',
            $diagnostico,
            'La tarjeta del minijuego no declara la lista explícita de propiedades.'
        );
    }
}
