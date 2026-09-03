<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionPsicometrica extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_psicometricas';

    protected $fillable = [
        'user_id',
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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
