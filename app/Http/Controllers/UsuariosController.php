<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Rol;
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
        $rows = Usuario::query()
            ->with(['empleado.persona','role'])
            ->where('baja', false)
            ->orderByDesc('id')
            ->get()
            ->map(function($u){
                $p = $u->empleado->persona;
                return [
                    'id' => $u->id,
                    'username' => $u->username,
                    'email' => $u->email,
                    'empleado' => $u->empleado->numero_empleado.' · '.trim($p->nombres.' '.$p->apellido_paterno.' '.$p->apellido_materno),
                    'rol' => $u->role->nombre ?? '',
                    'estatus' => $u->baja ? 'Baja' : ($u->is_active ? 'Activo' : 'Inactivo'),
                ];
            });

        return response()->json(['data'=>$rows]);
    }

    public function empleadosDisponibles()
    {
        // Empleados sin usuario y activos
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
                    'numero_empleado' => $e->numero_empleado,
                    'nombre' => trim($p->nombres.' '.$p->apellido_paterno.' '.$p->apellido_materno),
                    'puesto' => $e->puesto,
                ];
            });

        return response()->json(['data'=>$emps]);
    }

    public function show($id)
    {
        $u = Usuario::with(['empleado.persona','role'])->findOrFail($id);
        $p = $u->empleado->persona;

        return response()->json([
            'id' => $u->id,
            'empleado_id' => $u->empleado_id,
            'email' => $u->email,
            'username' => $u->username,
            'role_id' => $u->role_id,
            'is_active' => (bool)$u->is_active,
            'empleado_label' => $u->empleado->numero_empleado.' · '.trim($p->nombres.' '.$p->apellido_paterno.' '.$p->apellido_materno),
        ]);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'empleado_id' => 'required|integer|exists:empleados,id',
            'role_id' => 'required|integer|exists:roles,id',
            'email' => 'nullable|email|max:160|unique:usuarios,email',
            'username' => 'required|string|max:80|unique:usuarios,username',
            'password' => 'required|string|min:6|max:120',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function () use ($req) {
            // valida que el empleado no tenga usuario activo
            $exists = Usuario::where('empleado_id', $req->empleado_id)->where('baja',false)->exists();
            if ($exists) return response()->json(['message'=>'El empleado ya tiene usuario.'], 422);

            $u = Usuario::create([
                'empleado_id' => $req->empleado_id,
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

    public function baja($id)
    {
        $u = Usuario::findOrFail($id);
        $u->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => 'Baja desde módulo usuarios',
            'is_active' => false,
        ]);
        return response()->json(['ok'=>true]);
    }
}
