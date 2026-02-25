<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoletaPago extends Model
{
    protected $table = 'boletas_pago';
    public $timestamps = true;

    protected $fillable = [
        'folio','cliente_id','vendedor_id','lotificacion_id','socio_id','lote_id',
        'oficina','fecha_contrato','tipo_venta',
        'costo_contado','costo_credito','enganche','comision_vendedor','meses',
        'observaciones','created_by','updated_by',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'fecha_contrato'=>'date',
        'baja'=>'boolean',
        'baja_at'=>'datetime',
    ];
}
