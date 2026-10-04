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

        foreach (['.btn:hover', '.btn:active', '.tarjeta:hover', '.modulo-pestana:hover', '.selector:hover'] as $estado) {
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
}
