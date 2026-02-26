<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $u = auth()->user();
        if (!$u) return redirect()->route('login');

        $path = '/'.$request->path();

        // Busca módulo por ruta exacta (puedes adaptar si usas rutas con prefijos)
        $mod = DB::table('modulos')
            ->where('baja', false)
            ->where('is_active', true)
            ->where(function($q) use ($path){
                $q->where('ruta', $path)
                  ->orWhere('ruta', ltrim($path,'/')); // por si guardaste sin slash
            })
            ->first();

        if ($mod) {
            $request->attributes->set('current_modulo_id', (int)$mod->id);

            // valida acceso del rol al módulo
            $hasRole = DB::table('roles_modulos')
                ->where('role_id', $u->role_id)
                ->where('modulo_id', $mod->id)
                ->exists();

            if (!$hasRole) abort(403, 'Sin acceso al módulo.');
        } else {
            // si no se encuentra módulo para la ruta, deja pasar (login, assets, etc.)
            $request->attributes->set('current_modulo_id', null);
        }

        return $next($request);
    }
}