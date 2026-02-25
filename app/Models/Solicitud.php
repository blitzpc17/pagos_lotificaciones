<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';
    public $timestamps = true;

    protected $fillable = [
        'tipo','estatus','modulo_id',
        'tabla_objetivo','registro_id',
        'motivo','payload',
        'solicitado_por','solicitado_at',
        'revisado_por','revisado_at','decision_motivo',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    protected $casts = [
        'payload' => 'array',
        'solicitado_at' => 'datetime',
        'revisado_at' => 'datetime',
        'baja' => 'boolean',
        'baja_at' => 'datetime',
    ];

    public function solicitadoPor(){ return $this->belongsTo(Usuario::class, 'solicitado_por'); }
    public function revisadoPor(){ return $this->belongsTo(Usuario::class, 'revisado_por'); }
    public function modulo(){ return $this->belongsTo(Modulo::class, 'modulo_id'); }
}
