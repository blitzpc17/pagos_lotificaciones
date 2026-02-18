<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';

    protected $fillable = [
        'nombres','apellido_paterno','apellido_materno',
        'fecha_nacimiento','notas',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
