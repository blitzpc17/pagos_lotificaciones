<?php

namespace App\Http\Controllers;

use App\Models\Socio;
use App\Models\PersonaTelefono;
use App\Models\PersonaCorreo;
use App\Models\PersonaDireccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SociosController extends Controller
{
    public function index()
    {
        return view('socios.index');
    }

    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $len    = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $orderColIdx = (int) $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        // Debe coincidir con columnas del DataTable: ID, Nombre, Color, Estatus, Acciones
        $cols = ['s.id','s.nombre','s.color','s.baja'];
        $orderBy = $cols[$orderColIdx] ?? 's.id';

        $base = DB::table('socios as s')
            ->select('s.id','s.nombre','s.color','s.telefono','s.email','s.baja','s.baja_motivo');

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('s.nombre','ilike',"%{$search}%")
                  ->orWhere('s.telefono','ilike',"%{$search}%")
                  ->orWhere('s.email','ilike',"%{$search}%");
            });
        }

        $recordsTotal = DB::table('socios')->count();
        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderByRaw("$orderBy $orderDir")
            ->offset($start)
            ->limit($len)
            ->get();

        $data = $rows->map(function($s){
            $estatus = $s->baja
                ? '<span class="badge danger">BAJA</span><div class="muted" style="font-size:12px;margin-top:4px;">Motivo: '.e($s->baja_motivo ?? '—').'</div>'
                : '<span class="badge ok">ACTIVO</span>';

            $color = '<span class="badge" style="gap:10px;">
                        <span style="width:14px;height:14px;border-radius:5px;background:'.e($s->color).';border:1px solid rgba(0,0,0,.2)"></span>
                        <b>'.e($s->color).'</b>
                      </span>';

            // ✅ CAMPO REQUERIDO POR EL DATATABLE (columna 4)
            $acciones = '
              <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnEditSocio" data-id="'.(int)$s->id.'" title="Editar">
                  <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="btn btnBajaSocio" data-id="'.(int)$s->id.'" title="Baja">
                  <i class="fa-solid fa-ban"></i>
                </button>
              </div>';

            return [
                'id' => (int)$s->id,
                'nombre' => e($s->nombre).($s->baja ? ' <span class="badge danger" style="margin-left:8px;">BAJA</span>' : ''),
                'color' => $color,
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
        $s = Socio::findOrFail($id);
        $pid = $s->persona_id;

        return response()->json([
            'id' => $s->id,
            'persona_id' => $pid,
            'nombre' => $s->nombre,
            'color' => $s->color,
            'telefono' => $s->telefono,
            'email' => $s->email,
            'baja' => (bool)$s->baja,
            'baja_motivo' => $s->baja_motivo,
            'telefonos' => $pid ? PersonaTelefono::where('persona_id',$pid)->orderBy('baja')->orderByDesc('es_principal')->get() : [],
            'correos' => $pid ? PersonaCorreo::where('persona_id',$pid)->orderBy('baja')->orderByDesc('es_principal')->get() : [],
            'direcciones' => $pid ? PersonaDireccion::where('persona_id',$pid)->orderBy('baja')->orderByDesc('es_principal')->get() : [],
        ]);
    }

    public function store(Request $req)
    {
        $v = Validator::make($req->all(), [
            'nombre' => 'required|string|max:160',
            'color' => 'required|string|max:20',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req){
            $nombre = mb_strtoupper(trim($req->nombre),'UTF-8');

            $personaId = DB::table('personas')->insertGetId([
                'nombres' => $nombre,
                'apellido_paterno' => 'SOCIO',
                'apellido_materno' => null,
                'created_at'=>now(),'updated_at'=>now(),
                'baja'=>false
            ]);

            $id = DB::table('socios')->insertGetId([
                'nombre' => $nombre,
                'color' => strtoupper(trim($req->color)),
                'telefono' => null,
                'email' => null,
                'persona_id' => $personaId,
                'created_at'=>now(),'updated_at'=>now(),
                'baja'=>false
            ]);

            return response()->json(['ok'=>true,'id'=>$id]);
        });
    }

    public function update(Request $req, $id)
    {
        $s = Socio::findOrFail($id);

        $v = Validator::make($req->all(), [
            'nombre' => 'required|string|max:160',
            'color' => 'required|string|max:20',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation error','errors'=>$v->errors()], 422);

        return DB::transaction(function() use ($req, $s){
            $nombre = mb_strtoupper(trim($req->nombre),'UTF-8');

            if (!$s->persona_id) {
                $personaId = DB::table('personas')->insertGetId([
                    'nombres' => $nombre,
                    'apellido_paterno' => 'SOCIO',
                    'apellido_materno' => null,
                    'created_at'=>now(),'updated_at'=>now(),
                    'baja'=>false
                ]);
                $s->persona_id = $personaId;
            } else {
                DB::table('personas')->where('id',$s->persona_id)->update([
                    'nombres'=>$nombre,
                    'updated_at'=>now()
                ]);
            }

            $s->nombre = $nombre;
            $s->color = strtoupper(trim($req->color));
            $s->updated_at = now();
            $s->save();

            return response()->json(['ok'=>true]);
        });
    }

    public function baja(Request $req, $id)
    {
        $v = Validator::make($req->all(), [
            'motivo' => 'required|string|min:3|max:500',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Motivo requerido','errors'=>$v->errors()], 422);

        $s = Socio::findOrFail($id);
        $s->update([
            'baja' => true,
            'baja_at' => now(),
            'baja_by' => auth()->id(),
            'baja_motivo' => $req->motivo,
        ]);

        return response()->json(['ok'=>true]);
    }
}