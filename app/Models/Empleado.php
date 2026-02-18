<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasBajaLogica;

class Empleado extends Model
{
    use HasBajaLogica;

    protected $table = 'empleados';

    protected $fillable = [
        'persona_id','puesto','puesto_detalle','numero_empleado','observaciones',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
