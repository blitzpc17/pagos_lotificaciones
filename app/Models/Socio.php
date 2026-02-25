<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Socio extends Model
{
    protected $table = 'socios';
    public $timestamps = true;

    protected $fillable = [
        'nombre','color','telefono','email',
        'baja','baja_at','baja_by','baja_motivo',
    ];

    protected $casts = [
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function bajaBy()
    {
        return $this->belongsTo(Usuario::class, 'baja_by');
    }
}
