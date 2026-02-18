<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccionUsuarioHistorial extends Model
{
    protected $table = 'acciones_usuario_historial';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id','modulo_id','accion','tabla','registro_id',
        'ip','user_agent','before_data','after_data','created_at'
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];
}
