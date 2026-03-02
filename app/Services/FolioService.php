<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FolioService
{
    /**
     * Obtiene el siguiente folio para una "clave" (ej: empleados, pagos_boleta, pagos_proveedor).
     * Guarda y actualiza el consecutivo dentro de variables_globales(nombre='folios').valor (JSONB).
     *
     * Estructura esperada:
     * {
     *   "empleados": {"prefijo":"", "longitud":4, "separador":"", "consecutivo": 1},
     *   "pagos_boleta": {"prefijo":"PB", "longitud":6, "separador":"-", "consecutivo": 1},
     *   "pagos_proveedor": {"prefijo":"PP", "longitud":6, "separador":"-", "consecutivo": 1}
     * }
     */
    public static function next(string $key): string
    {
        return DB::transaction(function() use ($key){

            $row = DB::table('variables_globales')
                ->where('nombre', 'folios')
                ->lockForUpdate()
                ->first();

            $valor = [];
            if ($row) {
                $raw = $row->valor;
                // En tu proyecto a veces guardas JSON como string en valor (por insert/updateOrInsert),
                // pero la columna es JSONB, así que puede venir como array/obj.
                if (is_string($raw)) {
                    $valor = json_decode($raw, true) ?: [];
                } else {
                    $valor = json_decode(json_encode($raw), true) ?: [];
                }
            }

            // defaults si no existe
            $defaults = [
                'empleados' => ['prefijo' => '',   'longitud' => 4, 'separador' => '',  'consecutivo' => 1],
                'pagos_boleta' => ['prefijo' => 'PB', 'longitud' => 6, 'separador' => '-', 'consecutivo' => 1],
                'pagos_proveedor' => ['prefijo' => 'PP', 'longitud' => 6, 'separador' => '-', 'consecutivo' => 1],
            ];

            if (!isset($valor[$key])) {
                $valor[$key] = $defaults[$key] ?? ['prefijo'=>'', 'longitud'=>6, 'separador'=>'', 'consecutivo'=>1];
            }

            // Asegura tipos
            $prefijo = (string)($valor[$key]['prefijo'] ?? '');
            $sep     = (string)($valor[$key]['separador'] ?? '');
            $len     = (int)($valor[$key]['longitud'] ?? 6);
            $consec  = (int)($valor[$key]['consecutivo'] ?? 1);

            $num = str_pad((string)$consec, max(1, $len), '0', STR_PAD_LEFT);
            $folio = $prefijo !== '' ? ($prefijo . $sep . $num) : $num;

            // incrementa
            $valor[$key]['consecutivo'] = $consec + 1;

            if (!$row) {
                DB::table('variables_globales')->insert([
                    'nombre' => 'folios',
                    'valor' => json_encode($valor),
                    'descripcion' => 'Consecutivos/folios por proceso',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);
            } else {
                DB::table('variables_globales')->where('id', $row->id)->update([
                    'valor' => json_encode($valor),
                    'updated_at' => now()
                ]);
            }

            return $folio;
        });
    }

    /**
     * Formatea un folio con la config, sin incrementar (útil si el usuario te manda "1" y lo quieres "0001")
     */
    public static function format(string $key, int|string $value): string
    {
        $row = DB::table('variables_globales')->where('nombre','folios')->first();
        $valor = [];
        if ($row) {
            $raw = $row->valor;
            $valor = is_string($raw) ? (json_decode($raw,true) ?: []) : (json_decode(json_encode($raw), true) ?: []);
        }
        $cfg = $valor[$key] ?? null;

        $prefijo = (string)($cfg['prefijo'] ?? '');
        $sep     = (string)($cfg['separador'] ?? '');
        $len     = (int)($cfg['longitud'] ?? 6);

        $num = str_pad((string)$value, max(1,$len), '0', STR_PAD_LEFT);
        return $prefijo !== '' ? ($prefijo.$sep.$num) : $num;
    }
}