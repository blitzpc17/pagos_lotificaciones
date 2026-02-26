<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuditService;

class AuditAction
{
    public function handle(Request $request, Closure $next, string $accion = 'VER')
    {
        $resp = $next($request);

        $u = auth()->user();
        if ($u) {
            $moduloId = $request->attributes->get('current_modulo_id');
            AuditService::log(
                usuarioId: $u->id,
                accion: strtoupper($accion),
                tabla: null,
                registroId: null,
                before: null,
                after: null,
                request: $request,
                moduloId: $moduloId ? (int)$moduloId : null
            );
        }

        return $resp;
    }
}