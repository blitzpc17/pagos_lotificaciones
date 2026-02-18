<?php

namespace App\Services;

use App\Models\Modulo;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccessService
{
    public function menuFor(Usuario $user): Collection
    {
        // 1) Validar rol activo/no baja
        $rolOk = DB::table('roles')
            ->where('id', $user->role_id)
            ->where('is_active', true)
            ->where('baja', false)
            ->exists();

        if (!$rolOk) return collect();

        // 2) Módulos asignados al rol
        $roleModuleIds = DB::table('roles_modulos')
            ->where('role_id', $user->role_id)
            ->pluck('modulo_id')
            ->values();

        if ($roleModuleIds->isEmpty()) return collect();

        // 3) Overrides por usuario
        $userPerms = DB::table('usuarios_acciones_modulo')
            ->where('usuario_id', $user->id)
            ->get()
            ->keyBy('modulo_id');

        // 4) Traer módulos permitidos por rol (solo activos y no baja)
        $mods = Modulo::query()
            ->whereIn('id', $roleModuleIds)
            ->where('is_active', true)
            ->where('baja', false)
            ->orderBy('parent_id')
            ->orderBy('orden')
            ->get();

        // 5) Filtrar por puede_ver efectivo (si no hay override, default true por rol)
        $allowed = $mods->filter(function ($m) use ($userPerms) {
            if (!isset($userPerms[$m->id])) return true;
            return (bool) $userPerms[$m->id]->puede_ver;
        })->values();

        if ($allowed->isEmpty()) return collect();

        // 6) Asegurar que si hay hijos, existan sus padres en el menú
        $parentIdsMissing = $allowed->pluck('parent_id')->filter()->unique()
            ->diff($allowed->pluck('id')->unique());

        if ($parentIdsMissing->isNotEmpty()) {
            $parents = Modulo::query()
                ->whereIn('id', $parentIdsMissing)
                ->where('is_active', true)
                ->where('baja', false)
                ->get();

            // Nota: si el rol no tenía el padre asignado, lo incluimos para render de árbol
            $allowed = $parents->merge($allowed)
                ->unique('id')
                ->sortBy([['parent_id','asc'],['orden','asc']])
                ->values();
        }

        // 7) Solo items marcados como menú
        $allowed = $allowed->where('es_menu', true)->values();

        // 8) Armar árbol
        $byParent = $allowed->groupBy('parent_id');

        $tree = ($byParent[null] ?? collect())->map(function ($parent) use ($byParent) {
            $parent->children_nodes = ($byParent[$parent->id] ?? collect())
                ->sortBy('orden')
                ->values();
            return $parent;
        })->sortBy('orden')->values();

        return $tree;
    }

    public function can(Usuario $user, int $moduloId, string $action): bool
    {
        // rol debe estar activo/no baja
        $rolOk = DB::table('roles')
            ->where('id', $user->role_id)
            ->where('is_active', true)
            ->where('baja', false)
            ->exists();

        if (!$rolOk) return false;

        // módulo debe estar activo/no baja
        $modOk = DB::table('modulos')
            ->where('id', $moduloId)
            ->where('is_active', true)
            ->where('baja', false)
            ->exists();

        if (!$modOk) return false;

        // rol debe tener el módulo
        $hasRoleAccess = DB::table('roles_modulos')
            ->where('role_id', $user->role_id)
            ->where('modulo_id', $moduloId)
            ->exists();

        if (!$hasRoleAccess) return false;

        // override por usuario si existe
        $perm = DB::table('usuarios_acciones_modulo')
            ->where('usuario_id', $user->id)
            ->where('modulo_id', $moduloId)
            ->first();

        // default sin override: ver=true, crear/mod/baja=false
        if (!$perm) {
            return $action === 'ver';
        }

        return match ($action) {
            'ver' => (bool) $perm->puede_ver,
            'crear' => (bool) $perm->puede_crear,
            'modificar' => (bool) $perm->puede_modificar,
            'baja' => (bool) $perm->puede_baja,
            default => false,
        };
    }
}
