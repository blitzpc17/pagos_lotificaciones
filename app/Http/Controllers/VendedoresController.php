<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use App\Models\Persona;
use App\Http\Resources\VendedoresResource;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendedoresController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson() || $request->get('json')) {
            $rows = Vendedor::with('persona')->orderByDesc('id')->get();
            return response()->json(['ok'=>true,'data'=>VendedoresResource::collection($rows)]);
        }
        return view('vendedores.index');
    }

    public function show(Vendedor $vendedor)
    {
        $vendedor->load('persona');
        return response()->json(['ok'=>true,'data'=>[
            'id'=>$vendedor->id,
            'clave'=>$vendedor->clave,
            'comision_default'=>$vendedor->comision_default,
            'baja'=>(bool)$vendedor->baja,
            'persona'=>[
                'id'=>$vendedor->persona?->id,
                'nombres'=>$vendedor->persona?->nombres,
                'apellido_paterno'=>$vendedor->persona?->apellido_paterno,
                'apellido_materno'=>$vendedor->persona?->apellido_materno,
                'fecha_nacimiento'=>$vendedor->persona?->fecha_nacimiento,
                'notas'=>$vendedor->persona?->notas,
            ],
        ]]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clave'=>['nullable','string','max:50'],
            'comision_default'=>['nullable','numeric'],

            'persona.nombres'=>['required','string','max:120'],
            'persona.apellido_paterno'=>['required','string','max:80'],
            'persona.apellido_materno'=>['nullable','string','max:80'],
            'persona.fecha_nacimiento'=>['nullable','date'],
            'persona.notas'=>['nullable','string'],
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me){
            $persona = Persona::create([
                'nombres'=>$data['persona']['nombres'],
                'apellido_paterno'=>$data['persona']['apellido_paterno'],
                'apellido_materno'=>$data['persona']['apellido_materno'] ?? null,
                'fecha_nacimiento'=>$data['persona']['fecha_nacimiento'] ?? null,
                'notas'=>$data['persona']['notas'] ?? null,
            ]);

            $vendedor = Vendedor::create([
                'persona_id'=>$persona->id,
                'clave'=>$data['clave'] ?? null,
                'comision_default'=>$data['comision_default'] ?? 0,
                'baja'=>false,
            ]);

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'CREAR','vendedores',$vendedor->id,null,[
                    'vendedor'=>$vendedor->toArray(),'persona'=>$persona->toArray()
                ],$request);
            }

            return response()->json(['ok'=>true,'message'=>'Vendedor creado correctamente.']);
        });
    }

    public function update(Request $request, Vendedor $vendedor)
    {
        $data = $request->validate([
            'clave'=>['nullable','string','max:50'],
            'comision_default'=>['nullable','numeric'],

            'persona.nombres'=>['required','string','max:120'],
            'persona.apellido_paterno'=>['required','string','max:80'],
            'persona.apellido_materno'=>['nullable','string','max:80'],
            'persona.fecha_nacimiento'=>['nullable','date'],
            'persona.notas'=>['nullable','string'],
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me, $vendedor){
            $vendedor->load('persona');
            $before = ['vendedor'=>$vendedor->toArray(),'persona'=>$vendedor->persona?->toArray()];

            $vendedor->persona->update([
                'nombres'=>$data['persona']['nombres'],
                'apellido_paterno'=>$data['persona']['apellido_paterno'],
                'apellido_materno'=>$data['persona']['apellido_materno'] ?? null,
                'fecha_nacimiento'=>$data['persona']['fecha_nacimiento'] ?? null,
                'notas'=>$data['persona']['notas'] ?? null,
            ]);

            $vendedor->update([
                'clave'=>$data['clave'] ?? null,
                'comision_default'=>$data['comision_default'] ?? 0,
            ]);

            if (class_exists(AuditService::class)) {
                $vendedor->refresh(); $vendedor->load('persona');
                AuditService::log($me->id,'MODIFICAR','vendedores',$vendedor->id,$before,[
                    'vendedor'=>$vendedor->toArray(),'persona'=>$vendedor->persona?->toArray()
                ],$request);
            }

            return response()->json(['ok'=>true,'message'=>'Vendedor actualizado correctamente.']);
        });
    }

    public function baja(Request $request, Vendedor $vendedor)
    {
        $data = $request->validate(['motivo'=>['nullable','string','max:500']]);
        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $me, $vendedor){
            $before = $vendedor->toArray();

            $vendedor->baja = true;
            $vendedor->baja_at = now();
            $vendedor->baja_by = $me->id;
            $vendedor->baja_motivo = $data['motivo'] ?? 'Baja desde UI';
            $vendedor->save();

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'BAJA','vendedores',$vendedor->id,['vendedor'=>$before],['vendedor'=>$vendedor->fresh()->toArray()],$request);
            }

            return response()->json(['ok'=>true,'message'=>'Vendedor dado de baja.']);
        });
    }
}
