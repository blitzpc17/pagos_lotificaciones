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
        // rol activo
        $rolOk = DB::table('roles')
            ->where('id', $user->role_id)
            ->where('is_active', true)
            ->where('baja', false)
            ->exists();

        if (!$rolOk) return collect();

        // módulos del rol
        $roleModuleIds = DB::table('roles_modulos')
            ->where('role_id', $user->role_id)
            ->pluck('modulo_id')
            ->values();

        if ($roleModuleIds->isEmpty()) return collect();

        // overrides por usuario (permisos)
        $userPerms = DB::table('usuarios_permisos_modulo')
            ->where('usuario_id', $user->id)
            ->get()
            ->keyBy('modulo_id');

        // módulos activos
        $mods = Modulo::query()
            ->whereIn('id', $roleModuleIds)
            ->where('is_active', true)
            ->where('baja', false)
            ->orderBy('parent_id')
            ->orderBy('orden')
            ->get();

        // filtrar por puede_ver efectivo
        $allowed = $mods->filter(function ($m) use ($userPerms) {
            if (!isset($userPerms[$m->id])) return true; // por rol: ver=true
            return (bool)$userPerms[$m->id]->puede_ver;
        })->values();

        if ($allowed->isEmpty()) return collect();

        // asegurar padres (para árbol)
        $missingParents = $allowed->pluck('parent_id')->filter()->unique()
            ->diff($allowed->pluck('id')->unique());

        if ($missingParents->isNotEmpty()) {
            $parents = Modulo::query()
                ->whereIn('id', $missingParents)
                ->where('is_active', true)
                ->where('baja', false)
                ->get();

            $allowed = $parents->merge($allowed)
                ->unique('id')
                ->sortBy([['parent_id', 'asc'], ['orden', 'asc']])
                ->values();
        }

        // solo menú
        $allowed = $allowed->where('es_menu', true)->values();

        // armar árbol
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
        // rol activo
        $rolOk = DB::table('roles')
            ->where('id', $user->role_id)
            ->where('is_active', true)
            ->where('baja', false)
            ->exists();
        if (!$rolOk) return false;

        // módulo activo
        $modOk = DB::table('modulos')
            ->where('id', $moduloId)
            ->where('is_active', true)
            ->where('baja', false)
            ->exists();
        if (!$modOk) return false;

        // rol debe tener el módulo
        $hasRole = DB::table('roles_modulos')
            ->where('role_id', $user->role_id)
            ->where('modulo_id', $moduloId)
            ->exists();
        if (!$hasRole) return false;

        // override por usuario (si no hay, default ver=true)
        $perm = DB::table('usuarios_permisos_modulo')
            ->where('usuario_id', $user->id)
            ->where('modulo_id', $moduloId)
            ->first();

        if (!$perm) return $action === 'ver';

        return match ($action) {
            'ver' => (bool)$perm->puede_ver,
            'crear' => (bool)$perm->puede_crear,
            'modificar' => (bool)$perm->puede_modificar,
            'baja' => (bool)$perm->puede_baja,
            default => false,
        };
    }
}