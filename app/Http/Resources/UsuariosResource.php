<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UsuariosResource extends JsonResource
{
    public function toArray($request)
    {
        $p = $this->persona;
        $rol = $this->rol;

        $nombreCompleto = trim(implode(' ', array_filter([
            $p?->nombres,
            $p?->apellido_paterno,
            $p?->apellido_materno,
        ])));

        $isBaja = (bool)($this->baja ?? false);
        $isActive = (bool)($this->is_active ?? true);

        $estatusHtml = $isBaja
            ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> Baja</span>'
            : ($isActive
                ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Activo</span>'
                : '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--warn)"></i> Inactivo</span>'
            );

        $acciones = '<div class="dt-actions">';
        $acciones .= '<button class="mini primary btnUserEdit" data-id="'.$this->id.'"><i class="fa-regular fa-pen-to-square"></i> Editar</button>';
        if(!$isBaja){
            $acciones .= '<button class="mini danger btnUserBaja" data-id="'.$this->id.'"><i class="fa-regular fa-trash-can"></i> Baja</button>';
        }
        $acciones .= '</div>';

        return [
            'id' => $this->id,
            'nombre_completo' => $nombreCompleto ?: '(Sin nombre)',
            'username' => $this->username,
            'email' => $this->email,
            'rol' => $rol?->nombre ?: 'N/D',
            'estatus_html' => $estatusHtml,
            'acciones_html' => $acciones,
        ];
    }
}
