<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public static function log(
        int $usuarioId,
        string $accion,
        ?string $tabla = null,
        ?int $registroId = null,
        $before = null,
        $after = null,
        ?Request $request = null,
        ?int $moduloId = null
    ): void
    {
        $req = $request ?: request();

        DB::table('acciones_usuario_historial')->insert([
            'usuario_id'  => $usuarioId,
            'modulo_id'   => $moduloId,
            'accion'      => strtoupper($accion),
            'tabla'       => $tabla,
            'registro_id' => $registroId,

            'ip'         => $req?->ip(),
            'user_agent' => $req ? substr((string)$req->userAgent(), 0, 240) : null,

            'meta' => $req ? json_encode([
                'path'   => $req->path(),
                'method' => $req->method(),
            ]) : null,

            'before_data' => $before !== null ? json_encode($before) : null,
            'after_data'  => $after !== null ? json_encode($after) : null,

            'created_at' => now(),
        ]);
    }
}