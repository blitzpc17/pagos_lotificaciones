<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    public $timestamps = true;

    protected $fillable = ['nombre','descripcion','is_active','baja','baja_at','baja_by','baja_motivo'];
    protected $casts = ['is_active'=>'boolean','baja'=>'boolean','baja_at'=>'datetime'];

    public function modulos()
    {
        return $this->belongsToMany(Modulo::class, 'roles_modulos', 'role_id', 'modulo_id')
            ->withTimestamps();
    }
}
