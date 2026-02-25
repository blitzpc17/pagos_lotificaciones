<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ConsecutivosService
{
    /**
     * variables_globales.valor JSONB:
     * { "prefix":"EMP-", "next":1, "pad":5 }
     */
    public function next(string $varName): string
    {
        return DB::transaction(function () use ($varName) {
            $row = DB::table('variables_globales')
                ->where('nombre', $varName)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                throw new \RuntimeException("Falta variable_global: {$varName}");
            }

            $v = is_string($row->valor) ? json_decode($row->valor, true) : (array)$row->valor;
            $prefix = (string)($v['prefix'] ?? '');
            $next   = (int)($v['next'] ?? 1);
            $pad    = (int)($v['pad'] ?? 4);

            $folio = $prefix . str_pad((string)$next, $pad, '0', STR_PAD_LEFT);

            DB::table('variables_globales')->where('id', $row->id)->update([
                'valor' => json_encode(['prefix'=>$prefix, 'next'=>$next + 1, 'pad'=>$pad]),
                'updated_at' => now(),
            ]);

            return $folio;
        });
    }
}
