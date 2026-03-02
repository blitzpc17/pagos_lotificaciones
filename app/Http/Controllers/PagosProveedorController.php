<?php

namespace App\Http\Controllers;

use App\Services\FolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagosProveedorController extends Controller
{
    public function index()
    {
        return view('pagos_proveedor.index');
    }

    public function proveedoresSelect()
    {
        $rows = DB::table('proveedores as pr')
            ->join('personas as pe','pe.id','=','pr.persona_id')
            ->where('pr.baja',false)
            ->orderBy('pe.nombres')
            ->select('pr.id','pe.nombres','pe.apellido_paterno','pe.apellido_materno')
            ->get()
            ->map(fn($r)=>[
                'id'=>$r->id,
                'text'=>trim($r->nombres.' '.$r->apellido_paterno.' '.$r->apellido_materno)
            ]);

        return response()->json(['data'=>$rows]);
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $base = DB::table('pago_proveedor as pp')
            ->join('proveedores as pr','pr.id','=','pp.proveedor_id')
            ->join('personas as pe','pe.id','=','pr.persona_id')
            ->select(
                'pp.id','pp.folio','pp.fecha_documento','pp.monto_total','pp.baja','pp.baja_motivo',
                'pe.nombres','pe.apellido_paterno','pe.apellido_materno'
            );

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('pp.folio','ilike',"%{$search}%")
                  ->orWhere('pe.nombres','ilike',"%{$search}%")
                  ->orWhere('pe.apellido_paterno','ilike',"%{$search}%")
                  ->orWhere('pe.apellido_materno','ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('pago_proveedor')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderByDesc('pp.id')->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $prov = trim($r->nombres.' '.$r->apellido_paterno.' '.$r->apellido_materno);

            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
                : '<span class="badge ok">ACTIVO</span>';

            $acciones = '
              <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnPPPartidas" data-id="'.(int)$r->id.'" title="Partidas">
                  <i class="fa-solid fa-list"></i>
                </button>
              </div>';

            return [
                'id' => (int)$r->id,
                'folio' => e($r->folio),
                'proveedor' => e($prov),
                'fecha_documento' => e($r->fecha_documento),
                'monto_total' => '$ '.number_format((float)$r->monto_total,2),
                'estatus' => $estatus,
                'acciones' => $acciones
            ];
        });

        return response()->json([
            'draw'=>$draw,
            'recordsTotal'=>$recordsTotal,
            'recordsFiltered'=>$recordsFiltered,
            'data'=>$data
        ]);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'proveedor_id' => 'required|integer',
            'fecha_documento' => 'required|date',
            'concepto' => 'nullable|string|max:200',
            'referencia' => 'nullable|string|max:120',
            'monto_total' => 'required|numeric',
            'observaciones' => 'nullable|string'
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $id = DB::table('pago_proveedor')->insertGetId([
            'folio' => 'PROV-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(8)),
            'proveedor_id' => (int)$req->proveedor_id,
            'fecha_documento' => $req->fecha_documento,
            'fecha_registro' => now(),
            'concepto' => $req->concepto,
            'referencia' => $req->referencia,
            'monto_total' => (float)$req->monto_total,
            'observaciones' => $req->observaciones,
            'created_by' => auth()->id(),
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false
        ]);

        return response()->json(['ok'=>true,'id'=>$id]);
    }

    public function show($id)
    {
        $pp = DB::table('pago_proveedor')->where('id',$id)->first();
        abort_if(!$pp, 404);
        return response()->json(['data'=>$pp]);
    }

    public function partidas($id)
    {
        $rows = DB::table('pago_proveedor_partidas')
            ->where('pago_proveedor_id', $id)
            ->where('baja', false)
            ->orderByDesc('id')
            ->get()
            ->map(function($r){
                return [
                    'id' => (int)$r->id,
                    'folio_partida' => e($r->folio_partida),
                    'fecha_pago' => e($r->fecha_pago),
                    'forma_pago' => e($r->forma_pago),
                    'tipo_partida' => e($r->tipo_partida),
                    'monto' => '$ '.number_format((float)$r->monto,2),
                    'referencia_pago' => e($r->referencia_pago ?? ''),
                    'recibo' => '<button class="btn btnPPRecibo" data-id="'.(int)$r->id.'"><i class="fa-solid fa-print"></i></button>'
                ];
            });

        return response()->json(['data'=>$rows]);
    }

    public function addPartida(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'fecha_pago' => 'required|date',
            'forma_pago' => 'required|string',
            'tipo_partida' => 'required|string',
            'monto' => 'required|numeric|min:0.01',
            'referencia_pago' => 'nullable|string|max:160',
            'observacion' => 'nullable|string|max:255'
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $folio = FolioService::next('pagos_proveedor');

        $pid = DB::table('pago_proveedor_partidas')->insertGetId([
            'pago_proveedor_id' => (int)$id,
            'folio_partida' => $folio,
            'fecha_pago' => $req->fecha_pago,
            'forma_pago' => $req->forma_pago,
            'tipo_partida' => $req->tipo_partida,
            'monto' => (float)$req->monto,
            'referencia_pago' => $req->referencia_pago,
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
        $partida = DB::table('pago_proveedor_partidas')->where('id',$partidaId)->first();
        abort_if(!$partida, 404);

        $pago = DB::table('pago_proveedor')->where('id',$partida->pago_proveedor_id)->first();
        abort_if(!$pago, 404);

        $prov = DB::table('proveedores as pr')
            ->join('personas as pe','pe.id','=','pr.persona_id')
            ->where('pr.id',$pago->proveedor_id)
            ->select('pe.nombres','pe.apellido_paterno','pe.apellido_materno')
            ->first();

        $proveedorNombre = $prov ? trim($prov->nombres.' '.$prov->apellido_paterno.' '.$prov->apellido_materno) : '—';

        return view('recibos.recibo_pago_proveedor', compact('partida','pago','proveedorNombre'));
    }
}