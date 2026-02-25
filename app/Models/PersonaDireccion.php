<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaDireccion extends Model
{
    protected $table = 'persona_direcciones';

    protected $fillable = [
        'persona_id','etiqueta','calle','numero_ext','numero_int','colonia','municipio','estado','cp','referencias','es_principal',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function persona(){ return $this->belongsTo(Persona::class, 'persona_id'); }
}
