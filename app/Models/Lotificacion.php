<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lotificacion extends Model
{
    protected $table = 'lotificaciones';

    protected $fillable = [
        'nombre','json_croquis','numero_lotes','oficina','estado','is_active',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'json_croquis' => 'array',
        'baja' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function lotes()
    {
        return $this->hasMany(Lote::class, 'lotificacion_id');
    }
}