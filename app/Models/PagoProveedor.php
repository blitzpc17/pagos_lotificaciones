<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoProveedor extends Model
{
    protected $table = 'pago_proveedor';

    protected $fillable = [
        'folio','proveedor_id','fecha_documento','fecha_registro',
        'concepto','referencia','monto_total','observaciones',
        'created_by','updated_by',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'fecha_registro' => 'datetime',
        'monto_total' => 'float',
        'baja' => 'boolean',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function partidas()
    {
        return $this->hasMany(PagoProveedorPartida::class, 'pago_proveedor_id');
    }
}