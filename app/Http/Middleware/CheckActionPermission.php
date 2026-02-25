<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UsuarioAccionModulo;

class CheckActionPermission
{
    public function handle(Request $request, Closure $next, string $accion)
    {
        $u = auth()->user();
        if(!$u) return redirect()->route('login');

        $moduloId = $request->attributes->get('current_modulo_id');
        if(!$moduloId) return $next($request);

        $perm = UsuarioAccionModulo::where('usuario_id',$u->id)->where('modulo_id',$moduloId)->first();

        // Si no hay fila, interpreta:
        // - ver: true
        // - crear/modificar/baja: false
        $can = match($accion){
            'ver' => $perm ? $perm->puede_ver : true,
            'crear' => $perm ? $perm->puede_crear : false,
            'modificar' => $perm ? $perm->puede_modificar : false,
            'baja' => $perm ? $perm->puede_baja : false,
            default => false
        };

        if(!$can) abort(403, 'Sin permiso para acción: '.$accion);

        return $next($request);
    }
}
