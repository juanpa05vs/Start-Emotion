<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles; // El motor de seguridad Alpha

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

    /**
     * RELACIÓN: Sincronizada con la llave foránea 'user_id'.
     * [REPARACIÓN]: Cambiamos 'usuario_id' a 'user_id' para coincidir con la migración.
     */
    public function emociones()
    {
        // Apuntamos al modelo RegistroEmocion usando la FK correcta: user_id
        return $this->hasMany(RegistroEmocion::class, 'user_id');
    }

    /**
     * HELPER DE NIVEL ALPHA (esAdmin)
     * [SEGURIDAD]: Verifica el acceso administrativo de forma robusta.
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    public function esAdmin()
    {
        // 1. Verifica por la columna 'rol' (para compatibilidad visual en BD)
        if ($this->rol && trim(strtolower($this->rol)) === 'administrador') {
            return true;
        }

        // 2. Verifica mediante el sistema de Spatie (Roles de seguridad)
        return $this->hasRole('Administrador');
    }
}
