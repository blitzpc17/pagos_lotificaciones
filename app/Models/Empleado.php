<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'persona_id','puesto','puesto_detalle','numero_empleado','observaciones',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function persona() { return $this->belongsTo(Persona::class, 'persona_id'); }

    // En el schema, vendedores y usuarios referencian persona_id
    public function vendedor(){ return $this->hasOne(Vendedor::class, 'persona_id', 'persona_id'); }
    public function usuario() { return $this->hasOne(Usuario::class, 'persona_id', 'persona_id'); }
}