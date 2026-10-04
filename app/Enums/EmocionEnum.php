<?php

namespace App\Enums;

enum EmocionEnum: string
{
    case ANSIEDAD = 'ansiedad';
    case IRA = 'ira';
    case FRUSTRACION = 'frustracion';
    case MELANCOLIA = 'melancolia';
    case EUFORIA = 'euforia';
    case ESTRES = 'estres';
    case APATIA = 'apatia';
    case CULPA = 'culpa';
    case TRISTEZA = 'tristeza';
    case ALEGRIA = 'alegria';
    case NEUTRALIDAD = 'neutralidad';
    case FELICIDAD = 'felicidad';

    /**
     * Cómo se escribe una emoción para quien la lee.
     *
     * Los valores del enum no llevan tilde a propósito: son lo que se guarda en
     * la base de datos y lo que viaja en las rutas y en los CSV. Ese formato no
     * puede enseñarse a una persona. Este método es el único sitio donde se
     * traduce de clave a palabra.
     *
     * @param  string  $valor  el `value` del enum
     */
    public static function etiqueta(string $valor): string
    {
        return match ($valor) {
            self::ANSIEDAD->value => 'Ansiedad',
            self::IRA->value => 'Ira',
            self::FRUSTRACION->value => 'Frustración',
            self::MELANCOLIA->value => 'Melancolía',
            self::EUFORIA->value => 'Euforia',
            self::ESTRES->value => 'Estrés',
            self::APATIA->value => 'Apatía',
            self::CULPA->value => 'Culpa',
            self::TRISTEZA->value => 'Tristeza',
            self::ALEGRIA->value => 'Alegría',
            self::NEUTRALIDAD->value => 'Neutralidad',
            self::FELICIDAD->value => 'Felicidad',
            default => ucfirst($valor),
        };
    }

    /**
     * Conjunto de clases del juego (Código Anómalo).
     */
    public static function juego(): array
    {
        return [
            self::ANSIEDAD->value,
            self::IRA->value,
            self::FRUSTRACION->value,
            self::MELANCOLIA->value,
            self::EUFORIA->value,
            self::ESTRES->value,
            self::APATIA->value,
            self::CULPA->value,
        ];
    }

    /**
     * Conjunto de clases utilizadas por Random Forest (5 clases).
     */
    public static function rf(): array
    {
        return [
            self::ALEGRIA->value,
            self::TRISTEZA->value,
            self::ANSIEDAD->value,
            self::FRUSTRACION->value,
            self::NEUTRALIDAD->value,
        ];
    }

    /**
     * Mapa para normalizar etiquetas entre taxonomías.
     */
    public static function normalizarParaRF(string $valor): ?string
    {
        $v = strtolower(trim($valor));

        return match ($v) {
            'euforia', 'felicidad', 'entusiasta', 'productivo', 'alegria' => self::ALEGRIA->value,
            'apatia', 'agotado', 'melancolia', 'tristeza' => self::TRISTEZA->value,
            'ansiedad', 'ansioso', 'estres' => self::ANSIEDAD->value,
            'ira', 'frustracion', 'frustrado' => self::FRUSTRACION->value,
            'culpa', 'relajado', 'neutralidad' => self::NEUTRALIDAD->value,
            default => $v,
        };
    }

    /**
     * Clases para la matriz de confusión del panel de Validación Científica.
     *
     * Son las MISMAS que Random Forest puede emitir, y esa es la razón de que no
     * se liste aquí el vocabulario del juego (8 clases). Una matriz de confusión
     * exige que ambos ejes vivan en el mismo espacio de etiquetas: las filas son
     * la clase real y las columnas la predicción. Si el eje de columnas incluyera
     * clases que el modelo nunca puede predecir, esas columnas quedarían siempre en
     * cero y las muestras predichas en ellas se descartarían silenciosamente.
     *
     * La clase real llega desde el juego (8 valores) y se colapsa a este espacio
     * con normalizarParaRF(), que cubre las 8. Ver tests/Feature/ValidacionCientificaTest.
     */
    public static function validacion(): array
    {
        return self::rf();
    }

    /**
     * Cómo se colapsa cada emoción del juego a una clase de la matriz.
     * Se expone para poder mostrarlo en la interfaz y en el CSV: unacollapse
     * silencioso haría que el panel pareciera menos preciso de lo que es.
     */
    public static function mapaColapso(): array
    {
        $mapa = [];

        foreach (self::juego() as $emocionJuego) {
            $mapa[$emocionJuego] = self::normalizarParaRF($emocionJuego);
        }

        return $mapa;
    }
}
