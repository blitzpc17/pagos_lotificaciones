<?php

namespace App\Http\Controllers;

use App\Models\BoletaPago;
use App\Models\BoletaPartida;
use App\Services\FolioService; // ✅ NUEVO
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BoletasPagoController extends Controller
{
    public function index()
    {
        $clientes = DB::table('clientes as c')
            ->join('personas as p','p.id','=','c.persona_id')
            ->where('c.baja', false)
            ->orderBy('p.nombres')
            ->get([
                'c.id',
                DB::raw("trim(p.nombres||' '||p.apellido_paterno||' '||coalesce(p.apellido_materno,'')) as nombre")
            ]);

        $vendedores = DB::table('vendedores as v')
            ->join('personas as p','p.id','=','v.persona_id')
            ->where('v.baja', false)
            ->orderBy('p.nombres')
            ->get([
                'v.id',
                DB::raw("trim(p.nombres||' '||p.apellido_paterno||' '||coalesce(p.apellido_materno,'')) as nombre")
            ]);

        $lotificaciones = DB::table('lotificaciones')->where('baja', false)->orderBy('nombre')->get(['id','nombre']);
        $socios = DB::table('socios')->where('baja', false)->orderBy('nombre')->get(['id','nombre']);

        return view('boletas.index', compact('clientes','vendedores','lotificaciones','socios'));
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $base = DB::table('boletas_pago as b')
            ->join('clientes as c','c.id','=','b.cliente_id')
            ->join('personas as pc','pc.id','=','c.persona_id')
            ->join('lotificaciones as l','l.id','=','b.lotificacion_id')
            ->join('lotes as lo','lo.id','=','b.lote_id')
            ->leftJoin('socios as s','s.id','=','b.socio_id')
            ->leftJoin('vendedores as v','v.id','=','b.vendedor_id')
            ->leftJoin('personas as pv','pv.id','=','v.persona_id')
            ->select(
                'b.id','b.folio','b.fecha_contrato','b.tipo_venta','b.baja','b.baja_motivo',
                'b.costo_contado','b.costo_credito','b.enganche','b.meses',
                'l.nombre as lotificacion','lo.clave_lote',
                's.nombre as socio',
                DB::raw("trim(pc.nombres||' '||pc.apellido_paterno||' '||coalesce(pc.apellido_materno,'')) as cliente"),
                DB::raw("trim(coalesce(pv.nombres,'')||' '||coalesce(pv.apellido_paterno,'')||' '||coalesce(pv.apellido_materno,'')) as vendedor")
            );

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('b.folio','ilike',"%{$search}%")
                  ->orWhere('l.nombre','ilike',"%{$search}%")
                  ->orWhere('lo.clave_lote','ilike',"%{$search}%")
                  ->orWhere(DB::raw("trim(pc.nombres||' '||pc.apellido_paterno||' '||coalesce(pc.apellido_materno,''))"),'ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('boletas_pago')->count();
        $recordsFiltered = (clone $base)->count();
        $rows = $base->orderByDesc('b.id')->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
                : '<span class="badge ok">ACTIVA</span>';

            // OJO: si tu UI de boletas ya usa btnEditBoleta/btnBajaBoleta, lo dejo igual.
            // Si también quieres botón "pagos/recibos" en el listado, me dices y lo agrego.
            $acciones = '
              <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnEditBoleta" data-id="'.(int)$r->id.'" title="Editar"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btnBajaBoleta" data-id="'.(int)$r->id.'" title="Baja"><i class="fa-solid fa-ban"></i></button>
              </div>';

            return [
                'id' => (int)$r->id,
                'folio' => e($r->folio),
                'cliente' => e($r->cliente),
                'lotificacion' => e($r->lotificacion),
                'lote' => e($r->clave_lote),
                'tipo_venta' => e($r->tipo_venta),
                'fecha_contrato' => e($r->fecha_contrato),
                'total' => number_format((float)($r->tipo_venta === 'CONTADO' ? $r->costo_contado : $r->costo_credito), 2),
                'estatus' => $estatus,
                'acciones' => $acciones,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function lotesDisponibles($lotificacionId)
    {
        $rows = DB::table('lotes')
            ->where('baja', false)
            ->where('lotificacion_id', (int)$lotificacionId)
            ->where('estado', 'LIBRE')
            ->orderBy('clave_lote')
            ->get(['id','clave_lote','manzana','numero','costo_contado','costo_credito']);

        return response()->json(['data'=>$rows]);
    }

    public function show($id)
    {
        $b = BoletaPago::findOrFail($id);
        $partidas = BoletaPartida::where('boleta_id',$id)->orderByDesc('id')->get();
        return response()->json(['boleta'=>$b,'partidas'=>$partidas]);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'cliente_id' => 'required|integer',
            'vendedor_id' => 'nullable|integer',
            'lotificacion_id' => 'required|integer',
            'socio_id' => 'nullable|integer',
            'lote_id' => 'required|integer',
            'oficina' => 'nullable|string|max:120',
            'fecha_contrato' => 'required|date',
            'tipo_venta' => 'required|string',
            'costo_contado' => 'nullable|numeric|min:0',
            'costo_credito' => 'nullable|numeric|min:0',
            'enganche' => 'nullable|numeric|min:0',
            'comision_vendedor' => 'nullable|numeric|min:0',
            'meses' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req){
            $lote = DB::table('lotes')
                ->where('id',(int)$req->lote_id)
                ->where('lotificacion_id',(int)$req->lotificacion_id)
                ->where('baja', false)
                ->lockForUpdate()
                ->first();

            if (!$lote) return response()->json(['message'=>'Lote inválido para la lotificación'], 422);
            if ($lote->estado !== 'LIBRE') return response()->json(['message'=>'El lote no está disponible (no está LIBRE).'], 422);

            $folio = $this->folio('BOL');

            $boletaId = DB::table('boletas_pago')->insertGetId([
                'folio' => $folio,
                'cliente_id' => (int)$req->cliente_id,
                'vendedor_id' => $req->vendedor_id ? (int)$req->vendedor_id : null,
                'lotificacion_id' => (int)$req->lotificacion_id,
                'socio_id' => $req->socio_id ? (int)$req->socio_id : null,
                'lote_id' => (int)$req->lote_id,
                'oficina' => $req->oficina,
                'fecha_contrato' => $req->fecha_contrato,
                'tipo_venta' => $req->tipo_venta,
                'costo_contado' => (float)($req->costo_contado ?? $lote->costo_contado ?? 0),
                'costo_credito' => (float)($req->costo_credito ?? $lote->costo_credito ?? 0),
                'enganche' => (float)($req->enganche ?? 0),
                'comision_vendedor' => (float)($req->comision_vendedor ?? 0),
                'meses' => (int)($req->meses ?? 0),
                'observaciones' => $req->observaciones,
                'created_by' => auth()->id(),
                'updated_by' => null,
                'created_at'=>now(),'updated_at'=>now(),
                'baja'=>false
            ]);

            DB::table('lotes')->where('id',(int)$req->lote_id)->update([
                'estado' => 'OCUPADO',
                'updated_at' => now(),
            ]);

            return response()->json(['ok'=>true,'id'=>$boletaId]);
        });
    }

    public function update(Request $req, $id)
    {
        $b = BoletaPago::findOrFail($id);

        $v = Validator::make($req->all(), [
            'oficina' => 'nullable|string|max:120',
            'fecha_contrato' => 'required|date',
            'tipo_venta' => 'required|string',
            'costo_contado' => 'nullable|numeric|min:0',
            'costo_credito' => 'nullable|numeric|min:0',
            'enganche' => 'nullable|numeric|min:0',
            'comision_vendedor' => 'nullable|numeric|min:0',
            'meses' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $b->update([
            'oficina' => $req->oficina,
            'fecha_contrato' => $req->fecha_contrato,
            'tipo_venta' => $req->tipo_venta,
            'costo_contado' => (float)($req->costo_contado ?? 0),
            'costo_credito' => (float)($req->costo_credito ?? 0),
            'enganche' => (float)($req->enganche ?? 0),
            'comision_vendedor' => (float)($req->comision_vendedor ?? 0),
            'meses' => (int)($req->meses ?? 0),
            'observaciones' => $req->observaciones,
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['ok'=>true]);
    }

    // =========================================================
    // ✅ NUEVO: listar partidas (para JS)
    // =========================================================
    public function partidas($id)
    {
        BoletaPago::findOrFail($id);

        $rows = DB::table('boletas_partidas')
            ->where('boleta_id', (int)$id)
            ->where('baja', false)
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
                    'recibo' => '<button class="btn btnBPRecibo" data-id="'.(int)$r->id.'"><i class="fa-solid fa-print"></i></button>',
                ];
            });

        return response()->json(['data'=>$rows]);
    }

    // =========================================================
    // ✅ MOD: addPartida usa FolioService (pagos_boleta)
    // =========================================================
    public function addPartida(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'fecha_pago' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'recargo' => 'nullable|boolean',
            'monto_recargo' => 'nullable|numeric|min:0',
            'tipo_pago' => 'required|string',
            'observacion' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        BoletaPago::findOrFail($id);

        // ✅ consecutivo basado en variables_globales->folios->pagos_boleta
        $folioPartida = FolioService::next('pagos_boleta');

        $pid = DB::table('boletas_partidas')->insertGetId([
            'boleta_id' => (int)$id,
            'folio_partida' => $folioPartida,
            'fecha_pago' => $req->fecha_pago,
            'monto' => (float)$req->monto,
            'recargo' => (bool)($req->recargo ?? false),
            'monto_recargo' => (float)($req->monto_recargo ?? 0),
            'tipo_pago' => $req->tipo_pago,
            'observacion' => $req->observacion,
            'usuario_registro_id' => auth()->id(),
            'created_at'=>now(),'updated_at'=>now(),
            'baja'=>false
        ]);

        return response()->json(['ok'=>true,'id'=>$pid,'folio'=>$folioPartida]);
    }

    public function bajaPartida(Request $req, $pid)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        $p = BoletaPartida::findOrFail($pid);
        $p->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
            'usuario_baja_id' => auth()->id(),
        ]);

        return response()->json(['ok'=>true]);
    }

    public function baja(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req, $id){
            $b = BoletaPago::findOrFail($id);
            $b->update([
                'baja' => true,
                'baja_at' => now(),
                'baja_by' => auth()->id(),
                'baja_motivo' => $req->motivo,
            ]);

            DB::table('lotes')->where('id',(int)$b->lote_id)->update([
                'estado' => 'LIBERADO',
                'updated_at' => now(),
            ]);

            return response()->json(['ok'=>true]);
        });
    }

    // =========================================================
    // ✅ NUEVO: Recibo media carta (partida)
    // =========================================================
    public function reciboPartida($pid)
    {
        $partida = DB::table('boletas_partidas')->where('id', (int)$pid)->first();
        abort_if(!$partida, 404);

        $boleta = DB::table('boletas_pago')->where('id', (int)$partida->boleta_id)->first();
        abort_if(!$boleta, 404);

        $cli = DB::table('clientes as c')
            ->join('personas as p','p.id','=','c.persona_id')
            ->where('c.id', (int)$boleta->cliente_id)
            ->select('p.nombres','p.apellido_paterno','p.apellido_materno')
            ->first();

        $clienteNombre = $cli ? trim($cli->nombres.' '.$cli->apellido_paterno.' '.$cli->apellido_materno) : '—';

        $lotificacion = DB::table('lotificaciones')->where('id', (int)$boleta->lotificacion_id)->first();
        $lote = DB::table('lotes')->where('id', (int)$boleta->lote_id)->first();

        return view('recibos.recibo_boleta_pago', compact('partida','boleta','clienteNombre','lotificacion','lote'));
    }

    // Mantengo tu folio original para cabecera (boleta) si así lo quieres.
    // Los pagos ya quedan con consecutivo por JSON.
    private function folio(string $prefix): string
    {
        return $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random(10));
    }
}