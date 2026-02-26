<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'persona_id',
        'role_id',
        'email',
        'username',
        'password_hash',
        'is_active',
        'baja',
        'baja_at',
        'baja_by',
        'baja_motivo',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'baja' => 'boolean',
        'baja_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function role()
    {
        return $this->belongsTo(Rol::class, 'role_id');
    }
}