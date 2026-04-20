<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    /**
     * [REPARACIÓN]: Cambiamos 'mensaje' por 'comentario'
     * para que coincida con tu base de datos.
     */
    protected $fillable = [
        'user_id',
        'comentario', // 👈 ¡Este es el nombre real en tu DB!
        'estado'    // Nuevo campo para seguimiento del feedback
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
