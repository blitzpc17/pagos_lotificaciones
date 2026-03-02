<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LotesController extends Controller
{
    public function index()
    {
        $lotificaciones = DB::table('lotificaciones')->where('baja', false)->orderBy('nombre')->get(['id','nombre']);
        return view('lotes.index', compact('lotificaciones'));
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));
        $lotificacionId = $request->input('lotificacion_id');

        $base = DB::table('lotes as lo')
            ->join('lotificaciones as l','l.id','=','lo.lotificacion_id')
            ->select(
                'lo.id','lo.clave_lote','lo.manzana','lo.numero','lo.estado',
                'lo.costo_contado','lo.costo_credito','lo.baja','lo.baja_motivo',
                'l.nombre as lotificacion'
            );

        if ($lotificacionId) {
            $base->where('lo.lotificacion_id', (int)$lotificacionId);
        }

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('lo.clave_lote','ilike',"%{$search}%")
                  ->orWhere('lo.manzana','ilike',"%{$search}%")
                  ->orWhere('lo.numero','ilike',"%{$search}%")
                  ->orWhere('l.nombre','ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('lotes')->count();
        $recordsFiltered = (clone $base)->count();
        $rows = $base->orderByDesc('lo.id')->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
                : '<span class="badge ok">ACTIVO</span>';

            $acciones = '
              <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnEditLote" data-id="'.(int)$r->id.'" title="Editar"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btnBajaLote" data-id="'.(int)$r->id.'" title="Baja"><i class="fa-solid fa-ban"></i></button>
              </div>';

            return [
                'id' => (int)$r->id,
                'lotificacion' => e($r->lotificacion),
                'clave_lote' => e($r->clave_lote),
                'manzana' => e($r->manzana ?? ''),
                'numero' => e($r->numero ?? ''),
                'estado' => e($r->estado),
                'costo_contado' => number_format((float)$r->costo_contado, 2),
                'costo_credito' => number_format((float)$r->costo_credito, 2),
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

    public function show($id)
    {
        $l = Lote::findOrFail($id);
        return response()->json($l);
    }

    public function byLotificacion($lotificacionId)
    {
        $rows = DB::table('lotes')
            ->where('baja', false)
            ->where('lotificacion_id', (int)$lotificacionId)
            ->orderBy('clave_lote')
            ->get(['id','clave_lote','estado','manzana','numero']);
        return response()->json(['data'=>$rows]);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'lotificacion_id' => 'required|integer',
            'clave_lote' => 'required|string|max:80',
            'manzana' => 'nullable|string|max:40',
            'numero' => 'nullable|string|max:40',
            'estado' => 'required|string',
            'costo_contado' => 'nullable|numeric|min:0',
            'costo_credito' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $id = DB::table('lotes')->insertGetId([
            'lotificacion_id' => (int)$req->lotificacion_id,
            'clave_lote' => trim($req->clave_lote),
            'manzana' => $req->manzana,
            'numero' => $req->numero,
            'estado' => $req->estado,
            'costo_contado' => (float)($req->costo_contado ?? 0),
            'costo_credito' => (float)($req->costo_credito ?? 0),
            'notas' => $req->notas,
            'created_at'=>now(),'updated_at'=>now(),
            'baja'=>false
        ]);

        // opcional: actualizar conteo de lotes
        DB::table('lotificaciones')->where('id',(int)$req->lotificacion_id)->update([
            'numero_lotes' => DB::raw('(SELECT count(*) FROM lotes WHERE lotificacion_id='.(int)$req->lotificacion_id.' AND baja=false)'),
            'updated_at' => now(),
        ]);

        return response()->json(['ok'=>true,'id'=>$id]);
    }

    public function update(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'lotificacion_id' => 'required|integer',
            'clave_lote' => 'required|string|max:80',
            'manzana' => 'nullable|string|max:40',
            'numero' => 'nullable|string|max:40',
            'estado' => 'required|string',
            'costo_contado' => 'nullable|numeric|min:0',
            'costo_credito' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $lote = Lote::findOrFail($id);
        $oldLotificacionId = (int)$lote->lotificacion_id;

        $lote->update([
            'lotificacion_id' => (int)$req->lotificacion_id,
            'clave_lote' => trim($req->clave_lote),
            'manzana' => $req->manzana,
            'numero' => $req->numero,
            'estado' => $req->estado,
            'costo_contado' => (float)($req->costo_contado ?? 0),
            'costo_credito' => (float)($req->costo_credito ?? 0),
            'notas' => $req->notas,
        ]);

        // recalcular conteos si se movió de lotificación
        $newLotificacionId = (int)$req->lotificacion_id;
        foreach(array_unique([$oldLotificacionId, $newLotificacionId]) as $lid){
            DB::table('lotificaciones')->where('id',$lid)->update([
                'numero_lotes' => DB::raw('(SELECT count(*) FROM lotes WHERE lotificacion_id='.$lid.' AND baja=false)'),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['ok'=>true]);
    }

    public function baja(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        $l = Lote::findOrFail($id);
        $l->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
        ]);

        DB::table('lotificaciones')->where('id',(int)$l->lotificacion_id)->update([
            'numero_lotes' => DB::raw('(SELECT count(*) FROM lotes WHERE lotificacion_id='.(int)$l->lotificacion_id.' AND baja=false)'),
            'updated_at' => now(),
        ]);

        return response()->json(['ok'=>true]);
    }
}