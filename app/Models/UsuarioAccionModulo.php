<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioAccionModulo extends Model
{
    protected $table = 'usuarios_acciones_modulo';
    public $timestamps = true;

    protected $fillable = [
        'usuario_id','modulo_id',
        'puede_ver','puede_crear','puede_modificar','puede_baja'
    ];

    protected $casts = [
        'puede_ver'=>'boolean',
        'puede_crear'=>'boolean',
        'puede_modificar'=>'boolean',
        'puede_baja'=>'boolean',
    ];

    public function modulo(){ return $this->belongsTo(Modulo::class, 'modulo_id'); }
    public function usuario(){ return $this->belongsTo(Usuario::class, 'usuario_id'); }
}
