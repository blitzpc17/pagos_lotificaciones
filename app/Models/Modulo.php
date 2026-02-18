<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model {
  protected $table='modulos';
  public function parent(){ return $this->belongsTo(Modulo::class,'parent_id'); }
  public function children(){ return $this->hasMany(Modulo::class,'parent_id')->orderBy('orden'); }
}
