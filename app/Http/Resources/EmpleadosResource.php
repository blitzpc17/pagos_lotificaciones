<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmpleadosResource extends JsonResource
{
    public function toArray($request)
    {
        $p = $this->persona;

        $nombre = trim(($p->nombres ?? '').' '.($p->apellido_paterno ?? '').' '.($p->apellido_materno ?? ''));

        $estatus = $this->baja
          ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> Baja</span>'
          : '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Activo</span>';

        $acc = '<div class="dt-actions">';
        $acc .= '<button class="mini primary btnEmpleadoEdit" data-id="'.$this->id.'"><i class="fa-regular fa-pen-to-square"></i> Editar</button>';
        if(!$this->baja){
          $acc .= '<button class="mini danger btnEmpleadoBaja" data-id="'.$this->id.'"><i class="fa-regular fa-trash-can"></i> Baja</button>';
        }
        $acc .= '</div>';

        return [
            'id' => $this->id,
            'nombre_completo' => $nombre,
            'puesto' => (string)$this->puesto,
            'numero_empleado' => $this->numero_empleado,
            'estatus_html' => $estatus,
            'acciones_html' => $acc,
        ];
    }
}
