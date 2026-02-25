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
    public function vendedor(){ return $this->hasOne(Vendedor::class, 'empleado_id'); }
    public function usuario() { return $this->hasOne(Usuario::class, 'empleado_id'); }
}
