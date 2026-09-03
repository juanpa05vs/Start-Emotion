<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles; // Motor de seguridad Alpha

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * [INGENIERÍA]: Mapeo a la tabla personalizada 'usuarios'.
     */
    protected $table = 'usuarios';

    /**
     * Campos que se pueden llenar de forma masiva.
     */
    protected $fillable = [
        'nombre',
        'edad',
        'correo',
        'password',
        'avatar',
        'tema',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * [LARAVEL 11+]: Configuración de cifrado de contraseñas.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES DE TELEMETRÍA, INVESTIGACIÓN Y BIENESTAR
    |--------------------------------------------------------------------------
    */

    /**
     * Registro básico de telemetría emocional (Módulo Dashboard).
     */
    public function emociones()
    {
        return $this->hasMany(RegistroEmocion::class, 'user_id');
    }

    /**
     * Evaluaciones psicométricas estandarizadas (Gold Standard / Test SISCO).
     */
    public function evaluacionesPsicometricas()
    {
        return $this->hasMany(EvaluacionPsicometrica::class, 'user_id');
    }

    /**
     * Telemetría conductual capturada durante las sesiones de juego (Latencia y Tapping).
     */
    public function telemetriasGameplay()
    {
        return $this->hasMany(TelemetriaGameplay::class, 'user_id');
    }

    /**
     * Feedback y reportes del sistema.
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS DE INGENIERÍA, SEGURIDAD Y BIOÉTICA
    |--------------------------------------------------------------------------
    */

    /**
     * Genera un identificador anonimizado para exportación científica y cumplimiento bioético.
     * Ejemplo de salida: TESVB-SIST-0042
     */
    public function getCodigoAnonimoAttribute(): string
    {
        return 'TESVB-SIST-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * HELPER DE NIVEL ALPHA (esAdmin)
     * Verifica el acceso administrativo de forma robusta.
     */
    public function esAdmin(): bool
    {
        // 1. Verifica por la columna 'rol' (para compatibilidad visual en BD)
        if ($this->rol && trim(strtolower($this->rol)) === 'administrador') {
            return true;
        }

        // 2. Verifica mediante el sistema de Spatie (Roles de seguridad)
        return $this->hasRole('Administrador');
    }
}
