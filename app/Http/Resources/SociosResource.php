<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SociosResource extends JsonResource
{
    public function toArray($request)
    {
        $isBaja = (bool)$this->baja;

        $estatus = $isBaja
          ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> Baja</span>'
          : '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Activo</span>';

        $color = $this->color ?: '#2D6CDF';
        $colorHtml = '<span class="badge"><span style="display:inline-block;width:12px;height:12px;border-radius:4px;background:'.$color.'"></span> '.$color.'</span>';

        $acc = '<div class="dt-actions">';
        $acc .= '<button class="mini primary btnSocioEdit" data-id="'.$this->id.'"><i class="fa-regular fa-pen-to-square"></i> Editar</button>';
        if(!$isBaja){
          $acc .= '<button class="mini danger btnSocioBaja" data-id="'.$this->id.'"><i class="fa-regular fa-trash-can"></i> Baja</button>';
        }
        $acc .= '</div>';

        return [
          'id'=>$this->id,
          'nombre'=>$this->nombre,
          'color_html'=>$colorHtml,
          'telefono'=>$this->telefono,
          'email'=>$this->email,
          'estatus_html'=>$estatus,
          'acciones_html'=>$acc,
        ];
    }
}
