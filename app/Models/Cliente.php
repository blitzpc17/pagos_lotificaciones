<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'persona_id','rfc','tipo_cliente','notas',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function persona(){ return $this->belongsTo(Persona::class, 'persona_id'); }
}
