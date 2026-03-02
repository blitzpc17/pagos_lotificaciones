<?php

namespace App\Http\Controllers;

use App\Models\Lotificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LotificacionesController extends Controller
{
    public function index()
    {
        return view('lotificaciones.index');
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $base = DB::table('lotificaciones as l')
            ->select('l.*');

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('l.nombre','ilike',"%{$search}%")
                  ->orWhere('l.oficina','ilike',"%{$search}%")
                  ->orWhere('l.estado','ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('lotificaciones')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderByDesc('l.id')->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
                : '<span class="badge ok">ACTIVO</span>';

            $acciones = '
              <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnEditLotificacion" data-id="'.(int)$r->id.'" title="Editar"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btnBajaLotificacion" data-id="'.(int)$r->id.'" title="Baja"><i class="fa-solid fa-ban"></i></button>
              </div>';

            return [
                'id' => (int)$r->id,
                'nombre' => e($r->nombre),
                'numero_lotes' => (int)$r->numero_lotes,
                'oficina' => e($r->oficina ?? ''),
                'estado' => e($r->estado ?? ''),
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
        $l = Lotificacion::findOrFail($id);
        return response()->json($l);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'nombre' => 'required|string|max:160',
            'numero_lotes' => 'nullable|integer|min:0',
            'oficina' => 'nullable|string|max:120',
            'estado' => 'nullable|string|max:80',
            'json_croquis' => 'nullable',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $id = DB::table('lotificaciones')->insertGetId([
            'nombre' => trim($req->nombre),
            'json_croquis' => $req->json_croquis ? (is_string($req->json_croquis) ? $req->json_croquis : json_encode($req->json_croquis)) : null,
            'numero_lotes' => (int)($req->numero_lotes ?? 0),
            'oficina' => $req->oficina,
            'estado' => $req->estado,
            'is_active' => true,
            'created_at'=>now(),'updated_at'=>now(),
            'baja'=>false
        ]);

        return response()->json(['ok'=>true,'id'=>$id]);
    }

    public function update(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'nombre' => 'required|string|max:160',
            'numero_lotes' => 'nullable|integer|min:0',
            'oficina' => 'nullable|string|max:120',
            'estado' => 'nullable|string|max:80',
            'json_croquis' => 'nullable',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        Lotificacion::findOrFail($id)->update([
            'nombre' => trim($req->nombre),
            'json_croquis' => $req->json_croquis ? (is_string($req->json_croquis) ? $req->json_croquis : json_encode($req->json_croquis)) : null,
            'numero_lotes' => (int)($req->numero_lotes ?? 0),
            'oficina' => $req->oficina,
            'estado' => $req->estado,
        ]);

        return response()->json(['ok'=>true]);
    }

    public function baja(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        Lotificacion::findOrFail($id)->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
        ]);

        return response()->json(['ok'=>true]);
    }
}