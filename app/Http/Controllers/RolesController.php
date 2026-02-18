<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson() || $request->get('json')) {
            $rows = Rol::query()->orderBy('nombre')->get();

            $data = $rows->map(function($r){
                $isBaja = (bool)$r->baja;
                $estatus = $isBaja
                    ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> Baja</span>'
                    : ($r->is_active
                        ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Activo</span>'
                        : '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--warn)"></i> Inactivo</span>'
                    );

                $acc = '<div class="dt-actions">';
                $acc .= '<button class="mini primary btnRoleEdit" data-id="'.$r->id.'"><i class="fa-regular fa-pen-to-square"></i> Editar</button>';
                if(!$isBaja){
                    $acc .= '<button class="mini danger btnRoleBaja" data-id="'.$r->id.'"><i class="fa-regular fa-trash-can"></i> Baja</button>';
                }
                $acc .= '</div>';

                return [
                    'id' => $r->id,
                    'nombre' => $r->nombre,
                    'descripcion' => $r->descripcion,
                    'estatus_html' => $estatus,
                    'acciones_html' => $acc,
                ];
            });

            return response()->json(['ok'=>true,'data'=>$data]);
        }

        return view('roles.index');
    }

    public function show(Rol $role)
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $role->id,
                'nombre' => $role->nombre,
                'descripcion' => $role->descripcion,
                'is_active' => (bool)$role->is_active,
                'baja' => (bool)$role->baja,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required','string','max:80', 'unique:roles,nombre'],
            'descripcion' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me){
            $r = Rol::create([
                'nombre' => strtoupper(trim($data['nombre'])),
                'descripcion' => $data['descripcion'] ?? null,
                'is_active' => (bool)($data['is_active'] ?? true),
                'baja' => false,
            ]);

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id, 'CREAR', 'roles', $r->id, null, ['role'=>$r->toArray()], $request);
            }

            return response()->json(['ok'=>true,'message'=>'Rol creado correctamente.']);
        });
    }

    public function update(Request $request, Rol $role)
    {
        $data = $request->validate([
            'nombre' => ['required','string','max:80', Rule::unique('roles','nombre')->ignore($role->id)],
            'descripcion' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me, $role){
            $before = $role->toArray();

            $role->nombre = strtoupper(trim($data['nombre']));
            $role->descripcion = $data['descripcion'] ?? null;
            $role->is_active = (bool)($data['is_active'] ?? true);
            $role->save();

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id, 'MODIFICAR', 'roles', $role->id, ['role'=>$before], ['role'=>$role->fresh()->toArray()], $request);
            }

            return response()->json(['ok'=>true,'message'=>'Rol actualizado correctamente.']);
        });
    }

    public function baja(Request $request, Rol $role)
    {
        $data = $request->validate([
            'motivo' => ['nullable','string','max:500']
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me, $role){
            $before = $role->toArray();

            $role->baja = true;
            $role->is_active = false;
            $role->baja_at = now();
            $role->baja_by = $me->id;
            $role->baja_motivo = $data['motivo'] ?? 'Baja desde UI';
            $role->save();

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id, 'BAJA', 'roles', $role->id, ['role'=>$before], ['role'=>$role->fresh()->toArray()], $request);
            }

            return response()->json(['ok'=>true,'message'=>'Rol dado de baja correctamente.']);
        });
    }
}
