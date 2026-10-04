<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'comentario',
        'estado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePendiente($query)
    {
        return $query->whereRaw('LOWER(estado) = ?', ['pendiente']);
    }

    public function scopeResuelto($query)
    {
        return $query->whereRaw('LOWER(estado) = ?', ['resuelto']);
    }

    public function getEstaResueltoAttribute(): bool
    {
        return strtolower((string) $this->estado) === 'resuelto';
    }
}
