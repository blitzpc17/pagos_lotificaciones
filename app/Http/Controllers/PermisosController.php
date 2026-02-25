<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Modulo;
use App\Models\RolModulo;
use App\Models\Usuario;
use App\Models\UsuarioAccionModulo;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermisosController extends Controller
{
    public function index()
    {
        // vista única: eliges rol para módulos, eliges usuario para acciones
        $roles = Rol::where('baja', false)->orderBy('nombre')->get(['id','nombre']);
        $usuarios = Usuario::where('baja', false)->orderByDesc('id')->get(['id','username','email']);
        $modulos = Modulo::where('baja', false)->orderBy('parent_id')->orderBy('orden')->get(['id','nombre','parent_id','ruta']);
        return view('permisos.index', compact('roles','usuarios','modulos'));
    }

    // -------- ROL -> MODULOS -----------
    public function getRoleModules(Rol $rol)
    {
        $ids = RolModulo::where('role_id', $rol->id)->pluck('modulo_id')->values();
        return response()->json(['ok'=>true,'data'=>$ids]);
    }

    public function setRoleModules(Request $request, Rol $rol)
    {
        $data = $request->validate([
            'modulo_ids' => ['array'],
            'modulo_ids.*' => ['integer'],
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $rol, $me){
            $before = RolModulo::where('role_id',$rol->id)->pluck('modulo_id')->values()->all();

            RolModulo::where('role_id', $rol->id)->delete();
            $ids = $data['modulo_ids'] ?? [];

            foreach ($ids as $mid) {
                RolModulo::create(['role_id'=>$rol->id,'modulo_id'=>$mid]);
            }

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'MODIFICAR','roles_modulos',$rol->id,
                    ['role_id'=>$rol->id,'modulos'=>$before],
                    ['role_id'=>$rol->id,'modulos'=>$ids],
                    $request
                );
            }

            return response()->json(['ok'=>true,'message'=>'Permisos por rol actualizados.']);
        });
    }

    // -------- USUARIO -> ACCIONES POR MODULO ----------
    public function getUserActions(Usuario $usuario)
    {
        $rows = UsuarioAccionModulo::where('usuario_id',$usuario->id)->get();
        $map = [];
        foreach($rows as $r){
            $map[$r->modulo_id] = [
                'puede_ver'=>$r->puede_ver,
                'puede_crear'=>$r->puede_crear,
                'puede_modificar'=>$r->puede_modificar,
                'puede_baja'=>$r->puede_baja,
            ];
        }
        return response()->json(['ok'=>true,'data'=>$map]);
    }

    public function setUserActions(Request $request, Usuario $usuario)
    {
        $data = $request->validate([
            'acciones' => ['required','array'], // { modulo_id: {puede_ver,...} }
        ]);

        $me = auth()->user();

        return DB::transaction(function() use ($data, $request, $usuario, $me){
            $before = UsuarioAccionModulo::where('usuario_id',$usuario->id)->get()->toArray();

            UsuarioAccionModulo::where('usuario_id',$usuario->id)->delete();

            foreach($data['acciones'] as $moduloId => $a){
                UsuarioAccionModulo::create([
                    'usuario_id'=>$usuario->id,
                    'modulo_id'=>(int)$moduloId,
                    'puede_ver'=> (bool)($a['puede_ver'] ?? true),
                    'puede_crear'=> (bool)($a['puede_crear'] ?? false),
                    'puede_modificar'=> (bool)($a['puede_modificar'] ?? false),
                    'puede_baja'=> (bool)($a['puede_baja'] ?? false),
                ]);
            }

            if (class_exists(AuditService::class)) {
                $after = UsuarioAccionModulo::where('usuario_id',$usuario->id)->get()->toArray();
                AuditService::log($me->id,'MODIFICAR','usuarios_acciones_modulo',$usuario->id,
                    ['usuario_id'=>$usuario->id,'rows'=>$before],
                    ['usuario_id'=>$usuario->id,'rows'=>$after],
                    $request
                );
            }

            return response()->json(['ok'=>true,'message'=>'Permisos por acciones del usuario actualizados.']);
        });
    }
}
