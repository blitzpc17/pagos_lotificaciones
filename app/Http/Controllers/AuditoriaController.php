<?php

namespace App\Http\Controllers;

use App\Models\AccionUsuarioHistorial;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        if (!($request->expectsJson() || $request->get('json'))) {
            return view('auditoria.index');
        }

        $q = AccionUsuarioHistorial::query()->orderByDesc('created_at');

        if ($request->filled('tabla')) {
            $q->where('tabla', 'ILIKE', '%'.$request->get('tabla').'%');
        }
        if ($request->filled('accion')) {
            $q->where('accion', 'ILIKE', '%'.$request->get('accion').'%');
        }
        if ($request->filled('usuario_id')) {
            $q->where('usuario_id', (int)$request->get('usuario_id'));
        }

        if ($request->filled('desde')) {
            $q->whereDate('created_at', '>=', $request->get('desde'));
        }
        if ($request->filled('hasta')) {
            $q->whereDate('created_at', '<=', $request->get('hasta'));
        }

        $rows = $q->limit(500)->get();

        $data = $rows->map(function($x){
            $before = $x->before_data ? json_encode($x->before_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '';
            $after  = $x->after_data ? json_encode($x->after_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '';

            // Escapa para data-attrs
            $beforeAttr = e($before);
            $afterAttr  = e($after);

            $btn = '<div class="dt-actions">';
            $btn .= '<button class="mini primary btnAuditDetail" data-before="'.$beforeAttr.'" data-after="'.$afterAttr.'">';
            $btn .= '<i class="fa-regular fa-eye"></i> Ver</button>';
            $btn .= '</div>';

            return [
                'created_at' => optional($x->created_at)->format('Y-m-d H:i:s'),
                'usuario_id' => $x->usuario_id,
                'accion' => $x->accion,
                'tabla' => $x->tabla,
                'registro_id' => $x->registro_id,
                'ip' => $x->ip,
                'acciones_html' => $btn,
            ];
        });

        return response()->json(['ok'=>true,'data'=>$data]);
    }
}
