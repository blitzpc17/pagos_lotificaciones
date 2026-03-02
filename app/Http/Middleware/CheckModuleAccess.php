<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, ?string $forcedRoute = null)
    {
        $u = auth()->user();
        if (!$u) return redirect()->route('login');

        // Si viene "module:/boletas" usamos esa ruta como base.
        // Si no, usamos la ruta actual del request.
        $path = $forcedRoute
            ? (str_starts_with($forcedRoute, '/') ? $forcedRoute : '/'.$forcedRoute)
            : '/'.$request->path();

        // 1) match exacto
        $mod = DB::table('modulos')
            ->where('baja', false)
            ->where('is_active', true)
            ->where(function($q) use ($path){
                $q->where('ruta', $path)
                  ->orWhere('ruta', ltrim($path,'/'));
            })
            ->first();

        // 2) Si no hay exacto, buscar por prefijo (para subrutas: /boletas/partidas/xx/recibo)
        if (!$mod) {
            $mod = DB::table('modulos')
                ->where('baja', false)
                ->where('is_active', true)
                ->whereNotNull('ruta')
                ->orderByRaw('length(ruta) desc')
                ->get()
                ->first(function($m) use ($path){
                    $r1 = (string)$m->ruta;
                    $r2 = str_starts_with($r1,'/') ? $r1 : '/'.$r1;

                    return $r1 !== ''
                        && ($path === $r2 || str_starts_with($path.'/', rtrim($r2,'/').'/'));
                });
        }

        if ($mod) {
            $request->attributes->set('current_modulo_id', (int)$mod->id);

            $hasRole = DB::table('roles_modulos')
                ->where('role_id', $u->role_id)
                ->where('modulo_id', $mod->id)
                ->exists();

            if (!$hasRole) abort(403, 'Sin acceso al módulo.');
        } else {
            // si no encuentra módulo, deja pasar
            $request->attributes->set('current_modulo_id', null);
        }

        return $next($request);
    }
}