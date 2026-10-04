<?php

namespace App\Enums;

/**
 * Qué elige compartir el estudiante con su profesional de psicología.
 *
 * Se guarda como array en la columna JSON `alcance` de la tabla
 * `consentimientos`. El estudiante marca una o varias; el psicólogo solo ve
 * lo marcado, nunca el conjunto completo por defecto.
 */
enum AlcanceConsentimiento: string
{
    case Registros = 'registros';
    case Evaluaciones = 'evaluaciones';
    case Juego = 'juego';

    /** Texto llano para la interfaz: el estudiante no debe ver claves internas. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Registros => 'Mis registros diarios de cómo me he sentido',
            self::Evaluaciones => 'Mis resultados de las evaluaciones psicológicas',
            self::Juego => 'Mis partidas y el tiempo de juego',
        };
    }

    /** Frase corta para listados y para el resumen de "qué comparto". */
    public function etiquetaCorta(): string
    {
        return match ($this) {
            self::Registros => 'Registros diarios',
            self::Evaluaciones => 'Evaluaciones',
            self::Juego => 'Partidas del juego',
        };
    }

    /** Explicación honesta de qué puede ver el profesional con esto. */
    public function descripcion(): string
    {
        return match ($this) {
            self::Registros => 'La emoción que registraste, tu nivel de energía y de estrés, y la nota que escribiste ese día.',
            self::Evaluaciones => 'Las respuestas de los cuestionarios SISCO y tus puntajes. No incluye tus notas personales.',
            self::Juego => 'Cuántas partidas jugaste, cuánto tardaste y tu ritmo de respuesta. No incluye lo que escribiste.',
        };
    }

    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /**
     * Normaliza lo que llega del formulario a una lista de casos válida.
     * Descarta silenciosamente cualquier valor desconocido: un `alcance` con
     * basura haría que el profesional viera datos que el estudiante no autorizó.
     *
     * @return array<int, self>
     */
    public static function desdeEntrada(mixed $entrada): array
    {
        if (! is_array($entrada)) {
            return [];
        }

        $validos = array_map(
            fn ($v) => is_string($v) ? self::tryFrom($v) : null,
            $entrada
        );

        return array_values(array_filter($validos));
    }
}
