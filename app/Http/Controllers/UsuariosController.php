<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\Rol;
use App\Http\Resources\UsuariosResource;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        // JSON for DataTables
        if ($request->expectsJson() || $request->get('json')) {
            $rows = Usuario::query()
                ->with(['persona','rol'])
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'ok' => true,
                'data' => UsuariosResource::collection($rows),
            ]);
        }

        return view('usuarios.index');
    }

    public function show(Request $request, Usuario $usuario)
    {
        $usuario->load(['persona','rol']);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $usuario->id,
                'username' => $usuario->username,
                'email' => $usuario->email,
                'role_id' => $usuario->role_id,
                'is_active' => (bool)$usuario->is_active,
                'baja' => (bool)$usuario->baja,
                'persona' => [
                    'id' => $usuario->persona?->id,
                    'nombres' => $usuario->persona?->nombres,
                    'apellido_paterno' => $usuario->persona?->apellido_paterno,
                    'apellido_materno' => $usuario->persona?->apellido_materno,
                    'fecha_nacimiento' => $usuario->persona?->fecha_nacimiento,
                    'notas' => $usuario->persona?->notas,
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['nullable','string','max:80', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:usuarios,username'],
            'email'    => ['nullable','email','max:160', 'unique:usuarios,email'],
            'password' => ['required','string','min:6','max:200'],
            'role_id'  => ['required','integer', Rule::exists('roles','id')->where(fn($q)=>$q->where('baja',false))],

            'persona.nombres'          => ['required','string','max:120'],
            'persona.apellido_paterno' => ['required','string','max:80'],
            'persona.apellido_materno' => ['nullable','string','max:80'],
            'persona.fecha_nacimiento' => ['nullable','date'],
            'persona.notas'            => ['nullable','string'],
        ]);

        if (empty($data['username']) && empty($data['email'])) {
            return response()->json(['ok'=>false,'message'=>'Captura username o email.'], 422);
        }

        $me = auth()->user();

        return DB::transaction(function () use ($data, $request, $me) {

            $persona = Persona::create([
                'nombres' => $data['persona']['nombres'],
                'apellido_paterno' => $data['persona']['apellido_paterno'],
                'apellido_materno' => $data['persona']['apellido_materno'] ?? null,
                'fecha_nacimiento' => $data['persona']['fecha_nacimiento'] ?? null,
                'notas' => $data['persona']['notas'] ?? null,
            ]);

            $usuario = Usuario::create([
                'persona_id' => $persona->id,
                'role_id' => (int)$data['role_id'],
                'email' => $data['email'] ?? null,
                'username' => $data['username'] ?? null,
                'password_hash' => Hash::make($data['password']),
                'is_active' => true,
                'baja' => false,
            ]);

            if (class_exists(AuditService::class)) {
                AuditService::log(
                    usuarioId: $me->id,
                    accion: 'CREAR',
                    tabla: 'usuarios',
                    registroId: $usuario->id,
                    before: null,
                    after: [
                        'usuario' => $usuario->only(['id','persona_id','role_id','email','username','is_active','baja']),
                        'persona' => $persona->only(['id','nombres','apellido_paterno','apellido_materno','fecha_nacimiento','notas']),
                    ],
                    request: $request
                );
            }

            return response()->json(['ok'=>true,'message'=>'Usuario creado correctamente.']);
        });
    }

    public function update(Request $request, Usuario $usuario)
    {
        $data = $request->validate([
            'username' => ['nullable','string','max:80','regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('usuarios','username')->ignore($usuario->id)
            ],
            'email'    => ['nullable','email','max:160',
                Rule::unique('usuarios','email')->ignore($usuario->id)
            ],
            'role_id'  => ['required','integer', Rule::exists('roles','id')->where(fn($q)=>$q->where('baja',false))],
            'password' => ['nullable','string','min:6','max:200'],

            'persona.nombres'          => ['required','string','max:120'],
            'persona.apellido_paterno' => ['required','string','max:80'],
            'persona.apellido_materno' => ['nullable','string','max:80'],
            'persona.fecha_nacimiento' => ['nullable','date'],
            'persona.notas'            => ['nullable','string'],
        ]);

        if (empty($data['username']) && empty($data['email'])) {
            return response()->json(['ok'=>false,'message'=>'Captura username o email.'], 422);
        }

        $me = auth()->user();

        return DB::transaction(function () use ($data, $request, $me, $usuario) {

            $usuario->load('persona','rol');

            $before = [
                'usuario' => $usuario->only(['id','persona_id','role_id','email','username','is_active','baja']),
                'persona' => $usuario->persona?->only(['id','nombres','apellido_paterno','apellido_materno','fecha_nacimiento','notas']),
            ];

            $usuario->persona->update([
                'nombres' => $data['persona']['nombres'],
                'apellido_paterno' => $data['persona']['apellido_paterno'],
                'apellido_materno' => $data['persona']['apellido_materno'] ?? null,
                'fecha_nacimiento' => $data['persona']['fecha_nacimiento'] ?? null,
                'notas' => $data['persona']['notas'] ?? null,
            ]);

            $usuario->role_id = (int)$data['role_id'];
            $usuario->email = $data['email'] ?? null;
            $usuario->username = $data['username'] ?? null;

            if (!empty($data['password'])) {
                $usuario->password_hash = Hash::make($data['password']);
            }

            $usuario->save();

            $after = [
                'usuario' => $usuario->fresh()->only(['id','persona_id','role_id','email','username','is_active','baja']),
                'persona' => $usuario->persona->fresh()->only(['id','nombres','apellido_paterno','apellido_materno','fecha_nacimiento','notas']),
            ];

            if (class_exists(AuditService::class)) {
                AuditService::log(
                    usuarioId: $me->id,
                    accion: 'MODIFICAR',
                    tabla: 'usuarios',
                    registroId: $usuario->id,
                    before: $before,
                    after: $after,
                    request: $request
                );
            }

            return response()->json(['ok'=>true,'message'=>'Usuario actualizado correctamente.']);
        });
    }

    public function baja(Request $request, Usuario $usuario)
    {
        $data = $request->validate([
            'motivo' => ['nullable','string','max:500'],
        ]);

        $me = auth()->user();

        return DB::transaction(function () use ($request, $me, $usuario, $data) {

            $before = $usuario->only(['id','baja','is_active','baja_at','baja_by','baja_motivo']);

            $usuario->baja = true;
            $usuario->is_active = false;
            $usuario->baja_at = now();
            $usuario->baja_by = $me->id;
            $usuario->baja_motivo = $data['motivo'] ?? 'Baja desde UI';
            $usuario->save();

            $after = $usuario->fresh()->only(['id','baja','is_active','baja_at','baja_by','baja_motivo']);

            if (class_exists(AuditService::class)) {
                AuditService::log(
                    usuarioId: $me->id,
                    accion: 'BAJA',
                    tabla: 'usuarios',
                    registroId: $usuario->id,
                    before: ['usuario'=>$before],
                    after: ['usuario'=>$after],
                    request: $request
                );
            }

            return response()->json(['ok'=>true,'message'=>'Usuario dado de baja correctamente.']);
        });
    }
}
