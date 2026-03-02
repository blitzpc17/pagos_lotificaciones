<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Persona;
use App\Models\PersonaTelefono;
use App\Models\PersonaCorreo;
use App\Models\PersonaDireccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\FolioService;

class EmpleadosController extends Controller
{
    public function index()
    {
        return view('empleados.index');
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $base = DB::table('empleados as em')
            ->join('personas as pe','pe.id','=','em.persona_id')
            ->select(
                'em.id',
                'em.puesto',
                'em.numero_empleado',
                'em.baja',
                'em.baja_motivo',
                'pe.nombres',
                'pe.apellido_paterno',
                'pe.apellido_materno'
            );

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('pe.nombres','ilike',"%{$search}%")
                ->orWhere('pe.apellido_paterno','ilike',"%{$search}%")
                ->orWhere('pe.apellido_materno','ilike',"%{$search}%")
                ->orWhere('em.numero_empleado','ilike',"%{$search}%")
                ->orWhere('em.puesto','ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('empleados')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderByDesc('em.id')->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $nombre = trim($r->nombres.' '.$r->apellido_paterno.' '.$r->apellido_materno);

            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($r->baja_motivo ?? '—').'</div>'
                : '<span class="badge ok">ACTIVO</span>';

            $acciones = '
            <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnEditEmpleado" data-id="'.(int)$r->id.'" title="Editar">
                <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="btn btnBajaEmpleado" data-id="'.(int)$r->id.'" title="Baja">
                <i class="fa-solid fa-ban"></i>
                </button>
            </div>';

            return [
                'id' => (int)$r->id,
                'nombre' => e($nombre),
                'puesto' => e($r->puesto ?? ''),
                'numero_empleado' => e($r->numero_empleado ?? ''),
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
        $e = Empleado::with('persona')->findOrFail($id);
        $pid = $e->persona_id;

        return response()->json([
            'id' => $e->id,
            'persona_id' => $pid,
            'puesto' => $e->puesto,
            'puesto_detalle' => $e->puesto_detalle,
            'numero_empleado' => $e->numero_empleado,
            'observaciones' => $e->observaciones,
            'baja' => (bool)$e->baja,
            'baja_motivo' => $e->baja_motivo,
            'persona' => [
                'id' => $e->persona->id,
                'nombres' => $e->persona->nombres,
                'apellido_paterno' => $e->persona->apellido_paterno,
                'apellido_materno' => $e->persona->apellido_materno,
                'fecha_nacimiento' => optional($e->persona->fecha_nacimiento)->format('Y-m-d'),
                'notas' => $e->persona->notas,
            ],
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

            'puesto' => 'required|string',
            'puesto_detalle' => 'nullable|string|max:120',
            'numero_empleado' => 'nullable|string|max:40',
            'observaciones' => 'nullable|string',
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

            $numeroEmpleado = trim((string)($req->numero_empleado ?? ''));

            // ✅ Si viene vacío: genera consecutivo (0001, 0002...) usando variables_globales/folios
            if ($numeroEmpleado === '') {
                $numeroEmpleado = FolioService::next('empleados'); // si prefijo vacío => "0001"
            } else {
                // si viene numérico (ej "1") lo vuelvo "0001" conforme config
                if (ctype_digit($numeroEmpleado)) {
                    $numeroEmpleado = FolioService::format('empleados', (int)$numeroEmpleado);
                }
            }

            // evita duplicado (por si el usuario fuerza algo)
            $exists = DB::table('empleados')->where('numero_empleado', $numeroEmpleado)->exists();
            if ($exists) {
                // fallback: si chocó, genera uno nuevo
                $numeroEmpleado = FolioService::next('empleados');
            }

            $id = DB::table('empleados')->insertGetId([
                'persona_id' => $personaId,
                'puesto' => $req->puesto,
                'puesto_detalle' => $req->puesto_detalle,
                'numero_empleado' => $numeroEmpleado,
                'observaciones' => $req->observaciones,
                'created_at'=>now(),'updated_at'=>now(),
                'baja'=>false
            ]);

            return response()->json(['ok'=>true,'id'=>$id,'numero_empleado'=>$numeroEmpleado]);
        });
    }

    public function update(Request $req, $id)
    {
        $e = Empleado::with('persona')->findOrFail($id);

        $v = Validator::make($req->all(), [
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date',
            'persona_notas' => 'nullable|string',

            'puesto' => 'required|string',
            'puesto_detalle' => 'nullable|string|max:120',
            'numero_empleado' => 'nullable|string|max:40',
            'observaciones' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req, $e){

            $numeroEmpleado = trim((string)($req->numero_empleado ?? ''));

            // Si lo dejan vacío en edición, NO lo borro, lo mantengo.
            if ($numeroEmpleado === '') {
                $numeroEmpleado = $e->numero_empleado;
            } else {
                if (ctype_digit($numeroEmpleado)) {
                    $numeroEmpleado = FolioService::format('empleados', (int)$numeroEmpleado);
                }
            }

            // evita duplicado con otro empleado
            $dup = DB::table('empleados')
                ->where('numero_empleado', $numeroEmpleado)
                ->where('id','!=',$e->id)
                ->exists();

            if ($dup) {
                return response()->json(['message'=>'El número de empleado ya existe.'], 422);
            }

            $e->persona->update([
                'nombres' => mb_strtoupper(trim($req->nombres),'UTF-8'),
                'apellido_paterno' => mb_strtoupper(trim($req->apellido_paterno),'UTF-8'),
                'apellido_materno' => $req->apellido_materno ? mb_strtoupper(trim($req->apellido_materno),'UTF-8') : null,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'notas' => $req->persona_notas,
            ]);

            $e->update([
                'puesto' => $req->puesto,
                'puesto_detalle' => $req->puesto_detalle,
                'numero_empleado' => $numeroEmpleado,
                'observaciones' => $req->observaciones,
            ]);

            return response()->json(['ok'=>true,'numero_empleado'=>$numeroEmpleado]);
        });
    }

    public function baja(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        $e = Empleado::findOrFail($id);
        $e->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
        ]);

        return response()->json(['ok'=>true]);
    }
}