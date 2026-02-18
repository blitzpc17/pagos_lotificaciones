<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasBajaLogica;

class Vendedor extends Model
{
    use HasBajaLogica;

    protected $table = 'vendedores';

    protected $fillable = [
        'persona_id','comision_default','clave',
        'baja','baja_at','baja_by','baja_motivo'
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
