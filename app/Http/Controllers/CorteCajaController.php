<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorteCajaController extends Controller
{
    public function index()
    {
        $lotificaciones = DB::table('lotificaciones')->where('baja', false)->orderBy('nombre')->get(['id','nombre','oficina']);
        $oficinas = DB::table('lotificaciones')
            ->where('baja', false)
            ->whereNotNull('oficina')
            ->select('oficina')->distinct()->orderBy('oficina')->pluck('oficina');

        return view('reportes.corte_caja', compact('lotificaciones','oficinas'));
    }

    private function period(Request $request): array
    {
        $month = (int) ($request->input('month') ?: now()->month);
        $year  = (int) ($request->input('year')  ?: now()->year);

        $from = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $to   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return [$month, $year, $from, $to];
    }

    /**
     * Base: boletas_pago, pero SOLO boletas que tengan al menos una partida en el mes consultado.
     * Ingreso mes y cartera p/mes = SUM(bp.monto) del mes, excluyendo enganche/recargo.
     */
    private function baseQuery(Request $request)
    {
        [$month, $year, $from, $to] = $this->period($request);

        $oficina = trim((string)$request->input('oficina',''));
        $lotificacionId = (int)($request->input('lotificacion_id') ?: 0);

        $q = DB::table('boletas_pago as b')
            ->join('lotificaciones as l','l.id','=','b.lotificacion_id')
            ->join('lotes as lo','lo.id','=','b.lote_id')
            ->join('clientes as c','c.id','=','b.cliente_id')
            ->join('personas as pc','pc.id','=','c.persona_id')

            // vendedor (b.vendedor_id -> vendedores -> personas)
            ->leftJoin('vendedores as v','v.id','=','b.vendedor_id')
            ->leftJoin('personas as pv','pv.id','=','v.persona_id')

            // ✅ socio del contrato (boleta)
            ->leftJoin('socios as sb', 'sb.id', '=', 'b.socio_id')

            // ✅ fallback: socios por lotificación (si hubiera varios)
            ->leftJoin('lotificacion_socios as ls','ls.lotificacion_id','=','l.id')
            ->leftJoin('socios as sls','sls.id','=','ls.socio_id')

            // partidas del mes
            ->join('boletas_partidas as bp', function($j) use ($from,$to){
                $j->on('bp.boleta_id','=','b.id')
                ->where('bp.baja',false)
                ->whereBetween('bp.fecha_pago', [$from,$to]);
            })

            ->where('b.baja', false)
            ->selectRaw("
                b.id as boleta_id,
                l.oficina as lugar,
                l.nombre as lotificacion,
                b.fecha_contrato,
                b.tipo_venta,
                COALESCE(b.costo_contado,0) as costo_contado,
                COALESCE(b.costo_credito,0) as costo_credito,
                COALESCE(b.enganche,0) as enganche,
                COALESCE(b.comision_vendedor,0) as comision_vendedor,
                COALESCE(b.meses,0) as meses,

                lo.numero as lote_num,
                lo.manzana as mz,

                trim(pc.nombres||' '||pc.apellido_paterno||' '||coalesce(pc.apellido_materno,'')) as cliente,

                trim(coalesce(pv.nombres,'')||' '||coalesce(pv.apellido_paterno,'')||' '||coalesce(pv.apellido_materno,'')) as vendedor,

                -- ✅ socio: preferir boleta.socio_id (sb). si no existe, concatenar por lotificación
                COALESCE(
                NULLIF(sb.nombre,''),
                NULLIF(string_agg(distinct sls.nombre, ' - '), ''),
                ''
                ) as socio,

                COALESCE(
                NULLIF(sb.color,''),
                NULLIF(string_agg(distinct sls.color, ' - '), ''),
                ''
                ) as socio_color,

                1 as no_lotes,

                MIN(bp.fecha_pago) as fecha,

                SUM(
                    CASE
                        WHEN upper(bp.tipo_pago::text) = 'ENGANCHE' THEN 0
                        WHEN bp.recargo = true THEN 0
                        ELSE COALESCE(bp.monto,0)
                    END
                ) as ingreso_mes,

                SUM(
                    CASE
                        WHEN upper(bp.tipo_pago::text) = 'ENGANCHE' THEN 0
                        WHEN bp.recargo = true THEN 0
                        ELSE COALESCE(bp.monto,0)
                    END
                ) as cartera_mes
            ")
            ->groupBy(
                'b.id','l.oficina','l.nombre','b.fecha_contrato','b.tipo_venta',
                'b.costo_contado','b.costo_credito','b.enganche','b.comision_vendedor','b.meses',
                'lo.numero','lo.manzana',
                'pc.nombres','pc.apellido_paterno','pc.apellido_materno',
                'pv.nombres','pv.apellido_paterno','pv.apellido_materno',
                // ✅ necesario por Postgres porque sb.* aparece en select
                'sb.nombre','sb.color'
            );

        if ($oficina !== '') $q->where('l.oficina', $oficina);
        if ($lotificacionId > 0) $q->where('b.lotificacion_id', $lotificacionId);

        // search
        $search = trim((string)$request->input('search.value',''));
        if ($search !== '') {
            $q->havingRaw("
                LOWER(l.oficina) LIKE LOWER(?) OR
                LOWER(l.nombre) LIKE LOWER(?) OR
                LOWER(COALESCE(lo.numero,'')) LIKE LOWER(?) OR
                LOWER(trim(pc.nombres||' '||pc.apellido_paterno||' '||coalesce(pc.apellido_materno,''))) LIKE LOWER(?) OR
                LOWER(COALESCE(b.id::text,'')) LIKE LOWER(?)
            ", ["%{$search}%","%{$search}%","%{$search}%","%{$search}%","%{$search}%"]);
        }

        return [$q, $month, $year];
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 25);

        [$base, $month, $year] = $this->baseQuery($request);

        $recordsFiltered = (clone $base)->count();
        $recordsTotal = $recordsFiltered;

        $rows = $base
            ->orderBy('cliente')
            ->orderBy('boleta_id')
            ->offset($start)->limit($len)
            ->get();

        $data = $rows->map(function($r) use ($month,$year){
            $tipo = strtoupper((string)$r->tipo_venta);
            $isCredito = ((int)$r->meses) > 0;

            $costoContado = ($tipo === 'CONTADO' || !$isCredito) ? (float)$r->costo_contado : 0;
            $costoCredito = $isCredito ? (float)$r->costo_credito : 0;

            // ingreso cartera global = (costo contado o credito) - (enganche + comision)
            $baseCosto = $isCredito ? $costoCredito : $costoContado;
            $ingresoCarteraGlobal = max(0, $baseCosto - ((float)$r->enganche + (float)$r->comision_vendedor));

            // lote/mz: si fueran varios, aquí concatenarías; hoy es 1
            $lote = (string)($r->lote_num ?? '');
            $mz = (string)($r->mz ?? '');

            return [
                'cliente_group' => $r->cliente,        // para rowGroup
                'boleta_group'  => (string)$r->boleta_id,

                'lugar' => e($r->lugar ?? ''),
                'no_lotes' => (int)($r->no_lotes ?? 1),
                'fecha' => e($r->fecha ?? ''),
                'socio' => e($r->socio ?? ''),
                'socio_color' => (string)($r->socio_color ?? ''), // usaremos 1er color
                'vendedor' => e(trim((string)$r->vendedor) ?: '—'),
                'lotificacion' => e($r->lotificacion ?? ''),
                'lote' => e($lote),
                'mz' => e($mz),
                'nombre_cliente' => e($r->cliente ?? ''),
                'estatus' => e($tipo),

                'costo_contado' => $costoContado > 0 ? ('$ '.number_format($costoContado,2)) : '',
                'costo_credito' => $costoCredito > 0 ? ('$ '.number_format($costoCredito,2)) : '',
                'enganche' => '$ '.number_format((float)$r->enganche,2),
                'comision' => '$ '.number_format((float)$r->comision_vendedor,2),

                'ingreso_cartera_global' => '$ '.number_format($ingresoCarteraGlobal,2),
                'ingreso_mes' => '$ '.number_format((float)$r->ingreso_mes,2),
                'meses' => (int)($r->meses ?? 0),
                'cartera_mes' => '$ '.number_format((float)$r->cartera_mes,2),
            ];
        });

        return response()->json([
            'draw'=>$draw,
            'recordsTotal'=>$recordsTotal,
            'recordsFiltered'=>$recordsFiltered,
            'data'=>$data,
        ]);
    }

    public function totales(Request $request)
    {
        [$base] = $this->baseQuery($request);
        $rows = (clone $base)->get();

        $sumIngresoMes = 0.0;
        $sumCarteraMes = 0.0;

        foreach($rows as $r){
            $sumIngresoMes += (float)$r->ingreso_mes;
            $sumCarteraMes += (float)$r->cartera_mes;
        }

        return response()->json([
            'ingreso_mes' => $sumIngresoMes,
            'cartera_mes' => $sumCarteraMes
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$base, $month, $year] = $this->baseQuery($request);

        $mesNombre = mb_strtoupper(Carbon::create($year,$month,1)->translatedFormat('F'),'UTF-8');
        $filename = "corte_caja_{$mesNombre}_{$year}.csv";

        $rows = (clone $base)
            ->orderBy('cliente')
            ->orderBy('boleta_id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function() use ($rows, $mesNombre, $year){
            $out = fopen('php://output','w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM excel

            fputcsv($out, [
                'LUGAR','NO. LOTES','FECHA','SOCIO','VENDEDOR','LOTIFICACION','LOTE','MZ',
                'NOMBRE DEL CLIENTE','ESTATUS',
                'COSTO CONTADO','COSTO CREDITO','ENGANCHE','COMISION',
                'INGRESO CARTERA GLOBAL',
                "INGRESO {$mesNombre} {$year}",
                'MESES',
                "CARTERA P/{$mesNombre} {$year}"
            ]);

            foreach($rows as $r){
                $tipo = strtoupper((string)$r->tipo_venta);
                $isCredito = ((int)$r->meses) > 0;

                $costoContado = ($tipo === 'CONTADO' || !$isCredito) ? (float)$r->costo_contado : 0;
                $costoCredito = $isCredito ? (float)$r->costo_credito : 0;
                $baseCosto = $isCredito ? $costoCredito : $costoContado;
                $ingresoCarteraGlobal = max(0, $baseCosto - ((float)$r->enganche + (float)$r->comision_vendedor));

                fputcsv($out, [
                    $r->lugar ?? '',
                    (int)($r->no_lotes ?? 1),
                    $r->fecha ?? '',
                    $r->socio ?? '',
                    trim((string)($r->vendedor ?? '')) ?: '—',
                    $r->lotificacion ?? '',
                    $r->lote_num ?? '',
                    $r->mz ?? '',
                    $r->cliente ?? '',
                    $tipo,
                    $costoContado > 0 ? number_format($costoContado,2,'.','') : '',
                    $costoCredito > 0 ? number_format($costoCredito,2,'.','') : '',
                    number_format((float)$r->enganche,2,'.',''),
                    number_format((float)$r->comision_vendedor,2,'.',''),
                    number_format($ingresoCarteraGlobal,2,'.',''),
                    number_format((float)$r->ingreso_mes,2,'.',''),
                    (int)($r->meses ?? 0),
                    number_format((float)$r->cartera_mes,2,'.',''),
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }
}