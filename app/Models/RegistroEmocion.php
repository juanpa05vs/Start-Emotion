<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroEmocion extends Model
{
    use HasFactory;

    protected $table = 'registros_emociones';

    protected $fillable = [
        'emocion',
        'energia',
        'nivel_estres_estimado',
        'recomendacion',
        'observaciones',
        'contexto',
        'alerta_burnout',
    ];

    protected function casts(): array
    {
        return [
            'alerta_burnout' => 'boolean',
            'nivel_estres_estimado' => 'decimal:2',
            'energia' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cómo se presenta el nivel de estrés del registro.
     *
     * Las clases son literales a propósito. Un nombre de token no se puede
     * componer en una cadena y luego poner en la vista: Tailwind compila
     * clases por texto, y una clase construida en tiempo de ejecución
     * —`text-' . $familia . '-400`— no existe en el CSS que se ha publicado.
     * Ese era el bug de aquí: `text-neon-cyan` era una constante de marca que
     * ya no existe, así que la etiqueta «Estable» salía sin color y heredaba
     * el del subtítulo.
     *
     * `text-rose-400` y `text-amber-400` son los colores semánticos de la
     * aplicación y el modo claro los oscurece solos (`resources/css/app.css`).
     * `text-red-500` y `text-yellow-500` no: son los tonos de Tailwind
     * elegidos para fondo oscuro, y sobre blanco bajan de 4.5:1 — el ámbar
     * a 2.0.
     *
     * @return array{label: string, color: string, pulse: string}
     */
    public function getStressStatus(): array
    {
        $estres = (float) ($this->nivel_estres_estimado ?? 0);

        if ($estres > 70) {
            return [
                'label' => 'Crítico',
                'color' => 'text-rose-400',
                'pulse' => 'animate-ping',
            ];
        }

        if ($estres > 40) {
            return [
                'label' => 'Elevado',
                'color' => 'text-amber-400',
                'pulse' => 'animate-pulse',
            ];
        }

        return [
            'label' => 'Estable',
            'color' => 'text-accent-text',
            'pulse' => '',
        ];
    }
}
