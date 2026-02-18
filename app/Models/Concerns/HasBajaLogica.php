<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasBajaLogica
{
    public static function bootHasBajaLogica()
    {
        static::addGlobalScope('no_baja', function (Builder $builder) {
            $builder->where('baja', false);
        });
    }

    public function scopeWithBaja($query)
    {
        return $query->withoutGlobalScope('no_baja');
    }

    public function darDeBaja(int $userId, ?string $motivo = null): bool
    {
        $this->baja = true;
        $this->baja_at = now();
        $this->baja_by = $userId;
        $this->baja_motivo = $motivo;
        return $this->save();
    }
}
