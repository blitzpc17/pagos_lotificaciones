<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioAccionModulo extends Model {
  protected $table='usuarios_acciones_modulo';
  protected $fillable=['usuario_id','modulo_id','puede_ver','puede_crear','puede_modificar','puede_baja'];
}
