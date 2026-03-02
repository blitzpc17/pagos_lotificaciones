<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportePagosController extends Controller
{
    public function index()
    {
        $oficinas = DB::table('boletas_pago')
            ->where('baja', false)
            ->whereNotNull('oficina')
            ->select('oficina')
            ->distinct()
            ->orderBy('oficina')
            ->pluck('oficina');

        $lotificaciones = DB::table('lotificaciones')
            ->where('baja', false)
            ->orderBy('nombre')
            ->get(['id','nombre']);

        return view('reportes.pagos', compact('oficinas','lotificaciones'));
    }

    private function period(Request $request): array
    {
        $month = (int) ($request->input('month') ?: now()->month);
        $year  = (int) ($request->input('year')  ?: now()->year);

        $from = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $to   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return [$month, $year, $from, $to];
    }

    private function baseQuery(Request $request)
    {
        [$month, $year, $from, $to] = $this->period($request);

        $oficina = trim((string) $request->input('oficina', ''));
        $lotificacionId = (int) ($request->input('lotificacion_id') ?: 0);

        // ✅ Campo mensualidad:
        // si meses > 0 => (costo_credito - enganche)/meses
        // si meses <= 0 => 0
        $mensualidadExpr = "CASE WHEN COALESCE(b.meses,0) > 0
            THEN ROUND((COALESCE(b.costo_credito,0) - COALESCE(b.enganche,0)) / NULLIF(b.meses,0), 2)
            ELSE 0 END";

        // ✅ Observación base: si contado -> "PAGADO AL CONTADO"
        // y además concatenamos observacion de partida
        $obsExpr = "trim(
            CASE WHEN COALESCE(b.meses,0) <= 0 THEN 'PAGADO AL CONTADO' ELSE '' END
            || CASE
                WHEN COALESCE(bp.observacion,'') <> '' AND COALESCE(b.meses,0) <= 0 THEN ' | '||bp.observacion
                WHEN COALESCE(bp.observacion,'') <> '' AND COALESCE(b.meses,0) > 0 THEN bp.observacion
                ELSE ''
               END
        )";

        $q = DB::table('boletas_partidas as bp')
            ->join('boletas_pago as b','b.id','=','bp.boleta_id')
            ->join('clientes as c','c.id','=','b.cliente_id')
            ->join('personas as p','p.id','=','c.persona_id')
            ->leftJoin('persona_telefonos as t', function($j){
                $j->on('t.persona_id','=','p.id')
                  ->where('t.baja', false)
                  ->where('t.es_principal', true);
            })
            ->join('lotificaciones as l','l.id','=','b.lotificacion_id')
            ->join('lotes as lo','lo.id','=','b.lote_id')
            ->where('bp.baja', false)
            ->whereBetween('bp.fecha_pago', [$from, $to])
            ->select([
                'bp.id',
                'b.oficina',
                'l.nombre as lotificacion',
                'lo.clave_lote as lote',
                DB::raw("trim(p.nombres||' '||p.apellido_paterno||' '||coalesce(p.apellido_materno,'')) as cliente"),
                DB::raw("coalesce(t.telefono,'') as telefono"),
                DB::raw("COALESCE(b.meses,0) as meses"),
                DB::raw("$mensualidadExpr as mensualidad"),

                'bp.monto',
                'bp.recargo',
                'bp.monto_recargo',
                'bp.tipo_pago',
                'bp.folio_partida',
                DB::raw("$obsExpr as observacion"),
                'bp.fecha_pago',
            ]);

        if ($oficina !== '') $q->where('b.oficina', $oficina);
        if ($lotificacionId > 0) $q->where('b.lotificacion_id', $lotificacionId);

        // búsqueda datatables
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $q->where(function($qq) use ($search){
                $qq->where('b.oficina','ilike',"%{$search}%")
                   ->orWhere('l.nombre','ilike',"%{$search}%")
                   ->orWhere('lo.clave_lote','ilike',"%{$search}%")
                   ->orWhere('bp.folio_partida','ilike',"%{$search}%")
                   ->orWhere(DB::raw("trim(p.nombres||' '||p.apellido_paterno||' '||coalesce(p.apellido_materno,''))"),'ilike',"%{$search}%");
            });
        }

        return [$q, $month, $year, $from, $to];
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 25);

        [$base, $month, $year] = $this->baseQuery($request);

        // “recordsTotal” en server-side real es sin search; aquí lo dejamos funcional:
        $recordsFiltered = (clone $base)->count();
        $recordsTotal = $recordsFiltered;

        $rows = $base
            ->orderBy('b.oficina')
            ->orderBy('l.nombre')
            ->orderBy('lo.clave_lote')
            ->orderBy('bp.fecha_pago')
            ->offset($start)->limit($len)
            ->get();

        $data = $rows->map(function($r){
            $tipo = strtoupper((string)$r->tipo_pago);
            $isEnganche = ($tipo === 'ENGANCHE');
            $isRecargo = (bool)$r->recargo;

            // REAL PAGADO: monto SOLO si no es enganche y no es recargo
            $realPagado = (!$isEnganche && !$isRecargo) ? (float)$r->monto : 0;

            // APARTADO/ENGANCHE: monto si tipo=ENGANCHE
            $enganche = $isEnganche ? (float)$r->monto : 0;

            // COBRO RECARGO: solo si recargo=true => monto_recargo
            $cobroRec = $isRecargo ? (float)($r->monto_recargo ?? 0) : null;

            return [
                'oficina' => e($r->oficina ?? ''),
                'lotificacion' => e($r->lotificacion ?? ''),
                'lote' => e($r->lote ?? ''),
                'cliente' => e($r->cliente ?? ''),
                'telefono' => e($r->telefono ?? ''),
                'mensualidad' => '$ '.number_format((float)$r->mensualidad, 2),
                'real_pagado' => $realPagado > 0 ? ('$ '.number_format($realPagado, 2)) : '',
                'enganche' => $enganche > 0 ? ('$ '.number_format($enganche, 2)) : '',
                'recargo' => is_null($cobroRec) ? '' : ('$ '.number_format($cobroRec, 2)),
                'folio' => e($r->folio_partida ?? ''),
                'observacion' => e($r->observacion ?? ''),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // ✅ Totales para footer (mismos filtros/mes)
    public function totales(Request $request)
    {
        [$base] = $this->baseQuery($request);

        // sumatorias según tu regla
        $rows = (clone $base)->get();

        $sumReal = 0.0;
        $sumEng  = 0.0;
        $sumRec  = 0.0;

        foreach ($rows as $r) {
            $tipo = strtoupper((string)$r->tipo_pago);
            $isEnganche = ($tipo === 'ENGANCHE');
            $isRecargo = (bool)$r->recargo;

            if (!$isEnganche && !$isRecargo) $sumReal += (float)$r->monto;
            if ($isEnganche) $sumEng += (float)$r->monto;
            if ($isRecargo) $sumRec += (float)($r->monto_recargo ?? 0);
        }

        return response()->json([
            'real_pagado' => $sumReal,
            'enganche' => $sumEng,
            'recargo' => $sumRec
        ]);
    }

    // ✅ Export CSV (stream)
    public function exportCsv(Request $request): StreamedResponse
    {
        [$base, $month, $year] = $this->baseQuery($request);

        $mesNombre = mb_strtoupper(Carbon::create($year, $month, 1)->translatedFormat('F'), 'UTF-8');
        $filename = "reporte_pagos_{$mesNombre}_{$year}.csv";

        $rows = $base
            ->orderBy('b.oficina')
            ->orderBy('l.nombre')
            ->orderBy('lo.clave_lote')
            ->orderBy('bp.fecha_pago')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function() use ($rows, $mesNombre, $year){
            $out = fopen('php://output', 'w');

            // BOM para Excel
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'OFICINA','LOTIFICACION','LOTE','NOMBRE DEL CLIENTE','NUM',
                'MENSUALIDAD',
                "REAL PAGADO {$mesNombre} {$year}",
                'APARTADO/ENGANCHE','COBRO DE RECARGO','FOLIO','OBSERVACION'
            ]);

            foreach ($rows as $r) {
                $tipo = strtoupper((string)$r->tipo_pago);
                $isEnganche = ($tipo === 'ENGANCHE');
                $isRecargo = (bool)$r->recargo;

                $realPagado = (!$isEnganche && !$isRecargo) ? (float)$r->monto : 0;
                $enganche = $isEnganche ? (float)$r->monto : 0;
                $cobroRec = $isRecargo ? (float)($r->monto_recargo ?? 0) : 0;

                fputcsv($out, [
                    $r->oficina ?? '',
                    $r->lotificacion ?? '',
                    $r->lote ?? '',
                    $r->cliente ?? '',
                    $r->telefono ?? '',
                    number_format((float)$r->mensualidad,2,'.',''),
                    $realPagado > 0 ? number_format($realPagado,2,'.','') : '',
                    $enganche > 0 ? number_format($enganche,2,'.','') : '',
                    $isRecargo ? number_format($cobroRec,2,'.','') : '',
                    $r->folio_partida ?? '',
                    $r->observacion ?? '',
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }
}