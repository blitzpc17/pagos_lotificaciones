<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';

    protected $fillable = [
        'nombres','apellido_paterno','apellido_materno','fecha_nacimiento','notas',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function telefonos() { return $this->hasMany(PersonaTelefono::class, 'persona_id'); }
    public function correos()   { return $this->hasMany(PersonaCorreo::class, 'persona_id'); }
    public function direcciones(){ return $this->hasMany(PersonaDireccion::class, 'persona_id'); }

    public function empleado()  { return $this->hasOne(Empleado::class, 'persona_id'); }
    public function cliente()   { return $this->hasOne(Cliente::class, 'persona_id'); }
}
