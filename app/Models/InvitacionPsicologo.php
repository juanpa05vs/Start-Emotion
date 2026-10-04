<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Código temporal que entrega el psicólogo al estudiante para que lo canjee
 * por un consentimiento. Se genera desde el espacio del profesional.
 *
 * El código es la única forma de iniciar la conexión: no existe un listado de
 * estudiantes que el psicólogo pueda consultar para "invitar a los que quiera".
 */
class InvitacionPsicologo extends Model
{
    protected $table = 'invitaciones_psicologo';

    protected $fillable = [
        'codigo',
        'expira_en',
    ];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'usada_en' => 'datetime',
        ];
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function usadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usada_por');
    }

    public function estaVigente(): bool
    {
        return $this->usada_en === null && $this->expira_en->isFuture();
    }

    /**
     * Busca un código que el estudiante pueda canjear en este momento.
     *
     * Filtra por vigente en SQL en vez de traer todas y descartar en PHP: un
     * código vencido nunca debe llegar al flujo de otorgamiento aunque el
     * reloj y la base de datos discrepen.
     */
    public static function buscarVigente(string $codigo): ?self
    {
        return self::query()
            ->where('codigo', $codigo)
            ->whereNull('usada_en')
            ->where('expira_en', '>', now())
            ->first();
    }
}
