<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    protected $table = 'vendedores';

    protected $fillable = [
        'persona_id',
        'comision_default',
        'clave',
        'baja',
        'baja_at',
        'baja_by',
        'baja_motivo',
    ];

    protected $casts = [
        'comision_default' => 'decimal:2',
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}