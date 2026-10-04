<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * La barra de intensidad, probada sobre la página que sale.
 *
 * Hubo una versión de esta prueba que montaba un DOM a mano en Node y metía dentro
 * un `document` de mentira. Pasaba las seis. No vio nada.
 *
 * El motivo está en el fallo que se le escapó. `conectarIntensidad()` buscaba las
 * nueve opciones de emoción con `raiz.querySelectorAll('[data-escala]')`, y en el
 * marcado esas nueve están en el `<fieldset>` de la emoción, FUERA del
 * `[data-intensidad]` que envuelve la barra. La lista salía vacía, el bucle no
 * iteraba, el oyente `change` no se instalaba en ninguna de las nueve, y elegir
 * emoción no repintaba un solo rótulo.
 *
 * El simulacro no podía verlo porque también ponía las opciones dentro del widget:
 * se había escrito copiando lo que el autor creía, no lo que la plantilla genera.
 * Un banco de pruebas a mano verifica el código contra la suposición de quien lo
 * escribió, y contra eso no hay nada que demostrar.
 *
 * Aquí ya no hay DOM inventado. Se pide `/dashboard` de verdad, se entrega ese HTML a
 * `jsdom` y se ejecuta `resources/js/app.js` sin tocarlo dentro de esa ventana. Lo
 * que se afirma son valores observados en el marcado real: si alguien mueve un
 * marcador de sitio, cambia el orden del HTML o mete las opciones en otro
 * contenedor, la prueba lo ve porque lo ve la página.
 *
 * El banco suplica tres cosas, las únicas que `jsdom` no trae: `matchMedia`,
 * `Element.animate` y `Element.getAnimations`. Todo lo demás es el navegador.
 */
class IntensidadBarraTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Los diez peldaños de la escalera, por su nombre.
     *
     * Se comparan contra lo que el guion pone EN PANTALLA, no contra la constante
     * que hay en `app.js`. Si las dos listas se copiaran del mismo sitio, cambiarlas
     * las dos a la vez seguiría dando verde y el cambio habría pasado sin que nadie
     * lo decidiera.
     */
    private const PALABRAS = [
        'Mínima', 'Muy baja', 'Baja', 'Moderada baja', 'Media',
        'Media alta', 'Moderada alta', 'Alta', 'Muy alta', 'Máxima',
    ];

    /**
     * El tono de cada uno de los diez peldaños: cinco bajos, tres medios, dos altos.
     *
     * Va declarado aquí aparte, y no leído de `app.js`, por lo mismo que las diez
     * palabras: si la lista saliera de la constante del guion, cambiarla allí y
     * aquí a la vez seguiría dando verde.
     *
     * El reparto es 5/3/2 y no tres tercios. Diez peldaños en tres tonos iguales
     * no cuadran, así que hay que elegir a quién se le da el margen: aquí la parte
     * alta son los dos últimos, porque «Máxima» solo se alcanza en el 91 y el latido
     * del tono `alto` tiene que reservarse para de verdad lo que está en el tope.
     * El efecto es que `medio` empieza en el 51 y `alto` en el 81, y no antes.
     *
     * Ojo con esto, porque ya salió mal una vez: dar por hecho el 4/3/3 y
     * «arreglar» el marcado que estaba bien.
     */
    private const TONOS = [
        'bajo', 'bajo', 'bajo', 'bajo', 'bajo',
        'medio', 'medio', 'medio',
        'alto', 'alto',
    ];

    /**
     * En qué peldaño cae un valor, según el rango que declara la barra.
     *
     * La fórmula está escrita aquí a mano, y no copiada del guion, para que la
     * prueba no dé verde por lo mismo que el guion se equivoque: cada una está
     * escrita una vez por su cuenta, y si discrepan se nota.
     *
     * @param  array{min: int, max: int}  $rango
     */
    private static function peldano(int $valor, array $rango): int
    {
        $porPeldano = intdiv($rango['max'] - $rango['min'] + 1, 10);

        return max(0, min(9, intdiv($valor - $rango['min'], $porPeldano)));
    }

    private function saltarSiNoHayNavegador(): void
    {
        exec('node --version 2>&1', $salida, $codigo);

        if ($codigo !== 0) {
            $this->markTestSkipped('Node no está instalado: no se puede ejecutar el JavaScript de la aplicación.');
        }

        if (! is_file(dirname(__DIR__, 2).'/node_modules/jsdom/package.json')) {
            $this->markTestSkipped('Falta `jsdom` en node_modules: hace falta para probar el guion sobre el HTML real.');
        }
    }

    /**
     * Los diez cortes de la escalera, palabra y tono, sobre el rango real.
     *
     * Se comprueba la frontera de cada peldaño por las dos partes: el último valor
     * de un peldaño y el primero del siguiente. Un reparto desplazado un valor
     * —`11` donde debería ser `10`, o el último valor quedándose fuera— deja las
     * diez palabras intactas y solo mueve estos cortes, que es justo lo que una
     * comparación de listas de palabras no detecta.
     *
     * El tono va en la misma vuelta porque es el reparto de al lado y no lo
     * cubría ninguna prueba. Aquí se demostró que hacía falta: el tono del marcado
     * se dio por bueno sin comprobarlo, se cambió «a mano» y resultó que el que
     * estaba escrito era el correcto. Una palabra que nadie compara con su color
     * es una palabra que se puede equivocar en silencio.
     */
    public function test_los_diez_peldanos_cortan_justo_en_su_frontera(): void
    {
        $rango = $this->rangoDeLaBarra();
        $estados = $this->enLaPagina('estados', $rango['min'], $rango['max'])['estados'];
        $porValor = array_column($estados, null, 'valor');

        $this->assertCount($rango['max'] - $rango['min'] + 1, $estados, 'No se han recorrido todos los valores de la barra real.');

        $fallos = [];

        foreach ($porValor as $valor => $estado) {
            // El reparto lo decide `peldano()`, que está declarado arriba con su
            // propia fórmula y no copiada del guion.
            $peldano = self::peldano($valor, $rango);

            if ($estado['palabra'] !== self::PALABRAS[$peldano]) {
                $fallos[] = $valor.': «'.$estado['palabra'].'», y el peldaño da «'.self::PALABRAS[$peldano].'»';
            }

            if ($estado['tono'] !== self::TONOS[$peldano]) {
                $fallos[] = $valor.': «'.self::PALABRAS[$peldano].'» sale en tono `'.$estado['tono'].'`, y el peldaño '.$peldano.' va en `'.self::TONOS[$peldano].'`';
            }
        }

        $this->assertSame(
            [],
            $fallos,
            "La escalera no reparte los valores en diez peldaños justos:\n  - ".implode("\n  - ", array_slice($fallos, 0, 12))
        );

        // Las esquinas y el primer corte, dichos aparte para que el fallo se lea sin
        // tener que contar peldaños.
        $this->assertSame(self::PALABRAS[0], $porValor[$rango['min']]['palabra'], 'En el mínimo debería sonar «'.self::PALABRAS[0].'».');
        $this->assertSame(self::PALABRAS[9], $porValor[$rango['max']]['palabra'], 'En el máximo debería sonar «'.self::PALABRAS[9].'».');
        $this->assertSame(self::PALABRAS[0], $porValor[$rango['min'] + 9]['palabra'], 'El valor IX por encima del mínimo sigue en el primer peldaño.');
        $this->assertSame(self::PALABRAS[1], $porValor[$rango['min'] + 10]['palabra'], 'El valor X por encima del mínimo abre el segundo peldaño.');
    }

    /**
     * Elegir UNA emoción repinta los rótulos. Es lo mínimo para que la página sirva
     * de algo, y es exactamente lo que estaba roto.
     *
     * El recorrido es sobre las nueve opciones de la página real, no sobre una de
     * cada familia: el fallo era que no se conectó NINGUNA, y una prueba que mirase
     * una sola no lo distingue de una que funciona.
     */
    public function test_elegir_cualquiera_de_las_emociones_repinta_los_rotulos(): void
    {
        $html = $this->dashboard();

        $lecturas = $this->lecturas($html);
        $opciones = $this->opcionesDeEmocion($html);

        $this->assertGreaterThan(
            4,
            count($opciones),
            'Se esperaban más de cuatro opciones de emoción: si no, se pierde justo el caso de las nueve.'
        );

        $fallos = [];

        foreach ($opciones as $opcion) {
            $lectura = $lecturas[$opcion['familia']] ?? null;

            if ($lectura === null) {
                $fallos[] = '«'.$opcion['nombre'].'» declara la familia `'.$opcion['familia'].'`, que no está en el bloque de lecturas.';

                continue;
            }

            $estado = $this->enLaPagina('elegir', 1, 100, ['opcion' => $opcion['indice'], 'valor' => 86]);

            if ($estado['despues']['titulo'] !== $lectura['titulo']
                || $estado['despues']['minimo'] !== $lectura['min']
                || $estado['despues']['maximo'] !== $lectura['max']) {

                $fallos[] = 'al elegir «'.$opcion['nombre'].'» (familia `'.$opcion['familia'].'`) siguen poniendo `'
                    .$estado['despues']['titulo'].' / '.$estado['despues']['minimo'].' / '.$estado['despues']['maximo']
                    .'`, y deberían poner `'.$lectura['titulo'].' / '.$lectura['min'].' / '.$lectura['max'].'`';
            }
        }

        $this->assertSame([], $fallos, "Elegir emoción no repinta los rótulos:\n  - ".implode("\n  - ", $fallos));
    }

    /**
     * Sin ninguna emoción elegida, el marcado y el guion tienen que decir lo mismo.
     *
     * El campo de emoción es obligatorio y va sin valor por defecto —prefijar una
     * haría que quien solo pulse enviar quedara registrado como si estuviera bien—,
     * así que al abrir la página no hay ninguna marcada y el guion entra por la
     * lectura genérica. Eso está bien. Lo que no puede pasar es que el marcado
     * traiga otra cosa: entonces la página dice una cosa al llegar y otra en cuanto
     * carga el guion, sin que nadie haya tocado nada.
     *
     * Pasaba. El marcado traía los rótulos de la primera emoción y el guion los
     * sustituía por los genéricos un instante después. El comentario de la plantilla
     * además afirmaba que la primera opción era «la que el formulario envía si se
     * pulsa enviar sin tocar nada», y era falso: `required` no marca nada.
     */
    public function test_sin_emocion_elegida_el_marcado_y_el_guion_dicen_lo_mismo(): void
    {
        $html = $this->dashboard();

        $this->assertSame(
            0,
            $this->opcionesMarcadas($html),
            'Se esperaba que al abrir la página no hubiera ninguna opción marcada. Si la hay, esta prueba ya no está mirando lo que cree mirar.'
        );

        $marcado = $this->rotulosDelMarcado($html);
        $alConectar = $this->enLaPagina('conectar')['estados'][0];

        foreach (['titulo', 'minimo', 'maximo'] as $campo) {
            $this->assertSame(
                $marcado[$campo],
                $alConectar[$campo],
                'El `'.$campo.'` del marcado dice «'.$marcado[$campo].'» y al cargar el guion pone «'
                    .$alConectar[$campo].'»: la página cambia de texto sola.'
            );
        }
    }

    /**
     * Y ese texto sin emoción no afirma ninguna familia.
     *
     * Es la contrapartida del anterior: si el texto genérico se quedara corto y
     * fuera el de alguna familia, la coherencia entre marcado y guion seguiría
     * dando verde y la página volvería a mentir de otra manera.
     */
    public function test_el_texto_sin_emocion_no_afirma_ninguna_familia(): void
    {
        $html = $this->dashboard();

        $lecturas = $this->lecturas($html);
        $generico = $this->rotulosDelMarcado($html);

        $this->assertNotEmpty($lecturas, 'No se ha leído el bloque de lecturas.');

        $correspondencias = ['titulo' => 'titulo', 'min' => 'minimo', 'max' => 'maximo'];
        $colisiones = [];

        foreach ($lecturas as $familia => $lectura) {
            foreach ($correspondencias as $clave => $marcador) {
                if (($lectura[$clave] ?? null) === $generico[$marcador]) {
                    $colisiones[] = 'el texto sin emoción elegida dice «'.$lectura[$clave]
                        .'», que es el `'.$clave.'` de la familia `'.$familia.'`';
                }
            }
        }

        $this->assertSame(
            [],
            $colisiones,
            "El texto que se enseña sin elegir emoción dice lo mismo que una familia:\n  - ".implode("\n  - ", $colisiones)
        );
    }

    /**
     * El número, el relleno y la palabra se mueven en el mismo `input`.
     *
     * El relleno es el caso lejano: lo corta una variable que escribe el guion y de
     * la que depende el degradado de la pista. Si se escribiera con otro formato, la
     * barra se quedaría a medio pintar mientras el número de al lado seguiría siendo
     * el correcto: dos verdades en la misma línea.
     */
    public function test_el_numero_el_relleno_y_el_texto_para_el_lector_no_se_desincronizan(): void
    {
        $rango = $this->rangoDeLaBarra();
        $estados = $this->enLaPagina('estados', $rango['min'], $rango['max'])['estados'];
        $porValor = array_column($estados, null, 'valor');
        $recorrido = $rango['max'] - $rango['min'];

        $medios = array_unique([
            $rango['min'],
            $rango['min'] + 1,
            intdiv($rango['min'] + $rango['max'], 2),
            $rango['min'] + 63,
            $rango['max'] - 14,
            $rango['max'],
        ]);

        $revisados = [];

        foreach ($medios as $valor) {
            if ($valor < $rango['min'] || $valor > $rango['max']) {
                continue;
            }

            $estado = $porValor[$valor];
            $revisados[] = $valor;

            $this->assertSame(
                $valor.'%',
                $estado['cifra'],
                'La cifra del valor '.$valor.' no es el número con su símbolo de porcentaje.'
            );

            // El relleno va de 0% a 100% según dónde esté el asa, que recorre de
            // `min` a `max`: en el mínimo la pista queda vacía y en el máximo llena.
            $avance = round(($valor - $rango['min']) / $recorrido * 100).'%';

            $this->assertSame(
                $avance,
                $estado['avance'],
                'El número dice '.$valor.'% y la pista está al '.$estado['avance'].', cuando le tocaría el '.$avance.'.'
            );
        }

        $this->assertGreaterThanOrEqual(4, count($revisados), 'Se han revisado muy pocos valores: el rango de la barra ha cambiado sin que esta prueba se entere.');

        $this->assertSame('0%', $porValor[$rango['min']]['avance'], 'En el mínimo la pista debería estar vacía.');
        $this->assertSame('100%', $porValor[$rango['max']]['avance'], 'En el máximo la pista debería estar llena.');

        // Y el texto del lector dice la palabra y el número. Un range solo con el
        // número no dice si es «casi» o «casi todo», y esa es justo la información
        // que la barra está dando.
        $indice = self::peldano(86, $rango);

        $this->assertSame(
            self::PALABRAS[$indice].', 86 por ciento',
            $porValor[86]['aria'],
            'El texto que lee el lector de pantalla no dice la palabra y el número.'
        );
    }

    /**
     * Elegir emoción cambia los rótulos y NO toca la medida.
     *
     * Lo segundo es lo que importa: si al cambiar de emoción se repintara la barra,
     * se vería moverse un valor que nadie ha tocado.
     */
    public function test_elegir_emocion_no_mueve_la_medida(): void
    {
        $html = $this->dashboard();

        $lecturas = $this->lecturas($html);
        $opciones = $this->opcionesDeEmocion($html);

        $otra = null;

        foreach ($opciones as $opcion) {
            if ($opcion['indice'] > 0) {
                $otra = $opcion;

                break;
            }
        }

        $this->assertNotNull($otra, 'Solo hay una opción de emoción: no hay con qué comparar.');

        $cambio = $this->enLaPagina('elegir', 1, 100, ['opcion' => $otra['indice'], 'valor' => 86]);

        // El banco mueve la barra a 86 ANTES de elegir, precisamente para que la
        // comparación tenga algo que comparar. Si se comparara el estado de salida
        // —donde todo está ya pintado— con el de después, los dos serían iguales se
        // llame o no a `actualizar()`, y la prueba pasaría con el fallo puesto.
        $this->assertSame('86%', $cambio['antes']['cifra'], 'El banco no ha dejado la barra en 86 antes de elegir.');

        foreach (['cifra', 'avance', 'palabra', 'tono', 'aria'] as $campo) {
            $this->assertSame(
                $cambio['antes'][$campo],
                $cambio['despues'][$campo],
                'Al elegir otra emoción se ha movido `'.$campo.'`: se vería moverse un valor que nadie ha tocado.'
            );
        }

        // Y los rótulos sí han cambiado, que es lo que hace que lo de arriba
        // signifique algo: si no cambiara nada, «no se movió la medida» no probaría
        // nada, porque tampoco se habría movido la lectura.
        $lectura = $lecturas[$otra['familia']];

        $this->assertSame($lectura['titulo'], $cambio['despues']['titulo'], 'Los rótulos no han cambiado al elegir otra emoción.');
        $this->assertNotSame($cambio['antes']['titulo'], $cambio['despues']['titulo'], 'El rótulo ya decía lo mismo antes de elegir.');
    }

    /**
     * Al conectar, el nivel se reparte solo.
     *
     * `conectarIntensidad()` aplica el estado inicial al conectarse, y no al
     * primer movimiento. Si no lo hiciera, el número, la palabra y el relleno se
     * quedarían con lo que dejó la plantilla, que puede ser de otra emoción. En
     * este caso la plantilla trae la palabra correcta, así que esta prueba vigila
     * que siga trayéndola.
     */
    public function test_al_conectar_se_reparte_el_estado_inicial(): void
    {
        $rango = $this->rangoDeLaBarra();
        $estado = $this->enLaPagina('conectar')['estados'][0];

        $this->assertSame($rango['valorInicial'].'%', $estado['cifra'], 'La cifra inicial no es la del `value` del marcado.');

        $indice = self::peldano($rango['valorInicial'], $rango);

        $this->assertSame(self::PALABRAS[$indice], $estado['palabra'], 'La palabra inicial no es la del valor inicial.');
        $this->assertNotEmpty($estado['tono'], 'La palabra inicial no tiene tono, así que saldría sin color.');
        $this->assertNotEmpty($estado['avance'], 'El relleno no está escrito al abrir la página, así que la pista se vería vacía.');
        $this->assertNotEmpty($estado['aria'], 'El texto para el lector de pantalla está vacío al abrir la página.');
    }

    /**
     * Sin JavaScript, el número y la palabra que dejó la plantilla no se contradicen.
     *
     * `conectarIntensidad()` repinta el estado inicial al cargar, así que con guion
     * todo cuadra aunque la plantilla venga mal. Pero quien llega con JavaScript
     * apagado, bloqueado o con el fichero todavía sin llegar ve exactamente lo que
     * escribió la plantilla: si el número y la palabra no casan, el rótulo cuenta
     * una medida que no es la de al lado.
     *
     * Esta es la única prueba de la suite que no pasa por el banco, y a propósito:
     * mira la plantilla antes de que corra nada. Todas las demás miran el estado
     * DESPUÉS de conectar, que es donde el guion se ocupa de tapar cualquier
     * cosa que la plantilla traiga mal.
     */
    public function test_sin_javascript_el_numero_y_la_palabra_del_marcado_no_se_contradicen(): void
    {
        $html = $this->dashboard();
        $rango = $this->rangoDeLaBarra();

        $this->assertSame(
            1,
            preg_match('#<span[^>]*data-intensidad-cifra[^>]*>\s*(\d+)%\s*</#', $html, $cifra),
            'No se ha encontrado la cifra inicial de la barra en el marcado.'
        );

        $this->assertSame(
            1,
            preg_match('#<span[^>]*data-intensidad-nivel[^>]*data-nivel="([a-z-]+)"[^>]*>\s*([^<]+?)\s*</#', $html, $nivel),
            'No se ha encontrado la palabra inicial del nivel en el marcado.'
        );

        $this->assertSame(
            $rango['valorInicial'].'%',
            $cifra[1].'%',
            'La cifra del marcado dice «'.$cifra[1].'%» y la barra declara `value="'.$rango['valorInicial'].'"`.'
        );

        $indice = self::peldano($rango['valorInicial'], $rango);

        $this->assertSame(
            self::PALABRAS[$indice],
            trim($nivel[2]),
            'La palabra del nivel del marcado es «'.trim($nivel[2]).'» y el valor '.$rango['valorInicial'].' cae en «'.self::PALABRAS[$indice].'»: sin JavaScript el rótulo cuenta otra medida.'
        );

        $this->assertSame(
            self::TONOS[$indice],
            $nivel[1],
            'La palabra «'.trim($nivel[2]).'» sale con el tono `'.$nivel[1].'`, y el peldaño '.$indice.' es `'.self::TONOS[$indice].'`: sin JavaScript el número y el color no hablan de lo mismo.'
        );
    }

    /* ── Andamiaje ──────────────────────────────────────────────────────────── */

    /**
     * El HTML que sirve `/dashboard` para un estudiante recién creado.
     *
     * Se pide por HTTP y no leyendo la plantilla: el fallo que motivó esta prueba
     * era que la plantilla y el guion no coinciden, y para verlo tiene que valer lo
     * que el navegador recibe, con los bucles `@foreach` ya ejecutados.
     */
    private function dashboard(): string
    {
        return $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();
    }

    /**
     * El texto que la plantilla deja escrito en los tres marcadores.
     *
     * @return array{titulo: string, minimo: string, maximo: string}
     */
    private function rotulosDelMarcado(string $html): array
    {
        $marcadores = [
            'titulo' => 'data-intensidad-titulo',
            'minimo' => 'data-intensidad-minimo',
            'maximo' => 'data-intensidad-maximo',
        ];

        $rotulos = [];

        foreach ($marcadores as $clave => $marcador) {
            $this->assertSame(
                1,
                preg_match('#<span[^>]*'.$marcador.'[^>]*>(.*?)</span>#s', $html, $coincidencia),
                'No se ha encontrado exactamente un `'.$marcador.'`: hay '.$clave.' que no, o ninguno.'
            );

            $rotulos[$clave] = trim($coincidencia[1]);
        }

        return $rotulos;
    }

    /**
     * @return array<string, array{titulo: string, min: string, max: string}>
     */
    private function lecturas(string $html): array
    {
        $this->assertSame(
            1,
            preg_match('#<script type="application/json" data-lecturas-escala>(.*?)</script>#s', $html, $bloque),
            'El bloque JSON con las lecturas de la escala no está en la página.'
        );

        $lecturas = json_decode(trim($bloque[1]), true);

        $this->assertIsArray($lecturas, 'El bloque de lecturas de la escala no es JSON válido.');

        return $lecturas;
    }

    /**
     * Las opciones de emoción del HTML real, con su posición entre las que llevan
     * `data-escala`.
     *
     * La posición es lo que usa el banco para elegir una con `click()`, igual que
     * lo haría quien usa la página. No se parsea el orden de los atributos, que es
     * un detalle de la plantilla que puede cambiar sin que cambie nada.
     *
     * @return array<int, array{indice: int, nombre: string, familia: string}>
     */
    private function opcionesDeEmocion(string $html): array
    {
        preg_match_all('#<input type="radio"[^>]*>#', $html, $etiquetas);

        $opciones = [];

        foreach ($etiquetas[0] as $etiqueta) {
            if (preg_match('#\bname="emocion"#', $etiqueta) !== 1) {
                continue;
            }

            $valor = preg_match('#\bvalue="([^"]*)"#', $etiqueta, $v) === 1 ? $v[1] : '';
            $familia = preg_match('#\bdata-escala="([^"]*)"#', $etiqueta, $f) === 1 ? $f[1] : null;

            $this->assertNotNull($familia, 'La opción de emoción «'.$valor.'» no declara `data-escala`, así que el guion no sabe qué escala es.');

            $opciones[] = [
                'indice' => count($opciones),
                'nombre' => $valor,
                'familia' => $familia,
            ];
        }

        $this->assertNotEmpty($opciones, 'No se ha encontrado ninguna opción de emoción en la página.');

        return $opciones;
    }

    private function opcionesMarcadas(string $html): int
    {
        preg_match_all('#<input type="radio"[^>]*name="emocion"[^>]*\schecked[^a-zA-Z]#', $html, $coincidencias);

        return count($coincidencias[0]);
    }

    /**
     * Lo que la barra declara: su rango y su valor de partida, leídos del marcado.
     *
     * @return array{min: int, max: int, valorInicial: int}
     */
    private function rangoDeLaBarra(): array
    {
        $html = $this->dashboard();

        $this->assertSame(
            1,
            preg_match('#<input type="range"[^>]*name="energia"[^>]*>#', $html, $etiqueta),
            'No se ha encontrado la barra de energía en la página.'
        );

        $rango = [];

        foreach (['min', 'max', 'value'] as $atributo) {
            $this->assertSame(
                1,
                preg_match('#\b'.$atributo.'="(-?\d+)"#', $etiqueta[0], $valor),
                'La barra no declara `'.$atributo.'`.'
            );

            $rango[$atributo] = (int) $valor[1];
        }

        // Si el rango no se reparte en diez partes justas, las pruebas de frontera
        // estarían comprobando otra cosa sin avisar.
        $this->assertSame(
            0,
            ($rango['max'] - $rango['min'] + 1) % 10,
            'La barra va de '.$rango['min'].' a '.$rango['max'].', que no se reparte en diez peldaños justos. Si el rango es a propósito, hay que rehacer el reparto y estas pruebas.'
        );

        $this->assertGreaterThanOrEqual(
            $rango['min'],
            $rango['value'],
            'El `value` inicial de la barra está por debajo de su mínimo.'
        );

        $this->assertLessThanOrEqual(
            $rango['max'],
            $rango['value'],
            'El `value` inicial de la barra está por encima de su máximo.'
        );

        return ['min' => $rango['min'], 'max' => $rango['max'], 'valorInicial' => $rango['value']];
    }

    /**
     * Pasa el HTML real por `jsdom`, ejecuta `resources/js/app.js` y devuelve lo que
     * se ve.
     *
     * `$accion` elige qué hace el banco una vez conectada la página:
     *   - `conectar`: nada. Se queda con el estado inicial, el que ve quien abre.
     *   - `estados`: recorre la barra valor a valor y devuelve el estado de cada uno.
     *   - `elegir`: pone la barra en un valor, elige una emoción y compara.
     *
     * @param  array<string, mixed>  $opciones
     * @return array<string, mixed>
     */
    private function enLaPagina(string $accion, ?int $rangoMin = null, ?int $rangoMax = null, array $opciones = []): array
    {
        $this->saltarSiNoHayNavegador();

        $molde = $this->molde();

        $guionBanco = strtr($molde, [
            '%ACCION%' => json_encode($accion),
            '%OPCIONES%' => (string) json_encode($opciones, JSON_UNESCAPED_UNICODE),
            '%RUTA_HTML%' => $this->fichero($this->dashboard()),
            // El guion se lee con `readFileSync` y se ejecuta con `eval`, así que es una ruta
            // de fichero normal, sin `file:///`.
            '%RUTA_GUION%' => json_encode(str_replace('\\', '/', dirname(__DIR__, 2).'/resources/js/app.js')),
            '%RANGO%' => (string) json_encode(['min' => $rangoMin, 'max' => $rangoMax]),
        ]);

        // El banco se escribe DENTRO del proyecto, no en el directorio temporal: al
        // resolver `import 'jsdom'` Node sube por los directorios buscando
        // `node_modules`, y desde la carpeta temporal del sistema no encuentra el
        // del proyecto. Fuera de ahí el banco no arrancaría nunca y la prueba daría
        // verde sin comprobar nada.
        //
        // Y por el mismo motivo no se pasan variables de entorno: en Windows `exec`
        // va por `cmd /c`, que no admite el `RUTA=valor comando` de POSIX.
        $directorio = storage_path('framework/testing');
        File::ensureDirectoryExists($directorio);

        $archivo = $directorio.'/intensidad-'.bin2hex(random_bytes(6)).'.mjs';

        File::put($archivo, $guionBanco);

        try {
            exec('node '.escapeshellarg($archivo).' 2>&1', $salida, $codigo);
        } finally {
            @unlink($archivo);
        }

        $texto = implode("\n", $salida);

        if ($codigo !== 0) {
            $this->fail("El banco de pruebas no ha podido usar la página real:\n".$texto);
        }

        $decodificado = json_decode($texto, true);

        $this->assertIsArray($decodificado, 'El banco no ha devuelto nada legible: '.$texto);

        return $decodificado;
    }

    /**
     * El banco de pruebas, con lo variable sin sustituir.
     */
    private function molde(): string
    {
        return <<<'JS'
            import { readFileSync } from 'node:fs';
            import { JSDOM } from 'jsdom';

            const ACCION = %ACCION%;
            const OPCIONES = %OPCIONES%;
            const RANGO = %RANGO%;

            const dom = new JSDOM(readFileSync(%RUTA_HTML%, 'utf8'), {
                url: 'http://localhost/dashboard',
                pretendToBeVisual: true,
                // `outside-only` da a la ventana un `eval` propio, que es lo que
                // hace que dentro corran los guiones de la página. Sin esto, `w.eval`
                // es el de Node y el guion se encuentra con que no existe `document`.
                // Los `<script>` del HTML tampoco se ejecutan, que es lo que se
                // quiere: el guion entra a mano, sin los `<script>` de la página.
                runScripts: 'outside-only',
            });

            const w = dom.window;

            // Lo único que `jsdom` no trae. Ninguno de los tres es de la barra: `matchMedia`
            // lo consulta el guion al pintar un módulo, `animate` y `getAnimations`
            // son de las transiciones, y `CSS.escape` lo usan las pestañas para
            // buscar su panel. Viven en el mismo archivo y no se puede dejar el
            // JavaScript a medias para que una cosa no estorbe a otra, así que se
            // ponen. Esta prueba no afirma nada sobre ninguno.
            w.matchMedia = () => ({ matches: false, addEventListener() {}, removeEventListener() {} });
            w.Element.prototype.animate = () => ({ cancel() {}, finish() {} });
            w.Element.prototype.getAnimations = () => [];

            // `CSS.escape` sale de la especificación: lo que no sea letra, número,
            // guion o guion bajo, o un carácter por encima de U+007F, se escribe
            // como `\XXXX`. Es lo bastante fiel para los nombres de módulo, que son
            // de letras y guiones, y si algún día metieran un espacio en un
            // `data-modulo` también saldría bien.
            w.CSS = {
                escape: (valor) => String(valor).replace(/[^a-zA-Z0-9_-￿]/g, (caracter) => {
                    const hex = caracter.codePointAt(0).toString(16).toUpperCase();

                    return '\\' + hex + ' ';
                }),
            };

            // `jsdom` deja el documento en un estado de carga que depende de cómo se
            // construyera. Se fija a `complete` para que `app.js` entre por la rama
            // que llama a `iniciar()` de una vez, y no por la que espera al
            // `DOMContentLoaded`: si no, el banco dependería de un temporizador que
            // no controla.
            Object.defineProperty(w.document, 'readyState', { value: 'complete', configurable: true });

            // El guion va tal cual, sin transformar ni trocear.
            w.eval(readFileSync(%RUTA_GUION%, 'utf8'));

            const raiz = w.document.querySelector('[data-intensidad]');

            if (!raiz) throw new Error('La página no tiene [data-intensidad].');

            const barra = raiz.querySelector('input[type="range"]');

            if (!barra) throw new Error('El widget no tiene la barra.');

            const leer = () => {
                const nivel = raiz.querySelector('[data-intensidad-nivel]');

                return {
                    cifra: raiz.querySelector('[data-intensidad-cifra]').textContent.trim(),
                    palabra: nivel.textContent.trim(),
                    tono: nivel.dataset.nivel,
                    avance: w.getComputedStyle(barra).getPropertyValue('--avance').trim(),
                    aria: barra.getAttribute('aria-valuetext'),
                    titulo: raiz.querySelector('[data-intensidad-titulo]').textContent.trim(),
                    minimo: raiz.querySelector('[data-intensidad-minimo]').textContent.trim(),
                    maximo: raiz.querySelector('[data-intensidad-maximo]').textContent.trim(),
                };
            };

            const estados = [];

            if (ACCION === 'conectar') {
                estados.push(leer());
            }

            if (ACCION === 'estados') {
                const min = RANGO.min === null ? Number(barra.min) : RANGO.min;
                const max = RANGO.max === null ? Number(barra.max) : RANGO.max;

                for (let v = min; v <= max; v++) {
                    barra.value = String(v);
                    barra.dispatchEvent(new w.Event('input', { bubbles: true }));

                    estados.push(Object.assign({ valor: v }, leer()));
                }
            }

            let antes = null;
            let despues = null;

            if (ACCION === 'elegir') {
                // La barra se mueve ANTES de elegir. Al conectar ya está pintado el
                // valor de partida, y comparar ese estado con el de después daría
                // iguales tanto se repinte como si no.
                barra.value = String(OPCIONES.valor);
                barra.dispatchEvent(new w.Event('input', { bubbles: true }));

                antes = leer();

                const opciones = [...w.document.querySelectorAll('[data-escala]')];
                const elegida = opciones[OPCIONES.opcion];

                if (!elegida) {
                    throw new Error(
                        'La página no tiene la opción ' + OPCIONES.opcion + ' de ' + opciones.length + '.'
                    );
                }

                // `click()` y no marcar a mano: en un navegador marca el radio,
                // desmarca el resto del grupo y dispara `change`, que es justo lo que
                // hay que provar.
                elegida.click();

                despues = leer();
            }

            process.stdout.write(JSON.stringify({ estados, antes, despues }));
            JS;
    }

    /**
     * Escribe el HTML a un fichero dentro del proyecto y devuelve la ruta.
     */
    private function fichero(string $contenido): string
    {
        $directorio = storage_path('framework/testing');
        File::ensureDirectoryExists($directorio);

        $ruta = $directorio.'/intensidad-'.bin2hex(random_bytes(6)).'.html';

        File::put($ruta, $contenido);

        register_shutdown_function(function () use ($ruta) {
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        });

        return json_encode(str_replace('\\', '/', $ruta));
    }
}
