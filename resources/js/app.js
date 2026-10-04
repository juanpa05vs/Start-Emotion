/* ───────────────────────────────────────────────────────────────────────────
   Start-Emotion · interacciones de página

   Aquí solo hay dos cosas, y las dos se resuelven sin dependencias: alternar
   módulos y avisar de los envíos que Tardan. Todo lo demás vive en atributos
   `data-*` del HTML, para que el comportamiento se pueda leer leyendo la
   plantilla.

   No hay Alpine.js ni Vue ni nada parecido en `package.json`. Añadir una
   biblioteca de reactividad para esto sería pagar una dependencia completa por
   unos treinta event listeners.
   ─────────────────────────────────────────────────────────────────────────── */

/** Respeta la preferencia del sistema. Se consulta en cada cambio y no se
 *  cachea, porque alguien puede activarla desde el sistema operativo con la
 *  pestaña ya abierta. */
const sinMovimiento = () =>
  window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const DURACION_MODULO = 200;

/* Aviso de que el módulo ya es visible y medible.
 *
 * Sin esto, cualquier cosa que necesite dimensiones reales —una gráfica, un
 * mapa, un contador de líneas— se inicializa con 0×0 si su módulo no era el
 * primero, y no vuelve a dibujarse sola.
 *
 * Va en su propia función porque tiene que salir en los DOS caminos: con
 * animación y sin ella. Cuando se escribía solo al final del camino animado,
 * quien tuviera «reducir movimiento» activado en el sistema se quedaba con la
 * gráfica en blanco y no tenía forma de recuperarla sin recargar. */
const avisarVisible = (panel) =>
  panel.dispatchEvent(new CustomEvent('modulo:visible', { bubbles: false }));

/* ── Módulos ────────────────────────────────────────────────────────────────
 *
 * Un grupo de pestañas y sus paneles. La marca para saber qué panel pertenece a
 * qué pestaña es `data-modulo="<nombre>"` en los dos lados: la pestaña lo lleva
 * en `data-modulo-panel` apuntando al panel que controla, y el panel lleva su
 * propio nombre en `data-modulo`. Es la misma información puesta en dos sitios,
 * pero evita depender de que el orden del HTML coincida con el de las pestañas,
 * que es el error clásico al reordenar una lista dentro de medio año.
 *
 * El estado visible lo lleva el atributo `hidden`, no una clase: es lo que leen
 * el lector de pantalla y la búsqueda por teclado. Por eso el panel inicial se
 * establece en el HTML y no al hacer clic, y un módulo mal puesto se vería desde
 * el principio en lugar de desaparecer de golpe.
 */
function conectarModulos() {
  document.querySelectorAll('[data-modulos]').forEach((grupo) => {
    const pestanas = Array.from(
      grupo.querySelectorAll('[data-modulo-panel]')
    ).filter((el) => el.matches('button, a, [role="tab"]'));

    if (pestanas.length < 2) return;

    const paneles = new Map();
    pestanas.forEach((pestana) => {
      const panel = grupo.querySelector(
        `[data-modulo="${CSS.escape(pestana.dataset.moduloPanel)}"]`
      );
      if (panel) paneles.set(pestana, panel);
    });

    if (paneles.size < 2) return;

    let animacionActual = null;

    const mostrar = (pestana) => {
      if (pestana.getAttribute('aria-selected') === 'true') return;

      // Una animación en curso se cancela antes de empezar la siguiente. Sin
      // esto, si se pulsan dos pestañas seguidas, la primera sigue adelante
      // después de la segunda y deja a la vista el panel equivocado.
      if (animacionActual) animacionActual.cancel();

      pestanas.forEach((otra) => {
        const activa = otra === pestana;
        otra.setAttribute('aria-selected', activa ? 'true' : 'false');
        // `roving tabindex`: el grupo es un solo punto de entrada al teclado y
        // dentro se navega con las flechas. Si todas las pestanas fueran
        // tabulables, Tab recorreria el grupo entero antes de llegar al panel.
        otra.setAttribute('tabindex', activa ? '0' : '-1');
      });

      paneles.forEach((panel, otraPestana) => {
        if (otraPestana === pestana) return;
        panel.hidden = true;
        panel.getAnimations?.().forEach((a) => a.cancel());
      });

      const entrante = paneles.get(pestana);
      entrante.hidden = false;

      if (sinMovimiento()) {
        // Sin animación no hay a quién esperar, así que el aviso sale ahora.
        avisarVisible(entrante);
        return;
      }

      // La entrada es opacidad más un desplazamiento corto hacia arriba. Se usa
      // `Element.animate()` y no una clase CSS porque alternar dos bloques pide
      // saber cuándo ha terminado la salida antes de decidir cuál queda: eso es
      // una promesa, y con WAAPI el navegador limpia la animación al terminar
      // sin dejar clases que luego haya que quitar a mano.
      animacionActual = entrante.animate(
        [
          { opacity: 0, transform: 'translateY(8px)' },
          { opacity: 1, transform: 'none' },
        ],
        {
          duration: DURACION_MODULO,
          easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
        }
      );

      animacionActual.finished
        .catch(() => {})
        .then(() => avisarVisible(entrante));
    };

    pestanas.forEach((pestana) => {
      const activa = pestana.getAttribute('aria-selected') === 'true';
      pestana.setAttribute('aria-selected', activa ? 'true' : 'false');
      if (!pestana.hasAttribute('tabindex')) {
        pestana.setAttribute('tabindex', activa ? '0' : '-1');
      }

      pestana.addEventListener('click', (evento) => {
        evento.preventDefault();
        mostrar(pestana);
        // El foco se queda en la pestaña que se acaba de pulsar: es donde está
        // la persona. Moverlo al panel la obligaría a recorrerlo entero con Tab
        // para volver.
        pestana.focus();
      });
    });

    // Flechas izquierda y derecha: es lo que espera quien ya conoce el patrón de
    // pestañas, y con teclado es la única forma de recorrerlas sin tabular por
    // todas una a una.
    grupo.addEventListener('keydown', (evento) => {
      const desplazamiento =
        evento.key === 'ArrowRight' ? 1 : evento.key === 'ArrowLeft' ? -1 : 0;

      if (desplazamiento === 0) return;

      const actual = pestanas.findIndex(
        (p) => p.getAttribute('aria-selected') === 'true'
      );
      if (actual < 0) return;

      evento.preventDefault();
      const siguiente =
        pestanas[(actual + desplazamiento + pestanas.length) % pestanas.length];
      mostrar(siguiente);
      siguiente.focus();
    });

    // Los paneles que no sean el inicial se ocultan sin animar, con estado
    // inicial. Un panel oculto no necesita transición, y animarlo al arrancar
    // produciría un parpadeo en cada carga de página.
    const inicial =
      pestanas.find((p) => p.getAttribute('aria-selected') === 'true') ??
      pestanas[0];
    paneles.forEach((panel, pestana) => {
      panel.hidden = pestana !== inicial;
    });
  });
}

/* ── Envíos que tardan ──────────────────────────────────────────────────────
 *
 * Un envío que toca la base de datos y recalcula una evaluación puede tardar
 * dos o tres segundos. Sin aviso, quien está esperando vuelve a pulsar y
 * duplica la fila.
 *
 * Solo se aplica a los formularios marcados con `data-envio-unico`. No se
 * detecta por heurística: adivinar qué envíos son lentos desde el HTML es
 * fragile, y un formulario que se bloquea por error —uno que falla la
 * validación del servidor y hay que reenviar— deja la pantalla muerta.
 */
function conectarEnviosUnicos() {
  document.addEventListener('submit', (evento) => {
    const formulario = evento.target;
    if (!(formulario instanceof HTMLFormElement)) return;
    if (!formulario.matches('[data-envio-unico]')) return;
    if (formulario.dataset.envioEnCurso === '1') return;

    formulario.dataset.envioEnCurso = '1';

    const boton = formulario.querySelector(
      'button[type="submit"], .btn--principal'
    );
    if (!boton) return;

    boton.dataset.etiquetaOriginal = boton.textContent.trim();
    boton.setAttribute('aria-busy', 'true');
    boton.disabled = true;
    boton.textContent = 'Guardando…';
  });
}

/* ── Barra de intensidad ─────────────────────────────────────────────────────
 *
 * La barra del registro emocional. Antes vivía en dos atributos `oninput` y
 * `onchange` escritos a mano dentro de la plantilla: uno pintaba el número y otro
 * cambiaba los rótulos según la emoción. Los dos desaparecen al llegar aquí.
 *
 * LA ESCALERA son diez peldaños sobre el rango que declare el propio input, no
 * sobre números fijos: se reparte entre `min` y `max`, así que si algún día la
 * barra pasa a 1-10 en lugar de 1-100 los diez peldaños se estiran solos y no hay
 * que tocar ni esta tabla ni el cálculo. Con el `min="1"` que lleva ahora, el
 * primer peldaño es 1-10 y el `Math.max` de abajo cubre el 0 por si alguien
 * pusiera `min="0"` más adelante.
 *
 * El ancho de la escalera son diez palabras y NO diez colores: en diez puntos
 * seguidos no se distinguen dos rosados, y un color que aparece en un solo
 * control deja de ser de la marca. De los tres tonos, solo el alto late.
 */
const NIVELES_INTENSIDAD = [
  'Mínima',
  'Muy baja',
  'Baja',
  'Moderada baja',
  'Media',
  'Media alta',
  'Moderada alta',
  'Alta',
  'Muy alta',
  'Máxima',
];

const TONOS_INTENSIDAD = [
  'bajo',
  'bajo',
  'bajo',
  'bajo',
  'bajo',
  'medio',
  'medio',
  'medio',
  'alto',
  'alto',
];

/* Lo que se lee mientras no haya ninguna emoción elegida.
 *
 * No es un texto de reserva ante un error: es el estado NORMAL de la pantalla al
 * abrirla, porque el campo de emoción es obligatorio y no lleva valor por defecto.
 * La barra está ahí y se puede mover, pero qué mide todavía no está dicho, y estos
 * tres rótulos no affirmed nada que no se haya elegido.
 *
 * Los tres aparecen también escritos en la plantilla, y tienen que ser los mismos
 * caracteres: si el marcado dijera una cosa y esto otra, la página cambiaría de
 * texto sola al cargar el guion. `IntensidadBarraTest` lo comprueba sobre el HTML
 * real, no comparando estas dos listas consigo mismas. */
const LECTURA_SIN_EMOCION = {
  titulo: 'Con qué intensidad',
  min: 'Intensidad mínima',
  max: 'Intensidad máxima',
};

/* El significado de la escala depende de la emoción elegida, y ese texto vive en
 * la vista: es contenido, no comportamiento. Va en un bloque JSON que la
 * plantilla coloca junto a la barra, y se lee una sola vez al conectar. */
function leerLecturas(raiz) {
  const bloque = raiz.querySelector('script[data-lecturas-escala]');

  if (!bloque) return null;

  try {
    return JSON.parse(bloque.textContent);
  } catch (error) {
    // Un JSON mal escrito no puede dejar la barra sin cifras: se avisa en
    // consola y el resto de la conexión sigue adelante.
    console.error('No se han podido leer las lecturas de la escala de intensidad.', error);
    return null;
  }
}

function conectarIntensidad() {
  document.querySelectorAll('[data-intensidad]').forEach((raiz) => {
    const barra = raiz.querySelector('input[type="range"]');

    if (!barra) return;

    const cifras = raiz.querySelector('[data-intensidad-cifra]');
    const nivel = raiz.querySelector('[data-intensidad-nivel]');
    const titulo = raiz.querySelector('[data-intensidad-titulo]');
    const minimo = raiz.querySelector('[data-intensidad-minimo]');
    const maximo = raiz.querySelector('[data-intensidad-maximo]');
    const lecturas = leerLecturas(raiz);

    const aplicarLectura = (nombre) => {
      // Una familia que no esté escrita en el bloque JSON cae también aquí: es
      // preferible un rótulo que no afirma nada a uno que afirma la emoción
      // equivocada.
      const lectura = (lecturas && lecturas[nombre]) || LECTURA_SIN_EMOCION;

      if (titulo) titulo.textContent = lectura.titulo;
      if (minimo) minimo.textContent = lectura.min;
      if (maximo) maximo.textContent = lectura.max;
    };

    const actualizar = () => {
      const valor = Number(barra.value);
      const min = Number(barra.min || 0);
      const max = Number(barra.max || 100);
      const peldaños = NIVELES_INTENSIDAD.length;
      const ancho = (max - min + 1) / peldaños;

      const indice = Math.max(
        0,
        Math.min(peldaños - 1, Math.floor((valor - min) / ancho))
      );

      const palabra = NIVELES_INTENSIDAD[indice];
      const porcentaje = Math.round(((valor - min) / (max - min)) * 100);

      if (cifras) cifras.textContent = `${valor}%`;
      if (nivel) {
        nivel.textContent = palabra;
        // El tono se marca con un atributo y no con una clase: es lo que ya hace
        // `.dato-estado` en el juego, y es la forma correcta de que un cambio de
        // aspecto sea un cambio de estado y no un reemplazo de nodo.
        nivel.dataset.nivel = TONOS_INTENSIDAD[indice];
      }

      // El degradado de la pista se corta aquí, en el mismo momento que cambia
      // la palabra. Si se escribiera en otro sitio, el número y la barra podrían
      // contradecirse un instante.
      barra.style.setProperty('--avance', `${porcentaje}%`);

      // `aria-valuetext`, no una región `aria-live`. Una región viva se anuncia
      // esté o no esté enfocada, y esta cambia en cada `input`: arrastrar la
      // barra encolaría decenas de mensajes. `aria-valuetext` es el atributo que
      // el propio range usa para decir en palabras lo que vale, y el lector lo
      // pronuncia al cambiar el valor, que es lo que se quiere oír.
      barra.setAttribute('aria-valuetext', `${palabra}, ${valor} por ciento`);
    };

    barra.addEventListener('input', actualizar);

    // Las opciones de emoción NO están dentro de `[data-intensidad]`: viven en el
    // `<fieldset>` de arriba, y el widget es una de las tres columnas de la fila
    // de al lado. Buscarlas con `raiz.querySelectorAll` devolvía una lista vacía,
    // que no da error: el bucle no iteraba, el oyente `change` no se instalaba en
    // ninguna de las nueve y cambiar de emoción no repintaba un solo rótulo. La
    // página parecía funcionar porque todo lo demás del widget sí estaba conectado.
    //
    // Lo que comparte las dos mitades es el formulario, así que es el formulario
    // el ámbito. Con un solo widget por formulario da igual; si algún día hubiera
    // dos, este código los cruzaría y habría que pasar a emparejarlos por otro
    // identificador.
    const ambito = raiz.closest('form') || raiz;

    ambito.querySelectorAll('[data-escala]').forEach((opcion) => {
      opcion.addEventListener('change', () => {
        // Solo cambian los rótulos. La medida no se repinta, y no por descuido:
        // el valor de la barra no ha cambiado, así que `actualizar()` volvería a
        // escribir las mismas cuatro cosas que ya están escritas. Se comprobó
        // quitando esta llamada, y la barra quedó idéntica.
        //
        // La palabra del nivel tampoco depende de la emoción: los diez peldaños
        // son los mismos para las cuatro familias. Lo que cambia con la emoción es
        // QUÉ mide la escala —sobrecarga, desgano, energía, calma—, y eso vive en
        // el título y en los dos extremos, que es lo que se repinta aquí.
        aplicarLectura(opcion.dataset.escala);
      });
    });

    // El estado inicial se aplica al conectar, no se espera al primer movimiento.
    //
    // Al cargar NO hay ninguna opción marcada: el campo es `required` a propósito
    // y sin valor por defecto, porque prefijar «felicidad» haría que quien solo
    // pulse enviar quedara registrado como feliz. El navegador no marca la primera
    // hasta que valida el envío, así que en esta pantalla inicial no hay ninguna.
    //
    // Por eso `aplicarLectura` recibe `null` y escribe la lectura genérica. Antes
    // el marcado traía los rótulos de la primera emoción y esta llamada los
    // pisaba al conectar: durante un instante la página decía una cosa y luego
    // otra. Los dos textos tienen que ser el mismo, y el que gana es el genérico.
    const marcada = ambito.querySelector('[data-escala]:checked');
    aplicarLectura(marcada ? marcada.dataset.escala : null);
    actualizar();
  });
}

function iniciar() {
  conectarModulos();
  conectarEnviosUnicos();
  conectarIntensidad();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', iniciar, { once: true });
} else {
  iniciar();
}