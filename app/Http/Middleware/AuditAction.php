<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class AuditAction
{
    public function handle($request, Closure $next, $accion = 'ver', $moduloId = null)
    {
        $resp = $next($request);

        if (auth()->check()) {
            DB::table('usuarios_acciones_modulo')->insert([
                'usuario_id' => auth()->id(),
                'modulo_id' => $moduloId ? (int)$moduloId : null,
                'accion' => $accion,
                'tabla' => null,
                'registro_id' => null,
                'meta' => json_encode([
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'ua' => substr((string)$request->userAgent(), 0, 240),
                ]),
                'created_at' => now(),
            ]);
        }

        return $resp;
    }
}
