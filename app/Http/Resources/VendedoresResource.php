<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendedoresResource extends JsonResource
{
    public function toArray($request)
    {
        $p = $this->persona;
        $nombre = trim(implode(' ', array_filter([$p?->nombres,$p?->apellido_paterno,$p?->apellido_materno])));
        $isBaja = (bool)$this->baja;

        $estatus = $isBaja
          ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> Baja</span>'
          : '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Activo</span>';

        $acc = '<div class="dt-actions">';
        $acc .= '<button class="mini primary btnVendedorEdit" data-id="'.$this->id.'"><i class="fa-regular fa-pen-to-square"></i> Editar</button>';
        if(!$isBaja){
          $acc .= '<button class="mini danger btnVendedorBaja" data-id="'.$this->id.'"><i class="fa-regular fa-trash-can"></i> Baja</button>';
        }
        $acc .= '</div>';

        return [
          'id'=>$this->id,
          'nombre_completo'=>$nombre ?: '(Sin nombre)',
          'clave'=>$this->clave,
          'comision_default'=>$this->comision_default,
          'estatus_html'=>$estatus,
          'acciones_html'=>$acc,
        ];
    }
}
