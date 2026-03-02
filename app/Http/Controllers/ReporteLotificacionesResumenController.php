<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteLotificacionesResumenController extends Controller
{
    public function index()
    {
        $oficinas = DB::table('lotificaciones')
            ->where('baja', false)
            ->whereNotNull('oficina')
            ->select('oficina')
            ->distinct()
            ->orderBy('oficina')
            ->pluck('oficina');

        return view('reportes.lotificaciones_resumen', compact('oficinas'));
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
     * Subquery por boleta:
     * - contrato (contado/credito)
     * - enganche (solo credito)
     * - cobrado_total (histórico)
     * - cobrado_mes (solo mes consultado)
     */
    private function boletaTotalsSubquery(string $from, string $to)
    {
        return DB::table('boletas_pago as b')
            ->leftJoin('boletas_partidas as bp', function($j){
                $j->on('bp.boleta_id','=','b.id')->where('bp.baja', false);
            })
            ->where('b.baja', false)
            ->selectRaw("
                b.id as boleta_id,
                b.lotificacion_id,

                CASE WHEN COALESCE(b.meses,0) > 0
                     THEN COALESCE(b.costo_credito,0)
                     ELSE COALESCE(b.costo_contado,0)
                END as contrato,

                CASE WHEN COALESCE(b.meses,0) > 0
                     THEN COALESCE(b.enganche,0)
                     ELSE 0
                END as enganche,

                SUM(
                    COALESCE(bp.monto,0)
                    + CASE WHEN bp.recargo = true THEN COALESCE(bp.monto_recargo,0) ELSE 0 END
                ) as cobrado_total,

                SUM(
                    CASE
                        WHEN bp.fecha_pago BETWEEN '{$from}' AND '{$to}'
                        THEN (COALESCE(bp.monto,0)
                              + CASE WHEN bp.recargo = true THEN COALESCE(bp.monto_recargo,0) ELSE 0 END)
                        ELSE 0
                    END
                ) as cobrado_mes
            ")
            ->groupBy('b.id','b.lotificacion_id','b.meses','b.costo_credito','b.costo_contado','b.enganche');
    }

    private function baseQuery(Request $request)
    {
        [$month, $year, $from, $to] = $this->period($request);
        $oficina = trim((string)$request->input('oficina',''));

        $sub = $this->boletaTotalsSubquery($from, $to);

        $q = DB::query()
            ->fromSub($sub, 'bt')
            ->join('lotificaciones as l','l.id','=','bt.lotificacion_id')
            ->where('l.baja', false)
            ->selectRaw("
                l.id as lotificacion_id,
                l.oficina,
                l.nombre as lotificacion,

                SUM(bt.contrato) as contratos,
                SUM(bt.enganche) as enganches,
                SUM(COALESCE(bt.cobrado_total,0)) as cobrado,

                SUM(GREATEST(0, COALESCE(bt.contrato,0) - COALESCE(bt.cobrado_total,0))) as resto_por_cobrar,

                SUM(COALESCE(bt.cobrado_mes,0)) as ingreso_mensual
            ")
            ->groupBy('l.id','l.oficina','l.nombre');

        if ($oficina !== '') $q->where('l.oficina', $oficina);

        // search (datatable)
        $search = trim((string)$request->input('search.value',''));
        if ($search !== '') {
            $q->havingRaw("
                LOWER(l.nombre) LIKE LOWER(?) OR LOWER(COALESCE(l.oficina,'')) LIKE LOWER(?)
            ", ["%{$search}%","%{$search}%"]);
        }

        return [$q, $month, $year];
    }

    public function datatable(Request $request)
    {
        $draw  = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $len   = (int) $request->input('length', 25);

        [$base, $month, $year] = $this->baseQuery($request);

        $recordsFiltered = (clone $base)->count();
        $recordsTotal = $recordsFiltered;

        $rows = $base
            ->orderBy('l.oficina')
            ->orderBy('l.nombre')
            ->offset($start)->limit($len)
            ->get();

        $money = fn($v)=> '$ '.number_format((float)$v, 2);

        $data = $rows->map(function($r) use ($money){
            return [
                'oficina' => e($r->oficina ?? ''),
                'lotificacion' => e($r->lotificacion ?? ''),
                'contratos' => $money($r->contratos),
                'enganches' => $money($r->enganches),
                'cobrado' => $money($r->cobrado),
                'resto_por_cobrar' => $money($r->resto_por_cobrar),
                'ingreso_mensual' => $money($r->ingreso_mensual),
            ];
        });

        return response()->json([
            'draw'=>$draw,
            'recordsTotal'=>$recordsTotal,
            'recordsFiltered'=>$recordsFiltered,
            'data'=>$data
        ]);
    }

    public function totales(Request $request)
    {
        [$base] = $this->baseQuery($request);
        $rows = (clone $base)->get();

        $sum = [
            'contratos'=>0.0,'enganches'=>0.0,'cobrado'=>0.0,'resto'=>0.0,'ingreso'=>0.0
        ];

        foreach($rows as $r){
            $sum['contratos'] += (float)$r->contratos;
            $sum['enganches'] += (float)$r->enganches;
            $sum['cobrado'] += (float)$r->cobrado;
            $sum['resto'] += (float)$r->resto_por_cobrar;
            $sum['ingreso'] += (float)$r->ingreso_mensual;
        }

        return response()->json($sum);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$base, $month, $year] = $this->baseQuery($request);
        $mesNombre = mb_strtoupper(Carbon::create($year,$month,1)->translatedFormat('F'),'UTF-8');
        $filename = "resumen_lotificaciones_{$mesNombre}_{$year}.csv";

        $rows = (clone $base)->orderBy('l.oficina')->orderBy('l.nombre')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function() use ($rows, $mesNombre, $year){
            $out = fopen('php://output','w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['OFICINA','LOTIFICACION','CONTRATOS','ENGANCHES','COBRADO','RESTO POR COBRAR',"INGRESO {$mesNombre} {$year}"]);

            $t = ['contratos'=>0,'enganches'=>0,'cobrado'=>0,'resto'=>0,'ingreso'=>0];

            foreach($rows as $r){
                $t['contratos'] += (float)$r->contratos;
                $t['enganches'] += (float)$r->enganches;
                $t['cobrado'] += (float)$r->cobrado;
                $t['resto'] += (float)$r->resto_por_cobrar;
                $t['ingreso'] += (float)$r->ingreso_mensual;

                fputcsv($out, [
                    $r->oficina ?? '',
                    $r->lotificacion ?? '',
                    number_format((float)$r->contratos,2,'.',''),
                    number_format((float)$r->enganches,2,'.',''),
                    number_format((float)$r->cobrado,2,'.',''),
                    number_format((float)$r->resto_por_cobrar,2,'.',''),
                    number_format((float)$r->ingreso_mensual,2,'.',''),
                ]);
            }

            // ✅ Totales al final como Excel
            fputcsv($out, []);
            fputcsv($out, ['TOTAL','',
                number_format($t['contratos'],2,'.',''),
                number_format($t['enganches'],2,'.',''),
                number_format($t['cobrado'],2,'.',''),
                number_format($t['resto'],2,'.',''),
                number_format($t['ingreso'],2,'.',''),
            ]);

            fclose($out);
        }, 200, $headers);
    }

    // XLSX (requiere phpoffice/phpspreadsheet). Si no lo tienes, usa CSV.
    public function exportXlsx(Request $request)
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            abort(500, 'PhpSpreadsheet no está instalado. Usa export CSV o instala phpoffice/phpspreadsheet.');
        }

        [$base, $month, $year] = $this->baseQuery($request);
        $mesNombre = mb_strtoupper(Carbon::create($year,$month,1)->translatedFormat('F'),'UTF-8');

        $rows = (clone $base)->orderBy('l.oficina')->orderBy('l.nombre')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        $headers = ['OFICINA','LOTIFICACION','CONTRATOS','ENGANCHES','COBRADO','RESTO POR COBRAR',"INGRESO {$mesNombre} {$year}"];
        $sheet->fromArray($headers, null, 'A1');

        $r = 2;
        $tot = ['contratos'=>0,'enganches'=>0,'cobrado'=>0,'resto'=>0,'ingreso'=>0];

        foreach($rows as $row){
            $tot['contratos'] += (float)$row->contratos;
            $tot['enganches'] += (float)$row->enganches;
            $tot['cobrado'] += (float)$row->cobrado;
            $tot['resto'] += (float)$row->resto_por_cobrar;
            $tot['ingreso'] += (float)$row->ingreso_mensual;

            $sheet->fromArray([
                $row->oficina ?? '',
                $row->lotificacion ?? '',
                (float)$row->contratos,
                (float)$row->enganches,
                (float)$row->cobrado,
                (float)$row->resto_por_cobrar,
                (float)$row->ingreso_mensual
            ], null, "A{$r}");

            $r++;
        }

        // Totales
        $r += 1;
        $sheet->setCellValue("A{$r}", "TOTAL");
        $sheet->setCellValue("C{$r}", $tot['contratos']);
        $sheet->setCellValue("D{$r}", $tot['enganches']);
        $sheet->setCellValue("E{$r}", $tot['cobrado']);
        $sheet->setCellValue("F{$r}", $tot['resto']);
        $sheet->setCellValue("G{$r}", $tot['ingreso']);

        // Formato básico
        $sheet->getStyle("A1:G1")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);

        foreach (range('C','G') as $col) {
            $sheet->getStyle("{$col}2:{$col}{$r}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach(range('A','G') as $col){
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = "resumen_lotificaciones_{$mesNombre}_{$year}.xlsx";

        return response()->streamDownload(function() use ($writer){
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}