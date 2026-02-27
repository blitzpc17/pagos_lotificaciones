<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Models\PersonaTelefono;
use App\Models\PersonaCorreo;
use App\Models\PersonaDireccion;

class PersonaContactosController extends Controller
{
    /**
     * Resuelve persona_id desde un "owner" (empleados/clientes/socios/proveedores) y su id.
     */
    private function personaIdFromOwner(string $owner, int $id): int
    {
        $owner = strtolower(trim($owner));

        $row = match ($owner) {
            'empleados'   => DB::table('empleados')->where('id', $id)->first(['persona_id']),
            'clientes'    => DB::table('clientes')->where('id', $id)->first(['persona_id']),
            'proveedores' => DB::table('proveedores')->where('id', $id)->first(['persona_id']),
            'socios'      => DB::table('socios')->where('id', $id)->first(['persona_id']),
            default       => null,
        };

        if (!$row || empty($row->persona_id)) {
            abort(404, 'No existe persona ligada.');
        }

        return (int) $row->persona_id;
    }

    /**
     * Helper: obtiene owner desde la ruta + castea el id del recurso (empleado/cliente/socio/proveedor).
     */
    private function ownerAndId(Request $req, $id): array
    {
        $owner = (string) $req->route('owner', '');
        $idInt = is_numeric($id) ? (int)$id : 0;
        return [$owner, $idInt];
    }

    /**
     * GET /{owner}/{id}/contactos
     */
    public function contactosByOwner(Request $req, $id)
    {
        [$owner, $id] = $this->ownerAndId($req, $id);
        if ($id <= 0) return response()->json(['message' => 'ID inválido'], 422);

        $personaId = $this->personaIdFromOwner($owner, $id);

        $telefonos = PersonaTelefono::where('persona_id', $personaId)
            ->orderBy('baja')
            ->orderByDesc('es_principal')
            ->orderByDesc('id')
            ->get();

        $correos = PersonaCorreo::where('persona_id', $personaId)
            ->orderBy('baja')
            ->orderByDesc('es_principal')
            ->orderByDesc('id')
            ->get();

        $direcciones = PersonaDireccion::where('persona_id', $personaId)
            ->orderBy('baja')
            ->orderByDesc('es_principal')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'ok' => true,
            'owner' => $owner,
            'owner_id' => $id,
            'persona_id' => $personaId,
            'telefonos' => $telefonos,
            'correos' => $correos,
            'direcciones' => $direcciones,
        ]);
    }

    /**
     * POST /{owner}/{id}/telefonos
     * UPSERT: si body trae id => update; si no => create
     */
    public function addTelefono(Request $req, $id)
    {
        [$owner, $id] = $this->ownerAndId($req, $id);
        if ($id <= 0) return response()->json(['message' => 'ID inválido'], 422);

        $personaId = $this->personaIdFromOwner($owner, $id);

        $v = Validator::make($req->all(), [
            'id' => 'nullable|integer',
            'etiqueta' => 'nullable|string|max:40',
            'telefono' => 'required|string|max:40',
            'extension' => 'nullable|string|max:12',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $cid = $req->input('id');

        if ($cid) {
            $row = PersonaTelefono::where('persona_id', $personaId)->where('id', $cid)->firstOrFail();
            $before = $row->toArray();

            $row->update([
                'etiqueta' => $req->input('etiqueta', 'principal'),
                'telefono' => $req->telefono,
                'extension' => $req->extension,
                'es_principal' => (bool)$req->input('es_principal', false),

                // si lo "editas" se reactiva automáticamente
                'baja' => false,
                'baja_at' => null,
                'baja_by' => null,
                'baja_motivo' => null,
            ]);

            if ($row->es_principal) {
                PersonaTelefono::where('persona_id', $personaId)->where('id', '<>', $row->id)->update(['es_principal' => false]);
            }

            $this->audit('MODIFICAR', 'persona_telefonos', $row->id, $before, $row->fresh()->toArray(), $req);

            return response()->json(['ok' => true, 'id' => $row->id]);
        }

        $tel = PersonaTelefono::create([
            'persona_id' => $personaId,
            'etiqueta' => $req->input('etiqueta', 'principal'),
            'telefono' => $req->telefono,
            'extension' => $req->extension,
            'es_principal' => (bool)$req->input('es_principal', false),
            'baja' => false,
        ]);

        if ($tel->es_principal) {
            PersonaTelefono::where('persona_id', $personaId)->where('id', '<>', $tel->id)->update(['es_principal' => false]);
        }

        $this->audit('CREAR', 'persona_telefonos', $tel->id, null, $tel->toArray(), $req);

        return response()->json(['ok' => true, 'id' => $tel->id]);
    }

    /**
     * POST /{owner}/{id}/correos
     * UPSERT
     */
    public function addCorreo(Request $req, $id)
    {
        [$owner, $id] = $this->ownerAndId($req, $id);
        if ($id <= 0) return response()->json(['message' => 'ID inválido'], 422);

        $personaId = $this->personaIdFromOwner($owner, $id);

        $v = Validator::make($req->all(), [
            'id' => 'nullable|integer',
            'etiqueta' => 'nullable|string|max:40',
            'correo' => 'required|email|max:160',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $cid = $req->input('id');

        if ($cid) {
            $row = PersonaCorreo::where('persona_id', $personaId)->where('id', $cid)->firstOrFail();
            $before = $row->toArray();

            $row->update([
                'etiqueta' => $req->input('etiqueta', 'principal'),
                'correo' => $req->correo,
                'es_principal' => (bool)$req->input('es_principal', false),

                'baja' => false,
                'baja_at' => null,
                'baja_by' => null,
                'baja_motivo' => null,
            ]);

            if ($row->es_principal) {
                PersonaCorreo::where('persona_id', $personaId)->where('id', '<>', $row->id)->update(['es_principal' => false]);
            }

            $this->audit('MODIFICAR', 'persona_correos', $row->id, $before, $row->fresh()->toArray(), $req);

            return response()->json(['ok' => true, 'id' => $row->id]);
        }

        $mail = PersonaCorreo::create([
            'persona_id' => $personaId,
            'etiqueta' => $req->input('etiqueta', 'principal'),
            'correo' => $req->correo,
            'es_principal' => (bool)$req->input('es_principal', false),
            'baja' => false,
        ]);

        if ($mail->es_principal) {
            PersonaCorreo::where('persona_id', $personaId)->where('id', '<>', $mail->id)->update(['es_principal' => false]);
        }

        $this->audit('CREAR', 'persona_correos', $mail->id, null, $mail->toArray(), $req);

        return response()->json(['ok' => true, 'id' => $mail->id]);
    }

    /**
     * POST /{owner}/{id}/direcciones
     * UPSERT
     */
    public function addDireccion(Request $req, $id)
    {
        [$owner, $id] = $this->ownerAndId($req, $id);
        if ($id <= 0) return response()->json(['message' => 'ID inválido'], 422);

        $personaId = $this->personaIdFromOwner($owner, $id);

        $v = Validator::make($req->all(), [
            'id' => 'nullable|integer',
            'etiqueta' => 'nullable|string|max:40',
            'calle' => 'nullable|string|max:160',
            'numero_ext' => 'nullable|string|max:30',
            'numero_int' => 'nullable|string|max:30',
            'colonia' => 'nullable|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'estado' => 'nullable|string|max:120',
            'cp' => 'nullable|string|max:12',
            'referencias' => 'nullable|string|max:5000',
            'es_principal' => 'nullable|boolean',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        $cid = $req->input('id');

        if ($cid) {
            $row = PersonaDireccion::where('persona_id', $personaId)->where('id', $cid)->firstOrFail();
            $before = $row->toArray();

            $row->update([
                'etiqueta' => $req->input('etiqueta', 'principal'),
                'calle' => $req->calle,
                'numero_ext' => $req->numero_ext,
                'numero_int' => $req->numero_int,
                'colonia' => $req->colonia,
                'municipio' => $req->municipio,
                'estado' => $req->estado,
                'cp' => $req->cp,
                'referencias' => $req->referencias,
                'es_principal' => (bool)$req->input('es_principal', false),

                'baja' => false,
                'baja_at' => null,
                'baja_by' => null,
                'baja_motivo' => null,
            ]);

            if ($row->es_principal) {
                PersonaDireccion::where('persona_id', $personaId)->where('id', '<>', $row->id)->update(['es_principal' => false]);
            }

            $this->audit('MODIFICAR', 'persona_direcciones', $row->id, $before, $row->fresh()->toArray(), $req);

            return response()->json(['ok' => true, 'id' => $row->id]);
        }

        $dir = PersonaDireccion::create([
            'persona_id' => $personaId,
            'etiqueta' => $req->input('etiqueta', 'principal'),
            'calle' => $req->calle,
            'numero_ext' => $req->numero_ext,
            'numero_int' => $req->numero_int,
            'colonia' => $req->colonia,
            'municipio' => $req->municipio,
            'estado' => $req->estado,
            'cp' => $req->cp,
            'referencias' => $req->referencias,
            'es_principal' => (bool)$req->input('es_principal', false),
            'baja' => false,
        ]);

        if ($dir->es_principal) {
            PersonaDireccion::where('persona_id', $personaId)->where('id', '<>', $dir->id)->update(['es_principal' => false]);
        }

        $this->audit('CREAR', 'persona_direcciones', $dir->id, null, $dir->toArray(), $req);

        return response()->json(['ok' => true, 'id' => $dir->id]);
    }

    /**
     * POST /{owner}/contacto/{tipo}/{cid}/baja
     * motivo obligatorio
     */
    public function bajaContacto(Request $req, $tipo, $cid)
    {
        $cid = is_numeric($cid) ? (int)$cid : 0;
        if ($cid <= 0) return response()->json(['message'=>'ID de contacto inválido'], 422);

        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        [$model, $table] = match (strtolower($tipo)) {
            'tel'  => [PersonaTelefono::class, 'persona_telefonos'],
            'mail' => [PersonaCorreo::class, 'persona_correos'],
            'dir'  => [PersonaDireccion::class, 'persona_direcciones'],
            default => [null, null],
        };
        if (!$model) return response()->json(['message'=>'Tipo inválido'], 422);

        $m = $model::findOrFail($cid);
        $before = $m->only(['baja','baja_motivo','baja_at','baja_by']);

        $m->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
        ]);

        $this->audit('BAJA', $table, $m->id, $before, $m->fresh()->toArray(), $req);

        return response()->json(['ok'=>true]);
    }

    /**
     * POST /{owner}/contacto/{tipo}/{cid}/reactivar
     */
    public function reactivarContacto(Request $req, $tipo, $cid)
    {
        $cid = is_numeric($cid) ? (int)$cid : 0;
        if ($cid <= 0) return response()->json(['message'=>'ID de contacto inválido'], 422);

        [$model, $table] = match (strtolower($tipo)) {
            'tel'  => [PersonaTelefono::class, 'persona_telefonos'],
            'mail' => [PersonaCorreo::class, 'persona_correos'],
            'dir'  => [PersonaDireccion::class, 'persona_direcciones'],
            default => [null, null],
        };
        if (!$model) return response()->json(['message'=>'Tipo inválido'], 422);

        $m = $model::findOrFail($cid);
        $before = $m->only(['baja','baja_motivo','baja_at','baja_by']);

        $m->update([
            'baja' => false,
            'baja_at' => null,
            'baja_by' => null,
            'baja_motivo' => null,
        ]);

        $this->audit('MODIFICAR', $table, $m->id, $before, $m->fresh()->toArray(), $req);

        return response()->json(['ok'=>true]);
    }

    /**
     * Auditoría (si existe el servicio).
     */
    private function audit(string $accion, string $tabla, ?int $registroId, $before, $after, Request $req): void
    {
        if (!class_exists(\App\Services\AuditService::class)) return;

        \App\Services\AuditService::log(
            auth()->id(),
            $accion,
            $tabla,
            $registroId,
            $before,
            $after,
            $req,
            $req->attributes->get('current_modulo_id')
        );
    }
}