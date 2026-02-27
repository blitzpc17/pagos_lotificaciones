<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Http\Resources\ModulosResource;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModulosController extends Controller
{
    public function index() {
        return view('modulos.index');
    }

    public function parents() {
        $rows = DB::table('modulos')
            ->whereNull('parent_id')
            ->where('baja', false)
            ->orderBy('orden')->orderBy('id')
            ->get(['id','nombre']);
        return response()->json(['data'=>$rows]);
    }

    public function show($id) {
        $m = DB::table('modulos')->where('id',$id)->first();
        if(!$m) abort(404);
        return response()->json(['ok'=>true,'data'=>$m]);
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $base = \DB::table('modulos as m')
            ->leftJoin('modulos as p','p.id','=','m.parent_id')
            ->select(
                'm.id','m.nombre','m.ruta','m.icono','m.parent_id','m.es_menu','m.orden','m.is_active','m.baja','m.baja_motivo',
                \DB::raw("COALESCE(p.nombre,'—') as padre")
            );

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('m.nombre','ilike',"%{$search}%")
                ->orWhere('m.ruta','ilike',"%{$search}%")
                ->orWhere('m.icono','ilike',"%{$search}%")
                ->orWhereRaw("COALESCE((select nombre from modulos where id=m.parent_id),'') ilike ?", ["%{$search}%"]);
            });
        }

        $recordsTotal = \DB::table('modulos')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy('m.parent_id')->orderBy('m.orden')->orderBy('m.id')
            ->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
                : ($r->is_active ? '<span class="badge ok">ACTIVO</span>' : '<span class="badge warn">INACTIVO</span>');

            $esMenu = $r->es_menu ? '<span class="badge ok">Sí</span>' : '<span class="badge">No</span>';

            $acciones = '
            <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnEditModulo" data-id="'.(int)$r->id.'" title="Editar"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btnBajaModulo" data-id="'.(int)$r->id.'" title="Baja"><i class="fa-solid fa-ban"></i></button>
            </div>';

            return [
                'id' => (int)$r->id,
                'padre' => e($r->padre),
                'nombre' => e($r->nombre),
                'ruta' => e($r->ruta ?? ''),
                'icono' => e($r->icono ?? ''),
                'es_menu' => $esMenu,
                'orden' => (int)$r->orden,
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'=>['required','string','max:100'],
            'ruta'=>['nullable','string','max:180'],
            'icono'=>['nullable','string','max:60'],
            'parent_id'=>['nullable','integer'],
            'es_menu'=>['required','boolean'],
            'orden'=>['nullable','integer'],
            'is_active'=>['required','boolean'],
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me){
            $modulo = Modulo::create([
                'nombre'=>$data['nombre'],
                'ruta'=>$data['ruta'] ?? null,
                'icono'=>$data['icono'] ?? null,
                'parent_id'=>$data['parent_id'] ?? null,
                'es_menu'=>$data['es_menu'],
                'orden'=>$data['orden'] ?? 0,
                'is_active'=>$data['is_active'],
                'baja'=>false,
            ]);

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'CREAR','modulos',$modulo->id,null,['modulo'=>$modulo->toArray()],$request);
            }

            return response()->json(['ok'=>true,'message'=>'Módulo creado correctamente.']);
        });
    }

    public function update(Request $request, Modulo $modulo)
    {
        $data = $request->validate([
            'nombre'=>['required','string','max:100'],
            'ruta'=>['nullable','string','max:180'],
            'icono'=>['nullable','string','max:60'],
            'parent_id'=>['nullable','integer'],
            'es_menu'=>['required','boolean'],
            'orden'=>['nullable','integer'],
            'is_active'=>['required','boolean'],
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me, $modulo){
            $before = $modulo->toArray();

            $modulo->update([
                'nombre'=>$data['nombre'],
                'ruta'=>$data['ruta'] ?? null,
                'icono'=>$data['icono'] ?? null,
                'parent_id'=>$data['parent_id'] ?? null,
                'es_menu'=>$data['es_menu'],
                'orden'=>$data['orden'] ?? 0,
                'is_active'=>$data['is_active'],
            ]);

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'MODIFICAR','modulos',$modulo->id,['modulo'=>$before],['modulo'=>$modulo->fresh()->toArray()],$request);
            }

            return response()->json(['ok'=>true,'message'=>'Módulo actualizado correctamente.']);
        });
    }

    public function baja(Request $request, Modulo $modulo)
    {
        $data = $request->validate(['motivo'=>['nullable','string','max:500']]);
        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me, $modulo){
            $before = $modulo->toArray();

            $modulo->baja = true;
            $modulo->baja_at = now();
            $modulo->baja_by = $me->id;
            $modulo->baja_motivo = $data['motivo'] ?? 'Baja desde UI';
            $modulo->save();

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'BAJA','modulos',$modulo->id,['modulo'=>$before],['modulo'=>$modulo->fresh()->toArray()],$request);
            }

            return response()->json(['ok'=>true,'message'=>'Módulo dado de baja.']);
        });
    }
}
