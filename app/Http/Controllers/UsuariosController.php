<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuariosController extends Controller
{
    public function index()
    {
        $roles = Rol::where('baja',false)->where('is_active',true)->orderBy('nombre')->get();
        return view('usuarios.index', compact('roles'));
    }

    public function datatable()
    {
        $rows = DB::table('usuarios as u')
            ->join('personas as p','p.id','=','u.persona_id')
            ->leftJoin('empleados as e','e.persona_id','=','u.persona_id')
            ->leftJoin('roles as r','r.id','=','u.role_id')
            ->select([
                'u.id','u.username','u.email','u.is_active','u.baja','u.baja_motivo',
                'p.nombres','p.apellido_paterno','p.apellido_materno',
                'e.numero_empleado','e.puesto',
                'r.nombre as rol'
            ])
            ->orderByDesc('u.id')
            ->get()
            ->map(function($u){
                $nombre = trim(($u->nombres ?? '').' '.($u->apellido_paterno ?? '').' '.($u->apellido_materno ?? ''));
                $empleadoLabel = $u->numero_empleado ? ($u->numero_empleado.' · '.$nombre) : $nombre;

                $estatus = $u->baja
                    ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($u->baja_motivo ?? '—').'</div>'
                    : ($u->is_active ? '<span class="badge ok">ACTIVO</span>' : '<span class="badge">INACTIVO</span>');

                return [
                    'id' => $u->id,
                    'username' => e($u->username),
                    'email' => e($u->email ?? ''),
                    'empleado' => e($empleadoLabel).($u->baja ? ' <span class="badge danger" style="margin-left:8px;">BAJA</span>' : ''),
                    'rol' => e($u->rol ?? ''),
                    'estatus' => $estatus,
                    '_is_baja' => (bool)$u->baja,
                ];
            });

        return response()->json(['data'=>$rows]);
    }

    public function empleadosDisponibles()
    {
        // Empleados sin usuario activo (match por persona_id)
        $emps = Empleado::query()
            ->with('persona')
            ->where('baja',false)
            ->whereDoesntHave('usuario', fn($q)=> $q->where('baja',false))
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(function($e){
                $p = $e->persona;
                return [
                    'id' => $e->id,
                    'persona_id' => $e->persona_id,
                    'numero_empleado' => $e->numero_empleado,
                    'nombre' => trim($p->nombres.' '.$p->apellido_paterno.' '.$p->apellido_materno),
                    'puesto' => $e->puesto,
                ];
            });

        return response()->json(['data'=>$emps]);
    }

    public function show($id)
    {
        $u = Usuario::with(['persona','role'])->findOrFail($id);
        $p = $u->persona;

        // empleado opcional ligado por persona
        $emp = Empleado::where('persona_id',$u->persona_id)->first();
        $empleadoLabel = $emp
            ? ($emp->numero_empleado.' · '.trim($p->nombres.' '.$p->apellido_paterno.' '.$p->apellido_materno))
            : trim($p->nombres.' '.$p->apellido_paterno.' '.$p->apellido_materno);

        return response()->json([
            'id' => $u->id,
            'persona_id' => $u->persona_id,
            'empleado_id' => $emp?->id,
            'email' => $u->email,
            'username' => $u->username,
            'role_id' => $u->role_id,
            'is_active' => (bool)$u->is_active,
            'empleado_label' => $empleadoLabel,
        ]);
    }

    public function store(Request $req)
    {
        // UI elige empleado -> sacamos persona_id
        $v = Validator::make($req->all(), [
            'empleado_id' => 'required|integer|exists:empleados,id',
            'role_id' => 'required|integer|exists:roles,id',
            'email' => 'nullable|email|max:160|unique:usuarios,email',
            'username' => 'required|string|max:80|unique:usuarios,username',
            'password' => 'required|string|min:6|max:120',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function () use ($req) {
            $emp = Empleado::findOrFail($req->empleado_id);
            $personaId = $emp->persona_id;

            // valida que esa persona no tenga usuario activo
            $exists = Usuario::where('persona_id', $personaId)->where('baja',false)->exists();
            if ($exists) return response()->json(['message'=>'El empleado ya tiene usuario.'], 422);

            $u = Usuario::create([
                'persona_id' => $personaId,
                'role_id' => $req->role_id,
                'email' => $req->email,
                'username' => $req->username,
                'password_hash' => Hash::make($req->password),
                'is_active' => true,
                'baja' => false,
            ]);

            return response()->json(['ok'=>true,'id'=>$u->id]);
        });
    }

    public function update(Request $req, $id)
    {
        $u = Usuario::findOrFail($id);

        $v = Validator::make($req->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'email' => 'nullable|email|max:160|unique:usuarios,email,'.$u->id,
            'username' => 'required|string|max:80|unique:usuarios,username,'.$u->id,
            'password' => 'nullable|string|min:6|max:120',
            'is_active' => 'required|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $u->update([
            'role_id' => $req->role_id,
            'email' => $req->email,
            'username' => $req->username,
            'is_active' => (bool)$req->is_active,
            'password_hash' => $req->password ? Hash::make($req->password) : $u->password_hash,
        ]);

        return response()->json(['ok'=>true]);
    }

    public function baja(Request $request, $id)
    {
        $request->validate([
            'motivo' => ['required','string','min:3','max:500']
        ]);

        $u = Usuario::findOrFail($id);
        $u->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $request->motivo,
            'is_active' => false,
        ]);
        return response()->json(['ok'=>true]);
    }
}