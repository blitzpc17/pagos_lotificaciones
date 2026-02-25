<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Http\Resources\ModulosResource;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModulosController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson() || $request->get('json')) {
            $rows = Modulo::with('parent')->orderBy('parent_id')->orderBy('orden')->orderBy('id')->get();
            return response()->json(['ok'=>true,'data'=>ModulosResource::collection($rows)]);
        }
        return view('modulos.index');
    }

    public function show(Modulo $modulo)
    {
        $modulo->load('parent');
        return response()->json(['ok'=>true,'data'=>$modulo]);
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
