<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'modulos';
    public $timestamps = true;

    protected $fillable = [
        'nombre','ruta','icono','parent_id','es_menu','orden','is_active',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'es_menu'=>'boolean','is_active'=>'boolean','baja'=>'boolean','baja_at'=>'datetime'
    ];

    public function parent()
    {
        return $this->belongsTo(Modulo::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Modulo::class, 'parent_id')->orderBy('orden');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'roles_modulos', 'modulo_id', 'role_id')
            ->withTimestamps();
    }
}
