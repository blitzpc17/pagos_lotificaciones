<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SociosController extends Controller
{
    public function index(Request $request)
    {
        return view('socios.index');
    }

    public function datatable(Request $request)
    {
        $draw   = intval($request->input('draw', 1));
        $start  = intval($request->input('start', 0));
        $len    = intval($request->input('length', 10));
        $search = trim($request->input('search.value', ''));

        $orderColIdx = intval($request->input('order.0.column', 0));
        $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        // Deben coincidir con columnas DataTables
        $cols = ['s.id','s.nombre','s.color','s.baja'];
        $orderBy = $cols[$orderColIdx] ?? 's.id';

        $base = DB::table('socios as s')
            ->select('s.id','s.nombre','s.color','s.telefono','s.email','s.baja');

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
            ->offset($start)->limit($len)->get();

        $data = $rows->map(function($r){
            $estatus = $r->baja
                ? '<span class="badge danger">BAJA</span>'
                : '<span class="badge ok">ACTIVO</span>';

            $color = '<span class="badge" style="gap:10px;">
                        <span style="width:14px;height:14px;border-radius:5px;background:'.$r->color.';border:1px solid rgba(0,0,0,.2)"></span>
                        <b>'.e($r->color).'</b>
                      </span>';

            $acciones = '
              <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                <button class="btn btnEditSocio" data-id="'.$r->id.'"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btnBajaSocio" data-id="'.$r->id.'"><i class="fa-solid fa-ban"></i></button>
              </div>';

            return [
                'id' => $r->id,
                'nombre' => e($r->nombre),
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
        $socio = DB::table('socios')->where('id',$id)->first();
        if(!$socio) return response()->json(['message'=>'No encontrado'],404);

        // Si existen tablas de contactos para socio, las intentamos leer
        $telefonos = [];
        $correos = [];
        $direcciones = [];

        try {
            if (DB::getSchemaBuilder()->hasTable('socios_telefonos')) {
                $telefonos = DB::table('socios_telefonos')->where('socio_id',$id)->where('baja',false)->orderByDesc('es_principal')->get();
            }
            if (DB::getSchemaBuilder()->hasTable('socios_correos')) {
                $correos = DB::table('socios_correos')->where('socio_id',$id)->where('baja',false)->orderByDesc('es_principal')->get();
            }
            if (DB::getSchemaBuilder()->hasTable('socios_direcciones')) {
                $direcciones = DB::table('socios_direcciones')->where('socio_id',$id)->where('baja',false)->orderByDesc('es_principal')->get();
            }
        } catch (\Throwable $e) {
            // si no existen, no pasa nada
        }

        return response()->json([
            'id' => $socio->id,
            'nombre' => $socio->nombre,
            'color' => $socio->color,
            'telefono' => $socio->telefono,
            'email' => $socio->email,
            'baja' => (bool)$socio->baja,
            'telefonos' => $telefonos,
            'correos' => $correos,
            'direcciones' => $direcciones,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'   => ['required','string','max:160'],
            'color'    => ['required','string','max:20'],
            'telefono' => ['nullable','string','max:40'],
            'email'    => ['nullable','email','max:160'],
            'telefonos' => ['nullable','array'],
            'correos' => ['nullable','array'],
            'direcciones' => ['nullable','array'],
        ]);

        return DB::transaction(function() use ($data) {

            $id = DB::table('socios')->insertGetId([
                'nombre' => mb_strtoupper($data['nombre']),
                'color' => strtoupper($data['color']),
                'telefono' => $data['telefono'] ? mb_strtoupper($data['telefono']) : null,
                'email' => $data['email'] ? strtolower($data['email']) : null,
                'created_at'=>now(),
                'updated_at'=>now(),
                'baja'=>false,
            ]);

            // contactos opcionales si existen tablas
            $this->syncContactosIfTablesExist($id, $data);

            return response()->json(['ok'=>true,'id'=>$id]);
        });
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre'   => ['required','string','max:160'],
            'color'    => ['required','string','max:20'],
            'telefono' => ['nullable','string','max:40'],
            'email'    => ['nullable','email','max:160'],
            'telefonos' => ['nullable','array'],
            'correos' => ['nullable','array'],
            'direcciones' => ['nullable','array'],
        ]);

        return DB::transaction(function() use ($data, $id) {

            $ok = DB::table('socios')->where('id',$id)->update([
                'nombre' => mb_strtoupper($data['nombre']),
                'color' => strtoupper($data['color']),
                'telefono' => $data['telefono'] ? mb_strtoupper($data['telefono']) : null,
                'email' => $data['email'] ? strtolower($data['email']) : null,
                'updated_at'=>now(),
            ]);

            if(!$ok) return response()->json(['message'=>'No encontrado'],404);

            $this->syncContactosIfTablesExist($id, $data);

            return response()->json(['ok'=>true]);
        });
    }

    public function baja(Request $request, $id)
    {
        $ok = DB::table('socios')->where('id',$id)->update([
            'baja'=>true,
            'baja_at'=>now(),
            'baja_by'=>auth()->id(),
            'baja_motivo'=>$request->input('motivo','Baja desde UI'),
            'updated_at'=>now(),
        ]);
        if(!$ok) return response()->json(['message'=>'No encontrado'],404);
        return response()->json(['ok'=>true]);
    }

    private function syncContactosIfTablesExist(int $socioId, array $data): void
    {
        try{
            $sch = DB::getSchemaBuilder();

            if($sch->hasTable('socios_telefonos')){
                foreach(($data['telefonos'] ?? []) as $t){
                    if(empty($t['telefono'])) continue;
                    DB::table('socios_telefonos')->insert([
                        'socio_id'=>$socioId,
                        'etiqueta'=> mb_strtoupper($t['etiqueta'] ?? 'principal'),
                        'telefono'=> mb_strtoupper($t['telefono']),
                        'extension'=> isset($t['extension']) ? mb_strtoupper($t['extension']) : null,
                        'es_principal'=> !empty($t['es_principal']),
                        'baja'=>false,
                        'created_at'=>now(),
                        'updated_at'=>now(),
                    ]);
                }
            }

            if($sch->hasTable('socios_correos')){
                foreach(($data['correos'] ?? []) as $c){
                    if(empty($c['correo'])) continue;
                    DB::table('socios_correos')->insert([
                        'socio_id'=>$socioId,
                        'etiqueta'=> mb_strtoupper($c['etiqueta'] ?? 'principal'),
                        'correo'=> strtolower($c['correo']),
                        'es_principal'=> !empty($c['es_principal']),
                        'baja'=>false,
                        'created_at'=>now(),
                        'updated_at'=>now(),
                    ]);
                }
            }

            if($sch->hasTable('socios_direcciones')){
                foreach(($data['direcciones'] ?? []) as $d){
                    if(empty($d['calle']) && empty($d['cp'])) continue;
                    DB::table('socios_direcciones')->insert([
                        'socio_id'=>$socioId,
                        'etiqueta'=> mb_strtoupper($d['etiqueta'] ?? 'principal'),
                        'calle'=> isset($d['calle']) ? mb_strtoupper($d['calle']) : null,
                        'numero_ext'=> isset($d['numero_ext']) ? mb_strtoupper($d['numero_ext']) : null,
                        'numero_int'=> isset($d['numero_int']) ? mb_strtoupper($d['numero_int']) : null,
                        'colonia'=> isset($d['colonia']) ? mb_strtoupper($d['colonia']) : null,
                        'municipio'=> isset($d['municipio']) ? mb_strtoupper($d['municipio']) : null,
                        'estado'=> isset($d['estado']) ? mb_strtoupper($d['estado']) : null,
                        'cp'=> isset($d['cp']) ? mb_strtoupper($d['cp']) : null,
                        'referencias'=> isset($d['referencias']) ? mb_strtoupper($d['referencias']) : null,
                        'es_principal'=> !empty($d['es_principal']),
                        'baja'=>false,
                        'created_at'=>now(),
                        'updated_at'=>now(),
                    ]);
                }
            }

        }catch(\Throwable $e){
            // si no existen tablas o hay mismatch, no truena el socio base
        }
    }
}
