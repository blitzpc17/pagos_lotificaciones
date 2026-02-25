<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Persona;
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
        $draw   = intval($request->input('draw', 1));
        $start  = intval($request->input('start', 0));
        $len    = intval($request->input('length', 10));
        $search = trim($request->input('search.value', ''));

        $orderColIdx = intval($request->input('order.0.column', 0));
        $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $cols = [
            'pr.id',
            'p.nombres',
            'pr.rfc',
            'pr.razon_social',
            'pr.baja',
        ];
        $orderBy = $cols[$orderColIdx] ?? 'pr.id';

        $base = DB::table('proveedores as pr')
            ->join('personas as p','p.id','=','pr.persona_id')
            ->select(
                'pr.id','pr.rfc','pr.razon_social','pr.baja',
                'p.nombres','p.apellido_paterno','p.apellido_materno'
            );

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('p.nombres','ilike',"%{$search}%")
                  ->orWhere('p.apellido_paterno','ilike',"%{$search}%")
                  ->orWhere('p.apellido_materno','ilike',"%{$search}%")
                  ->orWhere('pr.rfc','ilike',"%{$search}%")
                  ->orWhere('pr.razon_social','ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('proveedores')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderByRaw("$orderBy $orderDir")
            ->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $nombre = trim(($r->nombres ?? '').' '.($r->apellido_paterno ?? '').' '.($r->apellido_materno ?? ''));
            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span>'
                : '<span class="badge ok">ACTIVO</span>';

            $acciones = '
              <div class="dt-actions">
                <button class="btn btnEditProveedor" data-id="'.$r->id.'"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btnBajaProveedor" data-id="'.$r->id.'"><i class="fa-solid fa-ban"></i></button>
              </div>';

            return [
                'id' => $r->id,
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
            'rfc' => $pr->rfc,
            'razon_social' => $pr->razon_social,
            'notas' => $pr->notas,
            'baja' => (bool)$pr->baja,
            'persona' => [
                'id' => $pr->persona->id,
                'nombres' => $pr->persona->nombres,
                'apellido_paterno' => $pr->persona->apellido_paterno,
                'apellido_materno' => $pr->persona->apellido_materno,
                'fecha_nacimiento' => optional($pr->persona->fecha_nacimiento)->format('Y-m-d'),
                'notas' => $pr->persona->notas,
            ],
            'telefonos' => PersonaTelefono::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
            'correos' => PersonaCorreo::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
            'direcciones' => PersonaDireccion::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
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

            'telefonos' => 'nullable|array',
            'telefonos.*.telefono' => 'required_with:telefonos|string|max:40',
            'telefonos.*.etiqueta' => 'nullable|string|max:40',
            'telefonos.*.extension' => 'nullable|string|max:12',
            'telefonos.*.es_principal' => 'nullable|boolean',

            'correos' => 'nullable|array',
            'correos.*.correo' => 'required_with:correos|email|max:160',
            'correos.*.etiqueta' => 'nullable|string|max:40',
            'correos.*.es_principal' => 'nullable|boolean',

            'direcciones' => 'nullable|array',
            'direcciones.*.etiqueta' => 'nullable|string|max:40',
            'direcciones.*.calle' => 'nullable|string|max:160',
            'direcciones.*.numero_ext' => 'nullable|string|max:30',
            'direcciones.*.numero_int' => 'nullable|string|max:30',
            'direcciones.*.colonia' => 'nullable|string|max:120',
            'direcciones.*.municipio' => 'nullable|string|max:120',
            'direcciones.*.estado' => 'nullable|string|max:120',
            'direcciones.*.cp' => 'nullable|string|max:12',
            'direcciones.*.referencias' => 'nullable|string',
            'direcciones.*.es_principal' => 'nullable|boolean',
        ]);

        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function () use ($req) {

            $persona = Persona::create([
                'nombres' => $req->nombres,
                'apellido_paterno' => $req->apellido_paterno,
                'apellido_materno' => $req->apellido_materno,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'notas' => $req->persona_notas,
                'baja' => false,
            ]);

            $prov = Proveedor::create([
                'persona_id' => $persona->id,
                'rfc' => $req->rfc,
                'razon_social' => $req->razon_social,
                'notas' => $req->notas,
                'baja' => false,
            ]);

            $this->syncContactos($persona->id, $req);

            return response()->json(['ok'=>true,'id'=>$prov->id]);
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
            'telefonos.*.id' => 'nullable|integer',
            'telefonos.*.telefono' => 'required_with:telefonos|string|max:40',
            'telefonos.*.etiqueta' => 'nullable|string|max:40',
            'telefonos.*.extension' => 'nullable|string|max:12',
            'telefonos.*.es_principal' => 'nullable|boolean',

            'correos' => 'nullable|array',
            'correos.*.id' => 'nullable|integer',
            'correos.*.correo' => 'required_with:correos|email|max:160',
            'correos.*.etiqueta' => 'nullable|string|max:40',
            'correos.*.es_principal' => 'nullable|boolean',

            'direcciones' => 'nullable|array',
            'direcciones.*.id' => 'nullable|integer',
            'direcciones.*.etiqueta' => 'nullable|string|max:40',
            'direcciones.*.calle' => 'nullable|string|max:160',
            'direcciones.*.numero_ext' => 'nullable|string|max:30',
            'direcciones.*.numero_int' => 'nullable|string|max:30',
            'direcciones.*.colonia' => 'nullable|string|max:120',
            'direcciones.*.municipio' => 'nullable|string|max:120',
            'direcciones.*.estado' => 'nullable|string|max:120',
            'direcciones.*.cp' => 'nullable|string|max:12',
            'direcciones.*.referencias' => 'nullable|string',
            'direcciones.*.es_principal' => 'nullable|boolean',
        ]);

        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function () use ($req, $prov) {

            $prov->persona->update([
                'nombres' => $req->nombres,
                'apellido_paterno' => $req->apellido_paterno,
                'apellido_materno' => $req->apellido_materno,
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

    public function baja($id)
    {
        $prov = Proveedor::findOrFail($id);
        $prov->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => 'Baja desde módulo proveedores'
        ]);
        return response()->json(['ok'=>true]);
    }

    public function contactos($id)
    {
        $prov = Proveedor::findOrFail($id);
        $pid = $prov->persona_id;

        return response()->json([
            'persona_id' => $pid,
            'telefonos' => PersonaTelefono::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
            'correos' => PersonaCorreo::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
            'direcciones' => PersonaDireccion::where('persona_id',$pid)->where('baja',false)->orderBy('es_principal','desc')->get(),
        ]);
    }

    public function addTelefono(Request $req, $id)
    {
        $prov = Proveedor::findOrFail($id);

        $v = Validator::make($req->all(), [
            'telefono' => 'required|string|max:40',
            'etiqueta' => 'nullable|string|max:40',
            'extension' => 'nullable|string|max:12',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $tel = PersonaTelefono::create([
            'persona_id' => $prov->persona_id,
            'telefono' => $req->telefono,
            'etiqueta' => $req->etiqueta ?: 'principal',
            'extension' => $req->extension,
            'es_principal' => (bool)$req->es_principal,
            'baja' => false,
        ]);

        if ($tel->es_principal) {
            PersonaTelefono::where('persona_id',$prov->persona_id)
                ->where('id','<>',$tel->id)
                ->update(['es_principal'=>false]);
        }

        return response()->json(['ok'=>true]);
    }

    public function addCorreo(Request $req, $id)
    {
        $prov = Proveedor::findOrFail($id);

        $v = Validator::make($req->all(), [
            'correo' => 'required|email|max:160',
            'etiqueta' => 'nullable|string|max:40',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $mail = PersonaCorreo::create([
            'persona_id' => $prov->persona_id,
            'correo' => $req->correo,
            'etiqueta' => $req->etiqueta ?: 'principal',
            'es_principal' => (bool)$req->es_principal,
            'baja' => false,
        ]);

        if ($mail->es_principal) {
            PersonaCorreo::where('persona_id',$prov->persona_id)
                ->where('id','<>',$mail->id)
                ->update(['es_principal'=>false]);
        }

        return response()->json(['ok'=>true]);
    }

    public function addDireccion(Request $req, $id)
    {
        $prov = Proveedor::findOrFail($id);

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

        $dir = PersonaDireccion::create([
            'persona_id' => $prov->persona_id,
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

        if ($dir->es_principal) {
            PersonaDireccion::where('persona_id',$prov->persona_id)
                ->where('id','<>',$dir->id)
                ->update(['es_principal'=>false]);
        }

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

        $m = $map[$tipo]::findOrFail($cid);
        $m->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => 'Baja desde UI',
        ]);

        return response()->json(['ok'=>true]);
    }

    /**
     * Sincroniza (create/update) contactos enviados por JSON.
     * - respeta principal único por tipo (tel/mail/dir).
     */
    private function syncContactos(int $personaId, Request $req): void
    {
        $telefonos = $req->input('telefonos', []);
        $correos = $req->input('correos', []);
        $direcciones = $req->input('direcciones', []);

        // TEL
        $principalTelId = null;
        foreach ($telefonos as $t) {
            $id = $t['id'] ?? null;
            $payload = [
                'persona_id' => $personaId,
                'telefono' => $t['telefono'] ?? '',
                'etiqueta' => $t['etiqueta'] ?? 'principal',
                'extension' => $t['extension'] ?? null,
                'es_principal' => (bool)($t['es_principal'] ?? false),
                'baja' => false,
            ];

            if ($id) {
                $row = PersonaTelefono::where('persona_id',$personaId)->where('id',$id)->first();
                if ($row) {
                    $row->update($payload);
                    if ($row->es_principal) $principalTelId = $row->id;
                }
            } else {
                $row = PersonaTelefono::create($payload);
                if ($row->es_principal) $principalTelId = $row->id;
            }
        }
        if ($principalTelId) {
            PersonaTelefono::where('persona_id',$personaId)->where('id','<>',$principalTelId)->update(['es_principal'=>false]);
        } elseif (!empty($telefonos)) {
            $first = PersonaTelefono::where('persona_id',$personaId)->where('baja',false)->orderByDesc('id')->first();
            if ($first) {
                $first->update(['es_principal'=>true]);
                PersonaTelefono::where('persona_id',$personaId)->where('id','<>',$first->id)->update(['es_principal'=>false]);
            }
        }

        // MAIL
        $principalMailId = null;
        foreach ($correos as $c) {
            $id = $c['id'] ?? null;
            $payload = [
                'persona_id' => $personaId,
                'correo' => $c['correo'] ?? '',
                'etiqueta' => $c['etiqueta'] ?? 'principal',
                'es_principal' => (bool)($c['es_principal'] ?? false),
                'baja' => false,
            ];

            if ($id) {
                $row = PersonaCorreo::where('persona_id',$personaId)->where('id',$id)->first();
                if ($row) {
                    $row->update($payload);
                    if ($row->es_principal) $principalMailId = $row->id;
                }
            } else {
                $row = PersonaCorreo::create($payload);
                if ($row->es_principal) $principalMailId = $row->id;
            }
        }
        if ($principalMailId) {
            PersonaCorreo::where('persona_id',$personaId)->where('id','<>',$principalMailId)->update(['es_principal'=>false]);
        } elseif (!empty($correos)) {
            $first = PersonaCorreo::where('persona_id',$personaId)->where('baja',false)->orderByDesc('id')->first();
            if ($first) {
                $first->update(['es_principal'=>true]);
                PersonaCorreo::where('persona_id',$personaId)->where('id','<>',$first->id)->update(['es_principal'=>false]);
            }
        }

        // DIR
        $principalDirId = null;
        foreach ($direcciones as $d) {
            $id = $d['id'] ?? null;
            $payload = [
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
            ];

            if ($id) {
                $row = PersonaDireccion::where('persona_id',$personaId)->where('id',$id)->first();
                if ($row) {
                    $row->update($payload);
                    if ($row->es_principal) $principalDirId = $row->id;
                }
            } else {
                $row = PersonaDireccion::create($payload);
                if ($row->es_principal) $principalDirId = $row->id;
            }
        }
        if ($principalDirId) {
            PersonaDireccion::where('persona_id',$personaId)->where('id','<>',$principalDirId)->update(['es_principal'=>false]);
        } elseif (!empty($direcciones)) {
            $first = PersonaDireccion::where('persona_id',$personaId)->where('baja',false)->orderByDesc('id')->first();
            if ($first) {
                $first->update(['es_principal'=>true]);
                PersonaDireccion::where('persona_id',$personaId)->where('id','<>',$first->id)->update(['es_principal'=>false]);
            }
        }
    }
}