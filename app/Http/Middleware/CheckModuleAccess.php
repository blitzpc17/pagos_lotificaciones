<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Modulo;
use App\Models\RolModulo;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $moduloRutaOrNombre)
    {
        $u = auth()->user();
        if(!$u) return redirect()->route('login');

        // Buscar módulo por ruta o nombre
        $mod = Modulo::where('baja',false)
            ->where(function($q) use ($moduloRutaOrNombre){
                $q->where('ruta',$moduloRutaOrNombre)->orWhere('nombre',$moduloRutaOrNombre);
            })->first();

        if(!$mod) abort(403, 'Módulo no existe.');

        $has = RolModulo::where('role_id',$u->role_id)->where('modulo_id',$mod->id)->exists();
        if(!$has) abort(403, 'Sin acceso al módulo.');

        $request->attributes->set('current_modulo_id', $mod->id);

        return $next($request);
    }
}
