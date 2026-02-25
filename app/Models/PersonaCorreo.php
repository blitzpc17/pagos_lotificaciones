<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaCorreo extends Model
{
    protected $table = 'persona_correos';

    protected $fillable = [
        'persona_id','etiqueta','correo','es_principal',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function persona(){ return $this->belongsTo(Persona::class, 'persona_id'); }
}
