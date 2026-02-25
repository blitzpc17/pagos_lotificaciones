<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Persona;
use App\Models\PersonaTelefono;
use App\Models\PersonaCorreo;
use App\Models\PersonaDireccion;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmpleadosController extends Controller
{
    public function index()
    {
        return view('empleados.index');
    }

    public function datatable(Request $request)
    {
        // DataTables server-side "simple"
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim((string) data_get($request->input('search'), 'value', ''));

        $q = Empleado::query()
            ->with('persona')
            ->where('baja', false);

        if ($search !== '') {
            $q->whereHas('persona', function($p) use ($search){
                $p->whereRaw("upper(nombres) like ?", ['%'.mb_strtoupper($search,'UTF-8').'%'])
                  ->orWhereRaw("upper(apellido_paterno) like ?", ['%'.mb_strtoupper($search,'UTF-8').'%'])
                  ->orWhereRaw("upper(apellido_materno) like ?", ['%'.mb_strtoupper($search,'UTF-8').'%']);
            })->orWhereRaw("upper(numero_empleado) like ?", ['%'.mb_strtoupper($search,'UTF-8').'%']);
        }

        $recordsTotal = Empleado::where('baja', false)->count();
        $recordsFiltered = (clone $q)->count();

        $items = $q->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $items->map(function($e){
            $nombre = trim(($e->persona->nombres ?? '').' '.($e->persona->apellido_paterno ?? '').' '.($e->persona->apellido_materno ?? ''));

            return [
                'id' => $e->id,
                'nombre' => $nombre,
                'puesto' => $e->puesto,
                'numero_empleado' => $e->numero_empleado,
                'estatus' => $e->baja ? '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> BAJA</span>'
                                      : '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> ACTIVO</span>',
                'acciones' => '
                  <div class="dt-actions">
                    <button class="mini primary btnEditEmpleado" data-id="'.$e->id.'"><i class="fa-regular fa-pen-to-square"></i> Editar</button>
                    <button class="mini danger btnBajaEmpleado" data-id="'.$e->id.'"><i class="fa-regular fa-trash-can"></i> Baja</button>
                  </div>
                '
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
        $emp = Empleado::with([
            'persona',
            'persona.telefonos' => fn($q)=>$q->where('baja',false)->orderByDesc('es_principal')->orderBy('id'),
            'persona.correos'   => fn($q)=>$q->where('baja',false)->orderByDesc('es_principal')->orderBy('id'),
            'persona.direcciones' => fn($q)=>$q->where('baja',false)->orderByDesc('es_principal')->orderBy('id'),
            'vendedor'
        ])->findOrFail($id);

        return response()->json([
            'id' => $emp->id,
            'puesto' => $emp->puesto,
            'puesto_detalle' => $emp->puesto_detalle,
            'numero_empleado' => $emp->numero_empleado,
            'observaciones' => $emp->observaciones,
            'persona' => [
                'id' => $emp->persona->id,
                'nombres' => $emp->persona->nombres,
                'apellido_paterno' => $emp->persona->apellido_paterno,
                'apellido_materno' => $emp->persona->apellido_materno,
                'fecha_nacimiento' => $emp->persona->fecha_nacimiento,
                'notas' => $emp->persona->notas,
            ],
            'telefonos' => $emp->persona->telefonos->map(fn($t)=>[
                'id'=>$t->id,'etiqueta'=>$t->etiqueta,'telefono'=>$t->telefono,'extension'=>$t->extension,'es_principal'=>$t->es_principal
            ]),
            'correos' => $emp->persona->correos->map(fn($c)=>[
                'id'=>$c->id,'etiqueta'=>$c->etiqueta,'correo'=>$c->correo,'es_principal'=>$c->es_principal
            ]),
            'direcciones' => $emp->persona->direcciones->map(fn($d)=>[
                'id'=>$d->id,'etiqueta'=>$d->etiqueta,'calle'=>$d->calle,'numero_ext'=>$d->numero_ext,'numero_int'=>$d->numero_int,
                'colonia'=>$d->colonia,'municipio'=>$d->municipio,'estado'=>$d->estado,'cp'=>$d->cp,'referencias'=>$d->referencias,
                'es_principal'=>$d->es_principal
            ]),
            'vendedor' => $emp->vendedor ? [
                'id' => $emp->vendedor->id,
                'comision_default' => $emp->vendedor->comision_default,
                'clave' => $emp->vendedor->clave,
            ] : null
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEmpleado($request, false);

        return DB::transaction(function() use ($request, $data){

            // Persona
            $persona = Persona::create([
                'nombres' => mb_strtoupper($data['nombres'],'UTF-8'),
                'apellido_paterno' => mb_strtoupper($data['apellido_paterno'],'UTF-8'),
                'apellido_materno' => $data['apellido_materno'] ? mb_strtoupper($data['apellido_materno'],'UTF-8') : null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'notas' => $data['notas'] ? mb_strtoupper($data['notas'],'UTF-8') : null,
                'baja' => false
            ]);

            // Empleado (numero_empleado se autogenera en tu servicio; aquí lo dejamos como "YA CALCULADO")
            // Si tú ya tienes ConsecutivosService, úsalo aquí.
            $numeroEmpleado = $this->nextNumeroEmpleado();

            $emp = Empleado::create([
                'persona_id' => $persona->id,
                'puesto' => $data['puesto'],
                'puesto_detalle' => $data['puesto_detalle'] ? mb_strtoupper($data['puesto_detalle'],'UTF-8') : null,
                'numero_empleado' => $numeroEmpleado,
                'observaciones' => $data['observaciones'] ? mb_strtoupper($data['observaciones'],'UTF-8') : null,
                'baja' => false
            ]);

            // contactos + principal único
            $this->syncContactosCreate($persona->id, $request);

            // si puesto es VENTAS => complemento vendedor (si aplica tu regla)
            if ($data['puesto'] === 'VENTAS') {
                // clave 4 chars: 0001, 0002...
                $clave = $this->nextClaveVendedor();
                Vendedor::create([
                    'empleado_id' => $emp->id,
                    'comision_default' => (float)($data['comision_default'] ?? 0),
                    'clave' => $clave,
                    'baja' => false
                ]);
            }

            return response()->json(['ok'=>true,'id'=>$emp->id], 201);
        });
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateEmpleado($request, true);

        return DB::transaction(function() use ($request, $id, $data){

            $emp = Empleado::with(['persona','vendedor'])->findOrFail($id);
            if ($emp->baja) throw ValidationException::withMessages(['general'=>'Empleado está dado de baja']);

            // Persona
            $emp->persona->update([
                'nombres' => mb_strtoupper($data['nombres'],'UTF-8'),
                'apellido_paterno' => mb_strtoupper($data['apellido_paterno'],'UTF-8'),
                'apellido_materno' => $data['apellido_materno'] ? mb_strtoupper($data['apellido_materno'],'UTF-8') : null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'notas' => $data['notas'] ? mb_strtoupper($data['notas'],'UTF-8') : null,
            ]);

            // Empleado
            $emp->update([
                'puesto' => $data['puesto'],
                'puesto_detalle' => $data['puesto_detalle'] ? mb_strtoupper($data['puesto_detalle'],'UTF-8') : null,
                // numero_empleado NO editable
                'observaciones' => $data['observaciones'] ? mb_strtoupper($data['observaciones'],'UTF-8') : null,
            ]);

            // contactos + principal único
            $this->syncContactosUpdate($emp->persona->id, $request);

            // complemento vendedor si puesto VENTAS
            if ($data['puesto'] === 'VENTAS') {
                if (!$emp->vendedor) {
                    $clave = $this->nextClaveVendedor();
                    Vendedor::create([
                        'empleado_id' => $emp->id,
                        'comision_default' => (float)($data['comision_default'] ?? 0),
                        'clave' => $clave,
                        'baja' => false
                    ]);
                } else {
                    $emp->vendedor->update([
                        'comision_default' => (float)($data['comision_default'] ?? $emp->vendedor->comision_default),
                    ]);
                }
            } else {
                // si ya no es ventas, puedes dar de baja su complemento vendedor
                if ($emp->vendedor && !$emp->vendedor->baja) {
                    $emp->vendedor->update(['baja'=>true,'baja_at'=>now()]);
                }
            }

            return response()->json(['ok'=>true]);
        });
    }

    public function baja($id)
    {
        $emp = Empleado::findOrFail($id);
        if ($emp->baja) return response()->json(['ok'=>true]);

        $emp->update(['baja'=>true,'baja_at'=>now()]);
        return response()->json(['ok'=>true]);
    }

    // =============================
    // VALIDATION
    // =============================
    private function validateEmpleado(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date',
            'notas' => 'nullable|string',

            'puesto' => 'required|string',
            'puesto_detalle' => 'nullable|string|max:120',
            'observaciones' => 'nullable|string',

            // vendedor complementario (si puesto=VENTAS)
            'comision_default' => 'nullable|numeric|min:0',

            // contactos
            'telefonos' => 'nullable|array',
            'telefonos.*.id' => 'nullable|integer',
            'telefonos.*.telefono' => 'required_with:telefonos|string|max:40',
            'telefonos.*.etiqueta' => 'nullable|string|max:40',
            'telefonos.*.extension' => 'nullable|string|max:12',
            // ✅ aceptar 0/1 true/false
            'telefonos.*.es_principal' => 'nullable|in:0,1,true,false,"0","1"',

            'correos' => 'nullable|array',
            'correos.*.id' => 'nullable|integer',
            'correos.*.correo' => 'required_with:correos|email|max:160',
            'correos.*.etiqueta' => 'nullable|string|max:40',
            'correos.*.es_principal' => 'nullable|in:0,1,true,false,"0","1"',

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
            'direcciones.*.es_principal' => 'nullable|in:0,1,true,false,"0","1"',
        ], [
            'puesto.required' => 'El puesto es requerido.',
        ]);
    }

    // =============================
    // PRINCIPAL HELPERS
    // =============================
    private function setPrincipalTelefono(int $personaId, int $telefonoId): void
    {
        PersonaTelefono::where('persona_id', $personaId)->update(['es_principal' => false]);
        PersonaTelefono::where('id', $telefonoId)->update(['es_principal' => true]);
    }

    private function setPrincipalCorreo(int $personaId, int $correoId): void
    {
        PersonaCorreo::where('persona_id', $personaId)->update(['es_principal' => false]);
        PersonaCorreo::where('id', $correoId)->update(['es_principal' => true]);
    }

    private function setPrincipalDireccion(int $personaId, int $dirId): void
    {
        PersonaDireccion::where('persona_id', $personaId)->update(['es_principal' => false]);
        PersonaDireccion::where('id', $dirId)->update(['es_principal' => true]);
    }

    private function syncContactosCreate(int $personaId, Request $req): void
    {
        // TELEFONOS
        foreach ($req->input('telefonos', []) as $t) {
            $tel = PersonaTelefono::create([
                'persona_id' => $personaId,
                'etiqueta' => $t['etiqueta'] ?? 'principal',
                'telefono' => mb_strtoupper($t['telefono'] ?? '', 'UTF-8'),
                'extension' => $t['extension'] ?? null,
                'es_principal' => false,
                'baja' => false,
            ]);

            $isPrincipal = filter_var($t['es_principal'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isPrincipal) $this->setPrincipalTelefono($personaId, $tel->id);
        }

        // CORREOS
        foreach ($req->input('correos', []) as $c) {
            $mail = PersonaCorreo::create([
                'persona_id' => $personaId,
                'etiqueta' => $c['etiqueta'] ?? 'principal',
                'correo' => $c['correo'] ?? '',
                'es_principal' => false,
                'baja' => false,
            ]);

            $isPrincipal = filter_var($c['es_principal'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isPrincipal) $this->setPrincipalCorreo($personaId, $mail->id);
        }

        // DIRECCIONES
        foreach ($req->input('direcciones', []) as $d) {
            $dir = PersonaDireccion::create([
                'persona_id' => $personaId,
                'etiqueta' => $d['etiqueta'] ?? 'principal',
                'calle' => $d['calle'] ? mb_strtoupper($d['calle'], 'UTF-8') : null,
                'numero_ext' => $d['numero_ext'] ?? null,
                'numero_int' => $d['numero_int'] ?? null,
                'colonia' => $d['colonia'] ? mb_strtoupper($d['colonia'], 'UTF-8') : null,
                'municipio' => $d['municipio'] ? mb_strtoupper($d['municipio'], 'UTF-8') : null,
                'estado' => $d['estado'] ? mb_strtoupper($d['estado'], 'UTF-8') : null,
                'cp' => $d['cp'] ?? null,
                'referencias' => $d['referencias'] ? mb_strtoupper($d['referencias'], 'UTF-8') : null,
                'es_principal' => false,
                'baja' => false,
            ]);

            $isPrincipal = filter_var($d['es_principal'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isPrincipal) $this->setPrincipalDireccion($personaId, $dir->id);
        }

        // fallback: si nadie viene principal, marca el primero
        if (PersonaTelefono::where('persona_id',$personaId)->where('baja',false)->exists()
            && !PersonaTelefono::where('persona_id',$personaId)->where('es_principal',true)->exists()) {
            $first = PersonaTelefono::where('persona_id',$personaId)->where('baja',false)->orderBy('id')->first();
            if($first) $this->setPrincipalTelefono($personaId, $first->id);
        }

        if (PersonaCorreo::where('persona_id',$personaId)->where('baja',false)->exists()
            && !PersonaCorreo::where('persona_id',$personaId)->where('es_principal',true)->exists()) {
            $first = PersonaCorreo::where('persona_id',$personaId)->where('baja',false)->orderBy('id')->first();
            if($first) $this->setPrincipalCorreo($personaId, $first->id);
        }

        if (PersonaDireccion::where('persona_id',$personaId)->where('baja',false)->exists()
            && !PersonaDireccion::where('persona_id',$personaId)->where('es_principal',true)->exists()) {
            $first = PersonaDireccion::where('persona_id',$personaId)->where('baja',false)->orderBy('id')->first();
            if($first) $this->setPrincipalDireccion($personaId, $first->id);
        }
    }

    private function syncContactosUpdate(int $personaId, Request $req): void
    {
        // TELEFONOS: upsert sencillo (si viene id => update, si no => create)
        foreach ($req->input('telefonos', []) as $t) {
            $id = $t['id'] ?? null;
            $isPrincipal = filter_var($t['es_principal'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($id) {
                PersonaTelefono::where('id',$id)->where('persona_id',$personaId)->update([
                    'etiqueta' => $t['etiqueta'] ?? 'principal',
                    'telefono' => mb_strtoupper($t['telefono'] ?? '', 'UTF-8'),
                    'extension' => $t['extension'] ?? null,
                ]);
                if ($isPrincipal) $this->setPrincipalTelefono($personaId, (int)$id);
            } else {
                $tel = PersonaTelefono::create([
                    'persona_id' => $personaId,
                    'etiqueta' => $t['etiqueta'] ?? 'principal',
                    'telefono' => mb_strtoupper($t['telefono'] ?? '', 'UTF-8'),
                    'extension' => $t['extension'] ?? null,
                    'es_principal' => false,
                    'baja' => false,
                ]);
                if ($isPrincipal) $this->setPrincipalTelefono($personaId, $tel->id);
            }
        }

        // CORREOS
        foreach ($req->input('correos', []) as $c) {
            $id = $c['id'] ?? null;
            $isPrincipal = filter_var($c['es_principal'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($id) {
                PersonaCorreo::where('id',$id)->where('persona_id',$personaId)->update([
                    'etiqueta' => $c['etiqueta'] ?? 'principal',
                    'correo' => $c['correo'] ?? '',
                ]);
                if ($isPrincipal) $this->setPrincipalCorreo($personaId, (int)$id);
            } else {
                $mail = PersonaCorreo::create([
                    'persona_id' => $personaId,
                    'etiqueta' => $c['etiqueta'] ?? 'principal',
                    'correo' => $c['correo'] ?? '',
                    'es_principal' => false,
                    'baja' => false,
                ]);
                if ($isPrincipal) $this->setPrincipalCorreo($personaId, $mail->id);
            }
        }

        // DIRECCIONES
        foreach ($req->input('direcciones', []) as $d) {
            $id = $d['id'] ?? null;
            $isPrincipal = filter_var($d['es_principal'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $payload = [
                'etiqueta' => $d['etiqueta'] ?? 'principal',
                'calle' => $d['calle'] ? mb_strtoupper($d['calle'], 'UTF-8') : null,
                'numero_ext' => $d['numero_ext'] ?? null,
                'numero_int' => $d['numero_int'] ?? null,
                'colonia' => $d['colonia'] ? mb_strtoupper($d['colonia'], 'UTF-8') : null,
                'municipio' => $d['municipio'] ? mb_strtoupper($d['municipio'], 'UTF-8') : null,
                'estado' => $d['estado'] ? mb_strtoupper($d['estado'], 'UTF-8') : null,
                'cp' => $d['cp'] ?? null,
                'referencias' => $d['referencias'] ? mb_strtoupper($d['referencias'], 'UTF-8') : null,
            ];

            if ($id) {
                PersonaDireccion::where('id',$id)->where('persona_id',$personaId)->update($payload);
                if ($isPrincipal) $this->setPrincipalDireccion($personaId, (int)$id);
            } else {
                $dir = PersonaDireccion::create(array_merge($payload, [
                    'persona_id' => $personaId,
                    'es_principal' => false,
                    'baja' => false
                ]));
                if ($isPrincipal) $this->setPrincipalDireccion($personaId, $dir->id);
            }
        }

        // fallback principal si quedó ninguno
        if (PersonaTelefono::where('persona_id',$personaId)->where('baja',false)->exists()
            && !PersonaTelefono::where('persona_id',$personaId)->where('es_principal',true)->exists()) {
            $first = PersonaTelefono::where('persona_id',$personaId)->where('baja',false)->orderBy('id')->first();
            if($first) $this->setPrincipalTelefono($personaId, $first->id);
        }
        if (PersonaCorreo::where('persona_id',$personaId)->where('baja',false)->exists()
            && !PersonaCorreo::where('persona_id',$personaId)->where('es_principal',true)->exists()) {
            $first = PersonaCorreo::where('persona_id',$personaId)->where('baja',false)->orderBy('id')->first();
            if($first) $this->setPrincipalCorreo($personaId, $first->id);
        }
        if (PersonaDireccion::where('persona_id',$personaId)->where('baja',false)->exists()
            && !PersonaDireccion::where('persona_id',$personaId)->where('es_principal',true)->exists()) {
            $first = PersonaDireccion::where('persona_id',$personaId)->where('baja',false)->orderBy('id')->first();
            if($first) $this->setPrincipalDireccion($personaId, $first->id);
        }
    }

    // =============================
    // CONSECUTIVOS (stub)
    // =============================
    private function nextNumeroEmpleado(): string
    {
        // ✅ Aquí en tu proyecto real: ConsecutivosService->next('empleados')
        // Por ahora: ejemplo simple
        $last = Empleado::orderByDesc('id')->value('numero_empleado');
        $n = 1;
        if ($last && preg_match('/(\d+)/', $last, $m)) $n = ((int)$m[1]) + 1;
        return str_pad((string)$n, 6, '0', STR_PAD_LEFT); // 000001...
    }

    private function nextClaveVendedor(): string
    {
        $last = Vendedor::orderByDesc('id')->value('clave');
        $n = 1;
        if ($last && preg_match('/(\d+)/', $last, $m)) $n = ((int)$m[1]) + 1;
        return str_pad((string)$n, 4, '0', STR_PAD_LEFT); // 0001...
    }
}
