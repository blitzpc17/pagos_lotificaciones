<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Proveedor;
use App\Models\PersonaTelefono;
use App\Models\PersonaCorreo;
use App\Models\PersonaDireccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProveedoresController extends Controller
{
    public function index()
    {
        return view('proveedores.index');
    }

    public function datatable(Request $request)
{
    $draw   = (int) $request->input('draw', 1);
    $start  = (int) $request->input('start', 0);
    $len    = (int) $request->input('length', 10);
    $search = trim((string) $request->input('search.value', ''));

    $base = DB::table('proveedores as pr')
        ->join('personas as pe','pe.id','=','pr.persona_id')
        ->select(
            'pr.id',
            'pr.rfc',
            'pr.razon_social',
            'pr.baja',
            'pr.baja_motivo',
            'pe.nombres',
            'pe.apellido_paterno',
            'pe.apellido_materno'
        );

    if ($search !== '') {
        $base->where(function($q) use ($search){
            $q->where('pe.nombres','ilike',"%{$search}%")
              ->orWhere('pe.apellido_paterno','ilike',"%{$search}%")
              ->orWhere('pe.apellido_materno','ilike',"%{$search}%")
              ->orWhere('pr.rfc','ilike',"%{$search}%")
              ->orWhere('pr.razon_social','ilike',"%{$search}%");
        });
    }

    $recordsTotal = DB::table('proveedores')->count();
    $recordsFiltered = (clone $base)->count();

    $rows = $base->orderByDesc('pr.id')->offset($start)->limit($len)->get();

    $data = $rows->map(function($r){
        $nombre = trim($r->nombres.' '.$r->apellido_paterno.' '.$r->apellido_materno);

        $estatus = $r->baja
            ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
            : '<span class="badge ok">ACTIVO</span>';

        $acciones = '
          <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
            <button class="btn btnEditProveedor" data-id="'.(int)$r->id.'" title="Editar">
              <i class="fa-regular fa-pen-to-square"></i>
            </button>
            <button class="btn btnBajaProveedor" data-id="'.(int)$r->id.'" title="Baja">
              <i class="fa-solid fa-ban"></i>
            </button>
          </div>';

        return [
            'id' => (int)$r->id,
            'nombre' => e($nombre),
            'rfc' => e($r->rfc ?? ''),
            'razon_social' => e($r->razon_social ?? ''),
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
        $pr = Proveedor::with('persona')->findOrFail($id);
        $pid = $pr->persona_id;

        return response()->json([
            'id' => $pr->id,
            'persona_id' => $pid,
            'rfc' => $pr->rfc,
            'razon_social' => $pr->razon_social,
            'notas' => $pr->notas,
            'baja' => (bool)$pr->baja,
            'baja_motivo' => $pr->baja_motivo,
            'persona' => [
                'id' => $pr->persona->id,
                'nombres' => $pr->persona->nombres,
                'apellido_paterno' => $pr->persona->apellido_paterno,
                'apellido_materno' => $pr->persona->apellido_materno,
                'fecha_nacimiento' => optional($pr->persona->fecha_nacimiento)->format('Y-m-d'),
                'notas' => $pr->persona->notas,
            ],
            // IMPORTANTE: incluir bajas para poder reactivar en UI
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
            'razon_social' => 'nullable|string|max:200',
            'notas' => 'nullable|string',

            // opcional: si tu modal manda arrays, también los aceptamos
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

            $provId = DB::table('proveedores')->insertGetId([
                'persona_id' => $personaId,
                'rfc' => $req->rfc,
                'razon_social' => $req->razon_social,
                'notas' => $req->notas,
                'created_at'=>now(),'updated_at'=>now(),
                'baja'=>false
            ]);

            // si tu modal manda contactos en el mismo submit (compat)
            $this->syncContactos($personaId, $req);

            return response()->json(['ok'=>true,'id'=>$provId]);
        });
    }

    public function update(Request $req, $id)
    {
        $prov = Proveedor::with('persona')->findOrFail($id);

        $v = Validator::make($req->all(), [
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date',
            'persona_notas' => 'nullable|string',

            'rfc' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:200',
            'notas' => 'nullable|string',

            'telefonos' => 'nullable|array',
            'correos' => 'nullable|array',
            'direcciones' => 'nullable|array',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req, $prov){
            $prov->persona->update([
                'nombres' => mb_strtoupper(trim($req->nombres),'UTF-8'),
                'apellido_paterno' => mb_strtoupper(trim($req->apellido_paterno),'UTF-8'),
                'apellido_materno' => $req->apellido_materno ? mb_strtoupper(trim($req->apellido_materno),'UTF-8') : null,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'notas' => $req->persona_notas,
            ]);

            $prov->update([
                'rfc' => $req->rfc,
                'razon_social' => $req->razon_social,
                'notas' => $req->notas,
            ]);

            $this->syncContactos($prov->persona_id, $req);

            return response()->json(['ok'=>true]);
        });
    }

    public function baja(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        $prov = Proveedor::findOrFail($id);
        $prov->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
        ]);
        return response()->json(['ok'=>true]);
    }

    private function syncContactos(int $personaId, Request $req): void
    {
        // Esta función es compat: si tu modal manda arrays, los actualiza.
        // Si usas endpoints separados (PersonaContactosController), simplemente no mandes arrays y no pasa nada.
        $telefonos = $req->input('telefonos', []);
        $correos = $req->input('correos', []);
        $direcciones = $req->input('direcciones', []);

        if (!is_array($telefonos)) $telefonos = [];
        if (!is_array($correos)) $correos = [];
        if (!is_array($direcciones)) $direcciones = [];

        // TELEFONOS
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

        // CORREOS
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

        // DIRECCIONES
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