<?php

namespace App\Services;

use App\Models\AccionUsuarioHistorial;
use Illuminate\Http\Request;

class AuditService
{
    public static function log(
        int $usuarioId,
        string $accion,
        string $tabla,
        ?int $registroId,
        $before,
        $after,
        ?Request $request = null,
        ?int $moduloId = null
    ): void {
        $ip = $request?->ip();
        $ua = $request?->userAgent();

        AccionUsuarioHistorial::create([
            'usuario_id' => $usuarioId,
            'modulo_id' => $moduloId,
            'accion' => strtoupper($accion),
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'ip' => $ip,
            'user_agent' => $ua,
            'before_data' => $before,
            'after_data' => $after,
            'created_at' => now(),
        ]);
    }
}
