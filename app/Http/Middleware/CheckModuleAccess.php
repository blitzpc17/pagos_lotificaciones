<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccessService;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, $modulo, $action = 'ver')
    {
        $user = auth()->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'No autenticado'], 401)
                : redirect()->route('login');
        }

        // Permitir modulo como id numérico o como nombre
        $moduloId = is_numeric($modulo)
            ? (int)$modulo
            : (int) (DB::table('modulos')->where('nombre', $modulo)->value('id') ?? 0);

        if ($moduloId <= 0) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Módulo no encontrado'], 404)
                : abort(404, 'Módulo no encontrado');
        }

        $ok = app(AccessService::class)->can($user, $moduloId, $action);

        if (!$ok) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Sin permisos'], 403)
                : abort(403, 'Sin permisos');
        }

        return $next($request);
    }
}
