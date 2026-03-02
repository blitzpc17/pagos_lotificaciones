<?php

namespace App\Http\Controllers;

use App\Services\FolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BoletasController extends Controller
{
    public function index()
    {
        return view('boletas.index');
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        // saldo = (tipoVenta? costo - enganche) - sum(partidas.monto)   (sin recargo)
        $base = DB::table('boletas_pago as b')
            ->join('clientes as c','c.id','=','b.cliente_id')
            ->join('personas as p','p.id','=','c.persona_id')
            ->join('lotificaciones as lo','lo.id','=','b.lotificacion_id')
            ->join('lotes as lt','lt.id','=','b.lote_id')
            ->leftJoin('boletas_partidas as bp', function($j){
                $j->on('bp.boleta_id','=','b.id')->where('bp.baja',false);
            })
            ->selectRaw("
                b.id, b.folio, b.tipo_venta,
                lo.nombre as lotificacion,
                lt.clave_lote as lote,
                p.nombres, p.apellido_paterno, p.apellido_materno,
                b.costo_contado, b.costo_credito, b.enganche,
                COALESCE(SUM(bp.monto),0) as suma_pagos
            ")
            ->groupBy(
                'b.id','b.folio','b.tipo_venta','lo.nombre','lt.clave_lote',
                'p.nombres','p.apellido_paterno','p.apellido_materno',
                'b.costo_contado','b.costo_credito','b.enganche'
            );

        if ($search !== '') {
            $base->havingRaw("LOWER(b.folio) LIKE LOWER(?) OR LOWER(p.nombres) LIKE LOWER(?) OR LOWER(p.apellido_paterno) LIKE LOWER(?) OR LOWER(lo.nombre) LIKE LOWER(?) OR LOWER(lt.clave_lote) LIKE LOWER(?)",
                ["%{$search}%","%{$search}%","%{$search}%","%{$search}%","%{$search}%"]
            );
        }

        $recordsTotal = DB::table('boletas_pago')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderByDesc('id')->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $cliente = trim($r->nombres.' '.$r->apellido_paterno.' '.$r->apellido_materno);
            $base = ($r->tipo_venta === 'CONTADO') ? (float)$r->costo_contado : (float)$r->costo_credito;
            $saldo = max(0, ($base - (float)$r->enganche) - (float)$r->suma_pagos);

            return [
                'id' => (int)$r->id,
                'folio' => e($r->folio),
                'cliente' => e($cliente),
                'lotificacion' => e($r->lotificacion),
                'lote' => e($r->lote),
                'tipo_venta' => e($r->tipo_venta),
                'saldo' => '$ '.number_format($saldo,2),
                'acciones' => '
                  <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                    <button class="btn btnBoletaPagos" data-id="'.(int)$r->id.'" title="Pagos">
                      <i class="fa-solid fa-receipt"></i>
                    </button>
                  </div>'
            ];
        });

        return response()->json([
            'draw'=>$draw,
            'recordsTotal'=>$recordsTotal,
            'recordsFiltered'=>$recordsFiltered,
            'data'=>$data
        ]);
    }

    public function partidas($id)
    {
        $rows = DB::table('boletas_partidas')
            ->where('boleta_id',$id)
            ->where('baja',false)
            ->orderByDesc('id')
            ->get()
            ->map(function($r){
                $total = (float)$r->monto + (float)($r->monto_recargo ?? 0);
                return [
                    'id' => (int)$r->id,
                    'folio_partida' => e($r->folio_partida),
                    'fecha_pago' => e($r->fecha_pago),
                    'tipo_pago' => e($r->tipo_pago),
                    'monto' => '$ '.number_format((float)$r->monto,2),
                    'recargo' => $r->recargo ? ('$ '.number_format((float)$r->monto_recargo,2)) : '—',
                    'total' => '$ '.number_format($total,2),
                    'recibo' => '<button class="btn btnBPRecibo" data-id="'.(int)$r->id.'"><i class="fa-solid fa-print"></i></button>'
                ];
            });

        return response()->json(['data'=>$rows]);
    }

    public function addPago(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'fecha_pago' => 'required|date',
            'tipo_pago' => 'required|string',
            'monto' => 'required|numeric|min:0.01',
            'recargo' => 'nullable|boolean',
            'monto_recargo' => 'nullable|numeric|min:0',
            'observacion' => 'nullable|string|max:255'
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $folio = FolioService::next('pagos_boleta');

        $pid = DB::table('boletas_partidas')->insertGetId([
            'boleta_id' => (int)$id,
            'folio_partida' => $folio,
            'fecha_pago' => $req->fecha_pago,
            'monto' => (float)$req->monto,
            'recargo' => (bool)($req->recargo ?? false),
            'monto_recargo' => (float)($req->monto_recargo ?? 0),
            'tipo_pago' => $req->tipo_pago,
            'observacion' => $req->observacion,
            'usuario_registro_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false
        ]);

        return response()->json(['ok'=>true,'partida_id'=>$pid,'folio'=>$folio]);
    }

    public function reciboPartida($partidaId)
    {
        $partida = DB::table('boletas_partidas')->where('id',$partidaId)->first();
        abort_if(!$partida, 404);

        $boleta = DB::table('boletas_pago')->where('id',$partida->boleta_id)->first();
        abort_if(!$boleta, 404);

        $cli = DB::table('clientes as c')
            ->join('personas as p','p.id','=','c.persona_id')
            ->where('c.id',$boleta->cliente_id)
            ->select('p.nombres','p.apellido_paterno','p.apellido_materno')
            ->first();

        $clienteNombre = $cli ? trim($cli->nombres.' '.$cli->apellido_paterno.' '.$cli->apellido_materno) : '—';

        $lotificacion = DB::table('lotificaciones')->where('id',$boleta->lotificacion_id)->first();
        $lote = DB::table('lotes')->where('id',$boleta->lote_id)->first();

        return view('recibos.recibo_boleta_pago', compact('partida','boleta','clienteNombre','lotificacion','lote'));
    }
}