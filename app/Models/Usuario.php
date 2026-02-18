<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'persona_id','role_id','email','username','password_hash',
        'is_active','baja','baja_at','baja_by','baja_motivo'
    ];

    protected $hidden = ['password_hash'];

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'role_id');
    }
}
