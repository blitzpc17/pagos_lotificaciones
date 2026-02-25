<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoletaPartida extends Model
{
    protected $table = 'boletas_partidas';
    public $timestamps = true;

    protected $fillable = [
        'boleta_id','folio_partida','fecha_pago','monto',
        'recargo','monto_recargo','tipo_pago','observacion',
        'usuario_registro_id','usuario_modifico_id','usuario_baja_id',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'fecha_pago'=>'date',
        'recargo'=>'boolean',
        'baja'=>'boolean',
        'baja_at'=>'datetime',
    ];
}
