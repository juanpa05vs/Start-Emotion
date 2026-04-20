<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistroEmocion extends Model
{
    use HasFactory;

    // Indicamos la tabla exacta según tu migración
    protected $table = 'registros_emociones';

    /**
     * Campos permitidos para asignación masiva.
     * Se agregaron nivel_estres_estimado y recomendacion para conectar con la
     * Capa de Lógica de Negocio[cite: 153].
     */
    protected $fillable = [
        'user_id',
        'emocion',
        'energia',
        'nivel_estres_estimado', // Requerido para el Bio-HUD [cite: 216]
        'recomendacion',         // Requerido para el Motor de Recomendaciones [cite: 161]
        'observaciones', // Nuevo
        'contexto',      // Nuevo
        'alerta_burnout' // Nuevo
    ];

    /**
     * Relación inversa: Un registro pertenece a un usuario.
     * Cumple con la Regla de Integridad: "Una partida pertenece a un único usuario"[cite: 238].
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Lógica de Presentación: Cambio de color dinámico.
     * Ayuda a la "identificación de emociones" mediante código visual[cite: 34].
     */
    public function getColor()
    {
        return match($this->emocion) {
            'Entusiasta' => 'text-yellow-400 border-yellow-400 shadow-yellow-400/20',
            'Productivo' => 'text-green-400 border-green-400 shadow-green-400/20',
            'Agotado'    => 'text-red-500 border-red-500 shadow-red-500/20',
            'Ansioso'    => 'text-purple-500 border-purple-500 shadow-purple-500/20',
            'Relajado'   => 'text-neon-cyan border-neon-cyan shadow-neon-cyan/20',
            default      => 'text-gray-400 border-white/10 shadow-transparent',
        };
    }

    /**
     * Módulo de Control de Sesiones: Determina el estado del Bio-HUD.
     * Basado en la métrica de nivel de estrés estimada[cite: 157, 216].
     */
    public function getStressStatus()
    {
        // Se extrae la métrica de la Capa de Datos [cite: 175]
        $estres = $this->nivel_estres_estimado ?? 0;

        if ($estres > 70) {
            return [
                'label' => 'CRÍTICO',
                'color' => 'text-red-500',
                'pulse' => 'animate-ping'
            ];
        }

        if ($estres > 40) {
            return [
                'label' => 'ELEVADO',
                'color' => 'text-yellow-500',
                'pulse' => 'animate-pulse'
            ];
        }

        return [
            'label' => 'ESTABLE',
            'color' => 'text-neon-cyan',
            'pulse' => ''
        ];
    }
}
