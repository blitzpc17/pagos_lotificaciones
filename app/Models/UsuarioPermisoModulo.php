<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Overrides de permisos por usuario / módulo.
 *
 * Nota:
 * - Esta tabla NO es auditoría.
 */
class UsuarioPermisoModulo extends Model
{
    protected $table = 'usuarios_permisos_modulo';

    protected $fillable = [
        'usuario_id',
        'modulo_id',
        'puede_ver',
        'puede_crear',
        'puede_modificar',
        'puede_baja',
    ];

    protected $casts = [
        'puede_ver' => 'boolean',
        'puede_crear' => 'boolean',
        'puede_modificar' => 'boolean',
        'puede_baja' => 'boolean',
    ];
}