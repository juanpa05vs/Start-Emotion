<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelemetriaGameplay extends Model
{
    use HasFactory;

    protected $table = 'telemetria_gameplay';

    protected $fillable = [
        'user_id',
        'minijuego_id',
        'latencia_promedio_ms',
        'frecuencia_tapping',
        'tiempo_total_ms',
        'conteo_rectificaciones',
        'errores_diagnostico',
        'score_final',
        'emocion_objetivo',
        'emocion_predicha',
        'diagnostico_correcto',
        'vector_caracteristicas',
    ];

    protected $casts = [
        'vector_caracteristicas' => 'array',
        'diagnostico_correcto'   => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
