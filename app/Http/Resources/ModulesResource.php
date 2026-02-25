<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModulosResource extends JsonResource
{
    public function toArray($request)
    {
        $isBaja = (bool)$this->baja;

        $estatus = $isBaja
          ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> Baja</span>'
          : '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Activo</span>';

        $acc = '<div class="dt-actions">';
        $acc .= '<button class="mini primary btnModuloEdit" data-id="'.$this->id.'"><i class="fa-regular fa-pen-to-square"></i> Editar</button>';
        if(!$isBaja){
          $acc .= '<button class="mini danger btnModuloBaja" data-id="'.$this->id.'"><i class="fa-regular fa-trash-can"></i> Baja</button>';
        }
        $acc .= '</div>';

        return [
            'id'=>$this->id,
            'nombre'=>$this->nombre,
            'ruta'=>$this->ruta,
            'icono'=>$this->icono,
            'parent_id'=>$this->parent_id,
            'parent_nombre'=>$this->parent?->nombre,
            'es_menu'=>$this->es_menu ? 'Sí':'No',
            'orden'=>$this->orden,
            'estatus_html'=>$estatus,
            'acciones_html'=>$acc,
        ];
    }
}
