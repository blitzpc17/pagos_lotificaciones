<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'lotificacion_id','clave_lote','manzana','numero',
        'estado','costo_contado','costo_credito','notas',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'baja' => 'boolean',
        'costo_contado' => 'float',
        'costo_credito' => 'float',
    ];

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }
}