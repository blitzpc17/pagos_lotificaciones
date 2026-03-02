<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoProveedorPartida extends Model
{
    protected $table = 'pago_proveedor_partidas';

    protected $fillable = [
        'pago_proveedor_id','folio_partida','fecha_pago','forma_pago','tipo_partida',
        'monto','referencia_pago','observacion',
        'usuario_registro_id','usuario_modifico_id','usuario_baja_id',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'float',
        'baja' => 'boolean',
    ];

    public function pago()
    {
        return $this->belongsTo(PagoProveedor::class, 'pago_proveedor_id');
    }
}