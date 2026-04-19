<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles; // El motor de seguridad que instalamos

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * [INGENIERÍA]: Mapeo a la tabla personalizada 'usuarios'.
     */
    protected $table = 'usuarios';

    /**
     * Campos que se pueden llenar de forma masiva.
     * [BIO-SYNC]: Incluimos 'rol' de nuevo por si aún lo usas en tu DB
     * como respaldo visual o en el registro inicial.
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

    /**
     * RELACIÓN: Fundamental para el Calendario y el Análisis Neural.
     */
    public function emociones()
    {
        return $this->hasMany(RegistroEmocion::class, 'usuario_id');
    }

    /**
     * HELPER DE NIVEL ALPHA (esAdmin)
     * [SEGURIDAD]: Verifica el acceso administrativo de forma robusta.
     * [PSICOLOGÍA]: Reduce la carga cognitiva al leer el código.
     */
    public function esAdmin()
    {
        // 1. Intenta verificar por la columna 'rol' (si existe en tu tabla usuarios)
        if ($this->rol && trim(strtolower($this->rol)) === 'administrador') {
            return true;
        }

        // 2. Si no, verifica mediante el sistema de Spatie (Tablas roles/permissions)
        return $this->hasRole('Administrador');
    }
}
