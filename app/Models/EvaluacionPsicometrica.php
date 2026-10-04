<?php

namespace App\Models;

use App\Enums\EmocionEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionPsicometrica extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_psicometricas';

    protected $fillable = [
        'instrumento',
        'puntaje_estresores',
        'puntaje_sintomas',
        'puntaje_afrontamiento',
        'puntaje_global',
        'nivel_estres',
        'estado_afectivo_predominante',
        'respuestas_detalle',
    ];

    protected $casts = [
        'respuestas_detalle' => 'array',
        'puntaje_estresores' => 'integer',
        'puntaje_sintomas' => 'integer',
        'puntaje_afrontamiento' => 'integer',
        'puntaje_global' => 'integer',
    ];

    /**
     * El nivel de estrés en palabras, y no como lo guarda la base de datos.
     *
     * La columna es un `enum` de MySQL con valores `bajo`, `moderado` y
     * `severo`. Esos tres valores se mostraban tal cual en tres pantallas
     * distintas —la encuesta, la ficha interna del profesional y la validación
     * científica— y cada una los escribía a su manera, con `ucfirst`, con
     * concatenación o sin tocar. Aquí se traducen una vez.
     *
     * Es presentación, no lógica: el baremo sigue siendo el del controlador.
     */
    public function nivelEstresEtiqueta(): string
    {
        return match ($this->nivel_estres) {
            'severo' => 'Severo',
            'moderado' => 'Moderado',
            default => 'Bajo',
        };
    }

    /**
     * La emoción que más pesó, en palabras y no como la guarda la base de datos.
     *
     * La columna es un `enum` con las cinco clases que usa el modelo: `ansiedad`,
     * `frustracion`… esas palabras son claves, no algo que se pueda leer delante
     * de alguien. Se traduce con `EmocionEnum::etiqueta()`, el único sitio donde
     * se decide cómo se escribe una emoción.
     *
     * Devuelve `null` cuando no hay nada escrito, para que cada pantalla decida
     * qué hacer con el hueco en vez de inventar un guion.
     */
    public function estadoAfectivoEtiqueta(): ?string
    {
        $valor = $this->estado_afectivo_predominante;

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        return EmocionEnum::etiqueta($valor);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
