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

    public function datatable(Request $request)
    {
        $draw   = intval($request->input('draw', 1));
        $start  = intval($request->input('start', 0));
        $len    = intval($request->input('length', 10));
        $search = trim($request->input('search.value', ''));

        $orderColIdx = intval($request->input('order.0.column', 0));
        $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $cols = ['c.id','p.nombres','c.rfc','c.tipo_cliente','c.baja'];
        $orderBy = $cols[$orderColIdx] ?? 'c.id';

        $base = DB::table('clientes as c')
            ->join('personas as p','p.id','=','c.persona_id')
            ->select('c.id','c.rfc','c.tipo_cliente','c.baja','c.baja_motivo','p.nombres','p.apellido_paterno','p.apellido_materno');

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('p.nombres','ilike',"%{$search}%")
                  ->orWhere('p.apellido_paterno','ilike',"%{$search}%")
                  ->orWhere('p.apellido_materno','ilike',"%{$search}%")
                  ->orWhere('c.rfc','ilike',"%{$search}%")
                  ->orWhere('c.tipo_cliente','ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('clientes')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderByRaw("$orderBy $orderDir")
            ->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $nombre = trim(($r->nombres ?? '').' '.($r->apellido_paterno ?? '').' '.($r->apellido_materno ?? ''));
            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
                : '<span class="badge ok">ACTIVO</span>';

            $acciones = '
              <div class="dt-actions">
                <button class="btn btnEditCliente" data-id="'.$r->id.'"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btnBajaCliente" data-id="'.$r->id.'"><i class="fa-solid fa-ban"></i></button>
              </div>';

            return [
                'id' => $r->id,
                'nombre' => e($nombre).($r->baja ? ' <span class="badge danger" style="margin-left:8px;">BAJA</span>' : ''),
                'rfc' => e($r->rfc ?? ''),
                'tipo_cliente' => e($r->tipo_cliente ?? ''),
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
        $c = Cliente::with('persona')->findOrFail($id);
        $pid = $c->persona_id;

        return response()->json([
            'id' => $c->id,
            'persona_id' => $pid,
            'rfc' => $c->rfc,
            'tipo_cliente' => $c->tipo_cliente,
            'notas' => $c->notas,
            'baja' => (bool)$c->baja,
            'baja_motivo' => $c->baja_motivo,
            'persona' => [
                'id' => $c->persona->id,
                'nombres' => $c->persona->nombres,
                'apellido_paterno' => $c->persona->apellido_paterno,
                'apellido_materno' => $c->persona->apellido_materno,
                'fecha_nacimiento' => optional($c->persona->fecha_nacimiento)->format('Y-m-d'),
                'notas' => $c->persona->notas,
            ],
            // INCLUIR BAJAS para reactivar en UI
            'telefonos' => PersonaTelefono::where('persona_id',$pid)->orderBy('baja')->orderByDesc('es_principal')->get(),
            'correos' => PersonaCorreo::where('persona_id',$pid)->orderBy('baja')->orderByDesc('es_principal')->get(),
            'direcciones' => PersonaDireccion::where('persona_id',$pid)->orderBy('baja')->orderByDesc('es_principal')->get(),
        ]);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date',
            'persona_notas' => 'nullable|string',

            'rfc' => 'nullable|string|max:20',
            'tipo_cliente' => 'nullable|string|max:40',
            'notas' => 'nullable|string',

            'telefonos' => 'nullable|array',
            'correos' => 'nullable|array',
            'direcciones' => 'nullable|array',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req){
            $personaId = DB::table('personas')->insertGetId([
                'nombres' => mb_strtoupper(trim($req->nombres),'UTF-8'),
                'apellido_paterno' => mb_strtoupper(trim($req->apellido_paterno),'UTF-8'),
                'apellido_materno' => $req->apellido_materno ? mb_strtoupper(trim($req->apellido_materno),'UTF-8') : null,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'notas' => $req->persona_notas,
                'created_at'=>now(),'updated_at'=>now(),
                'baja'=>false
            ]);

            $cliId = DB::table('clientes')->insertGetId([
                'persona_id' => $personaId,
                'rfc' => $req->rfc,
                'tipo_cliente' => $req->tipo_cliente ?: 'general',
                'notas' => $req->notas,
                'created_at'=>now(),'updated_at'=>now(),
                'baja'=>false
            ]);

            $this->syncContactos($personaId, $req);

            return response()->json(['ok'=>true,'id'=>$cliId]);
        });
    }

    public function update(Request $req, $id)
    {
        $cli = Cliente::with('persona')->findOrFail($id);

        $v = Validator::make($req->all(), [
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date',
            'persona_notas' => 'nullable|string',

            'rfc' => 'nullable|string|max:20',
            'tipo_cliente' => 'nullable|string|max:40',
            'notas' => 'nullable|string',

            'telefonos' => 'nullable|array',
            'correos' => 'nullable|array',
            'direcciones' => 'nullable|array',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req, $cli){
            $cli->persona->update([
                'nombres' => mb_strtoupper(trim($req->nombres),'UTF-8'),
                'apellido_paterno' => mb_strtoupper(trim($req->apellido_paterno),'UTF-8'),
                'apellido_materno' => $req->apellido_materno ? mb_strtoupper(trim($req->apellido_materno),'UTF-8') : null,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'notas' => $req->persona_notas,
            ]);

            $cli->update([
                'rfc' => $req->rfc,
                'tipo_cliente' => $req->tipo_cliente ?: $cli->tipo_cliente,
                'notas' => $req->notas,
            ]);

            $this->syncContactos($cli->persona_id, $req);

            return response()->json(['ok'=>true]);
        });
    }

    public function baja(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        $cli = Cliente::findOrFail($id);
        $cli->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
        ]);

        return response()->json(['ok'=>true]);
    }

    private function syncContactos(int $personaId, Request $req): void
    {
        // misma lógica que proveedores
        $telefonos = $req->input('telefonos', []);
        $correos = $req->input('correos', []);
        $direcciones = $req->input('direcciones', []);

        if (!is_array($telefonos)) $telefonos = [];
        if (!is_array($correos)) $correos = [];
        if (!is_array($direcciones)) $direcciones = [];

        $principalId = null;
        foreach ($telefonos as $t) {
            if (empty($t['telefono'])) continue;
            $id = $t['id'] ?? null;

            $payload = [
                'persona_id'=>$personaId,
                'etiqueta'=>$t['etiqueta'] ?? 'principal',
                'telefono'=>$t['telefono'],
                'extension'=>$t['extension'] ?? null,
                'es_principal'=> (bool)($t['es_principal'] ?? false),
                'baja'=>false,
                'updated_at'=>now(),
            ];

            if ($id) {
                $row = PersonaTelefono::where('persona_id',$personaId)->where('id',$id)->first();
                if ($row) { $row->update($payload); if($row->es_principal) $principalId = $row->id; }
            } else {
                $payload['created_at']=now();
                $row = PersonaTelefono::create($payload);
                if($row->es_principal) $principalId = $row->id;
            }
        }
        if ($principalId) {
            PersonaTelefono::where('persona_id',$personaId)->where('id','<>',$principalId)->update(['es_principal'=>false]);
        }

        $principalMail = null;
        foreach ($correos as $c) {
            if (empty($c['correo'])) continue;
            $id = $c['id'] ?? null;

            $payload = [
                'persona_id'=>$personaId,
                'etiqueta'=>$c['etiqueta'] ?? 'principal',
                'correo'=>$c['correo'],
                'es_principal'=> (bool)($c['es_principal'] ?? false),
                'baja'=>false,
                'updated_at'=>now(),
            ];

            if ($id) {
                $row = PersonaCorreo::where('persona_id',$personaId)->where('id',$id)->first();
                if ($row) { $row->update($payload); if($row->es_principal) $principalMail = $row->id; }
            } else {
                $payload['created_at']=now();
                $row = PersonaCorreo::create($payload);
                if($row->es_principal) $principalMail = $row->id;
            }
        }
        if ($principalMail) {
            PersonaCorreo::where('persona_id',$personaId)->where('id','<>',$principalMail)->update(['es_principal'=>false]);
        }

        $principalDir = null;
        foreach ($direcciones as $d) {
            $id = $d['id'] ?? null;

            $payload = [
                'persona_id'=>$personaId,
                'etiqueta'=>$d['etiqueta'] ?? 'principal',
                'calle'=>$d['calle'] ?? null,
                'numero_ext'=>$d['numero_ext'] ?? null,
                'numero_int'=>$d['numero_int'] ?? null,
                'colonia'=>$d['colonia'] ?? null,
                'municipio'=>$d['municipio'] ?? null,
                'estado'=>$d['estado'] ?? null,
                'cp'=>$d['cp'] ?? null,
                'referencias'=>$d['referencias'] ?? null,
                'es_principal'=> (bool)($d['es_principal'] ?? false),
                'baja'=>false,
                'updated_at'=>now(),
            ];

            $hasData = !empty($payload['calle']) || !empty($payload['cp']) || !empty($payload['colonia']);

            if ($id) {
                $row = PersonaDireccion::where('persona_id',$personaId)->where('id',$id)->first();
                if ($row) { $row->update($payload); if($row->es_principal) $principalDir = $row->id; }
            } else if ($hasData) {
                $payload['created_at']=now();
                $row = PersonaDireccion::create($payload);
                if($row->es_principal) $principalDir = $row->id;
            }
        }
        if ($principalDir) {
            PersonaDireccion::where('persona_id',$personaId)->where('id','<>',$principalDir)->update(['es_principal'=>false]);
        }
    }
}