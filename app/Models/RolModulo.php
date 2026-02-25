<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolModulo extends Model
{
    protected $table = 'roles_modulos';
    public $timestamps = true;

    protected $fillable = ['role_id','modulo_id'];
}
