<?php

namespace App\Models;

use App\Enums\AlcanceConsentimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Autorización del estudiante para que un profesional concreto vea parte de sus
 * datos.
 *
 * Una fila con `revocado_en` nulo ES el consentimiento vigente. No hay
 * caché, ni copia, ni vista materializada: cada consulta del espacio del
 * psicólogo pasa por aquí, así que revocar es inmediato por construcción.
 */
class Consentimiento extends Model
{
    protected $table = 'consentimientos';

    protected $fillable = [
        'alcance',
        'motivo_revocacion',
    ];

    // `otorgado_en` / `revocado_en` / los FK se asignan como propiedad
    // explícita, nunca en masa: son la evidencia de un acto de voluntad.
    protected function casts(): array
    {
        return [
            'alcance' => 'array',
            'otorgado_en' => 'datetime',
            'revocado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function estaVigente(): bool
    {
        return $this->revocado_en === null;
    }

    /**
     * Ámbito base de todo lo que ve el psicólogo. Si un forget se olvida de
     * aplicarlo, el estudiante ve datos ajenos: por eso se expone el método y
     * se insiste en su uso.
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNull('revocado_en');
    }

    public function scopeDeProfesional(Builder $query, int $profesionalId): Builder
    {
        return $query->where('profesional_id', $profesionalId);
    }

    /** @return array<int, AlcanceConsentimiento> */
    public function alcances(): array
    {
        $casos = array_map(
            fn ($v) => is_string($v) ? AlcanceConsentimiento::tryFrom($v) : null,
            (array) $this->alcance
        );

        return array_values(array_filter($casos));
    }

    public function incluye(AlcanceConsentimiento $caso): bool
    {
        return in_array($caso->value, (array) $this->alcance, true);
    }

    public function revocar(?string $motivo = null): void
    {
        $this->revocado_en = now();
        $this->motivo_revocacion = $motivo;
        $this->save();
    }

    /**
     * Consentimiento vigente entre estos dos usuarios, si existe.
     * Si el estudiante vuelve a conectar, se actualiza el alcance en lugar de
     * acumular filas: "lo que comparto ahora" es una sola respuesta.
     */
    public static function vigenteEntre(int $estudianteId, int $profesionalId): ?self
    {
        return self::query()
            ->vigentes()
            ->deProfesional($profesionalId)
            ->where('estudiante_id', $estudianteId)
            ->latest('otorgado_en')
            ->first();
    }
}
