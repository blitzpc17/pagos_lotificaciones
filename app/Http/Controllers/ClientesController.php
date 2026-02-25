<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Persona;
use App\Models\PersonaTelefono;
use App\Models\PersonaCorreo;
use App\Models\PersonaDireccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{
    public function index()
    {
        return view('clientes.index');
    }

    public function datatable()
    {
        $rows = Cliente::query()
            ->with('persona')
            ->where('baja',false)
            ->orderByDesc('id')
            ->get()
            ->map(function($c){
                $p = $c->persona;

                $acciones = '
                  <div class="dt-actions">
                    <button class="btn btnEditCliente" data-id="'.$c->id.'"><i class="fa-regular fa-pen-to-square"></i></button>
                    <button class="btn btnBajaCliente" data-id="'.$c->id.'"><i class="fa-solid fa-ban"></i></button>
                  </div>';

                return [
                    'id' => $c->id,
                    'nombre' => trim($p->nombres.' '.$p->apellido_paterno.' '.$p->apellido_materno),
                    'rfc' => $c->rfc,
                    'tipo_cliente' => $c->tipo_cliente,
                    'estatus' => $c->baja ? 'Baja' : 'Activo',
                    'acciones' => $acciones,
                ];
            });

        // ✅ OJO: tu JS tiene serverSide:true pero tu backend regresa simple.
        // Si quieres mantener serverSide:true necesitas responder con draw/recordsTotal...
        // Por ahora lo dejamos como tu payload actual (simple).
        return response()->json(['data'=>$rows]);
    }

    public function show($id)
    {
        $c = Cliente::with('persona')->findOrFail($id);
        return response()->json([
            'id' => $c->id,
            'rfc' => $c->rfc,
            'tipo_cliente' => $c->tipo_cliente,
            'notas_cliente' => $c->notas,
            'persona' => [
                'id' => $c->persona->id,
                'nombres' => $c->persona->nombres,
                'apellido_paterno' => $c->persona->apellido_paterno,
                'apellido_materno' => $c->persona->apellido_materno,
                'fecha_nacimiento' => optional($c->persona->fecha_nacimiento)->format('Y-m-d'),
                'notas' => $c->persona->notas,
            ],
        ]);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date',
            'notas' => 'nullable|string',

            'rfc' => 'nullable|string|max:20',
            'tipo_cliente' => 'nullable|string|max:40',
            'notas_cliente' => 'nullable|string',

            'telefonos' => 'nullable|array',
            'telefonos.*.telefono' => 'required_with:telefonos|string|max:40',
            'correos' => 'nullable|array',
            'correos.*.correo' => 'required_with:correos|email|max:160',
            'direcciones' => 'nullable|array',
        ]);

        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function () use ($req) {
            $persona = Persona::create([
                'nombres' => $req->nombres,
                'apellido_paterno' => $req->apellido_paterno,
                'apellido_materno' => $req->apellido_materno,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'notas' => $req->notas,
                'baja' => false,
            ]);

            $cliente = Cliente::create([
                'persona_id' => $persona->id,
                'rfc' => $req->rfc,
                'tipo_cliente' => $req->tipo_cliente ?: 'general',
                'notas' => $req->notas_cliente,
                'baja' => false,
            ]);

            $this->syncContactosCreate($persona->id, $req);

            return response()->json(['ok'=>true,'id'=>$cliente->id]);
        });
    }

    public function update(Request $req, $id)
    {
        $c = Cliente::with('persona')->findOrFail($id);

        $v = Validator::make($req->all(), [
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date',
            'notas' => 'nullable|string',

            'rfc' => 'nullable|string|max:20',
            'tipo_cliente' => 'nullable|string|max:40',
            'notas_cliente' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function () use ($req, $c) {
            $c->persona->update([
                'nombres' => $req->nombres,
                'apellido_paterno' => $req->apellido_paterno,
                'apellido_materno' => $req->apellido_materno,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'notas' => $req->notas,
            ]);

            $c->update([
                'rfc' => $req->rfc,
                'tipo_cliente' => $req->tipo_cliente ?: $c->tipo_cliente,
                'notas' => $req->notas_cliente,
            ]);

            return response()->json(['ok'=>true]);
        });
    }

    public function baja($id)
    {
        $c = Cliente::findOrFail($id);
        $c->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => 'Baja desde módulo clientes'
        ]);
        return response()->json(['ok'=>true]);
    }

    public function contactos($id)
    {
        $c = Cliente::findOrFail($id);
        $pid = $c->persona_id;

        return response()->json([
            'persona_id' => $pid,
            'telefonos' => PersonaTelefono::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
            'correos' => PersonaCorreo::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
            'direcciones' => PersonaDireccion::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
        ]);
    }

    public function addTelefono(Request $req, $id)
    {
        $c = Cliente::findOrFail($id);

        $v = Validator::make($req->all(), [
            'telefono' => 'required|string|max:40',
            'etiqueta' => 'nullable|string|max:40',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        PersonaTelefono::create([
            'persona_id' => $c->persona_id,
            'telefono' => $req->telefono,
            'etiqueta' => $req->etiqueta ?: 'principal',
            'es_principal' => (bool)$req->es_principal,
            'baja' => false,
        ]);

        return response()->json(['ok'=>true]);
    }

    public function addCorreo(Request $req, $id)
    {
        $c = Cliente::findOrFail($id);

        $v = Validator::make($req->all(), [
            'correo' => 'required|email|max:160',
            'etiqueta' => 'nullable|string|max:40',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        PersonaCorreo::create([
            'persona_id' => $c->persona_id,
            'correo' => $req->correo,
            'etiqueta' => $req->etiqueta ?: 'principal',
            'es_principal' => (bool)$req->es_principal,
            'baja' => false,
        ]);

        return response()->json(['ok'=>true]);
    }

    public function addDireccion(Request $req, $id)
    {
        $c = Cliente::findOrFail($id);

        $v = Validator::make($req->all(), [
            'etiqueta' => 'nullable|string|max:40',
            'calle' => 'nullable|string|max:160',
            'numero_ext' => 'nullable|string|max:30',
            'numero_int' => 'nullable|string|max:30',
            'colonia' => 'nullable|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'estado' => 'nullable|string|max:120',
            'cp' => 'nullable|string|max:12',
            'referencias' => 'nullable|string',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        PersonaDireccion::create([
            'persona_id' => $c->persona_id,
            'etiqueta' => $req->etiqueta ?: 'principal',
            'calle' => $req->calle,
            'numero_ext' => $req->numero_ext,
            'numero_int' => $req->numero_int,
            'colonia' => $req->colonia,
            'municipio' => $req->municipio,
            'estado' => $req->estado,
            'cp' => $req->cp,
            'referencias' => $req->referencias,
            'es_principal' => (bool)$req->es_principal,
            'baja' => false,
        ]);

        return response()->json(['ok'=>true]);
    }

    public function bajaContacto($tipo, $cid)
    {
        $map = [
            'tel' => PersonaTelefono::class,
            'mail' => PersonaCorreo::class,
            'dir' => PersonaDireccion::class,
        ];
        if (!isset($map[$tipo])) return response()->json(['message'=>'Tipo inválido'], 422);

        $model = $map[$tipo]::findOrFail($cid);
        $model->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => 'Baja desde UI',
        ]);

        return response()->json(['ok'=>true]);
    }

    private function syncContactosCreate(int $personaId, Request $req): void
    {
        foreach ($req->input('telefonos', []) as $t) {
            PersonaTelefono::create([
                'persona_id' => $personaId,
                'telefono' => $t['telefono'] ?? '',
                'etiqueta' => $t['etiqueta'] ?? 'principal',
                'es_principal' => (bool)($t['es_principal'] ?? false),
                'baja' => false,
            ]);
        }

        foreach ($req->input('correos', []) as $c) {
            PersonaCorreo::create([
                'persona_id' => $personaId,
                'correo' => $c['correo'] ?? '',
                'etiqueta' => $c['etiqueta'] ?? 'principal',
                'es_principal' => (bool)($c['es_principal'] ?? false),
                'baja' => false,
            ]);
        }

        foreach ($req->input('direcciones', []) as $d) {
            PersonaDireccion::create([
                'persona_id' => $personaId,
                'etiqueta' => $d['etiqueta'] ?? 'principal',
                'calle' => $d['calle'] ?? null,
                'numero_ext' => $d['numero_ext'] ?? null,
                'numero_int' => $d['numero_int'] ?? null,
                'colonia' => $d['colonia'] ?? null,
                'municipio' => $d['municipio'] ?? null,
                'estado' => $d['estado'] ?? null,
                'cp' => $d['cp'] ?? null,
                'referencias' => $d['referencias'] ?? null,
                'es_principal' => (bool)($d['es_principal'] ?? false),
                'baja' => false,
            ]);
        }
    }
}
