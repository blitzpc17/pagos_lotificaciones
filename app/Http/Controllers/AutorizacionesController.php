<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\BoletaPago;
use App\Models\BoletaPartida;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutorizacionesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson() || $request->get('json')) {
            $rows = Solicitud::with(['solicitadoPor','revisadoPor'])
                ->where('baja', false)
                ->whereIn('tabla_objetivo', ['boletas_pago','boletas_partidas'])
                ->orderByDesc('id')
                ->get();

            $data = $rows->map(function($s){
                $estatus = match($s->estatus){
                    'PENDIENTE' => '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--warn)"></i> Pendiente</span>',
                    'APROBADA' => '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Aprobada</span>',
                    'RECHAZADA' => '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--danger)"></i> Rechazada</span>',
                    default => '<span class="badge"><i class="fa-solid fa-circle" style="color:var(--muted)"></i> '.$s->estatus.'</span>',
                };

                $acc = '<div class="dt-actions">';
                $acc .= '<button class="mini primary btnSolView" data-id="'.$s->id.'"><i class="fa-regular fa-eye"></i> Ver</button>';
                if($s->estatus === 'PENDIENTE'){
                    $acc .= '<button class="mini danger btnSolReject" data-id="'.$s->id.'"><i class="fa-solid fa-ban"></i> Rechazar</button>';
                    $acc .= '<button class="mini primary btnSolApprove" data-id="'.$s->id.'"><i class="fa-solid fa-check"></i> Aprobar</button>';
                }
                $acc .= '</div>';

                return [
                    'id'=>$s->id,
                    'tipo'=>$s->tipo,
                    'tabla_objetivo'=>$s->tabla_objetivo,
                    'registro_id'=>$s->registro_id,
                    'motivo'=>$s->motivo,
                    'solicitado_at'=>optional($s->solicitado_at)->format('Y-m-d H:i'),
                    'solicitado_por'=>$s->solicitadoPor?->username ?? $s->solicitado_por,
                    'estatus_html'=>$estatus,
                    'acciones_html'=>$acc,
                ];
            });

            return response()->json(['ok'=>true,'data'=>$data]);
        }

        return view('autorizaciones.index');
    }

    public function show(Solicitud $solicitud)
    {
        $solicitud->load(['solicitadoPor','revisadoPor']);
        return response()->json(['ok'=>true,'data'=>$solicitud]);
    }

    public function aprobar(Request $request, Solicitud $solicitud)
    {
        $data = $request->validate([
            'decision_motivo'=>['nullable','string','max:700'],
        ]);

        if ($solicitud->estatus !== 'PENDIENTE') {
            return response()->json(['ok'=>false,'message'=>'La solicitud ya fue atendida.'], 422);
        }

        $me = auth()->user();

        return DB::transaction(function() use ($solicitud, $data, $request, $me){
            $before = $solicitud->toArray();

            // Resolver target
            [$model, $allowed] = $this->resolveTarget($solicitud->tabla_objetivo);

            /** @var \Illuminate\Database\Eloquent\Model $obj */
            $obj = $model::findOrFail($solicitud->registro_id);
            $objBefore = $obj->toArray();

            if ($solicitud->tipo === 'BAJA') {
                $obj->baja = true;
                $obj->baja_at = now();
                $obj->baja_by = $me->id;
                $obj->baja_motivo = $solicitud->motivo ?? 'Baja autorizada';
                $obj->save();
            } else { // MODIFICACION
                $payload = is_array($solicitud->payload) ? $solicitud->payload : [];
                $updates = array_intersect_key($payload, array_flip($allowed));
                $obj->fill($updates);
                // “updated_by” si existe
                if (isset($obj->updated_by)) $obj->updated_by = $me->id;
                $obj->save();
            }

            $solicitud->estatus = 'APROBADA';
            $solicitud->revisado_por = $me->id;
            $solicitud->revisado_at = now();
            $solicitud->decision_motivo = $data['decision_motivo'] ?? null;
            $solicitud->save();

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'APROBAR','solicitudes',$solicitud->id,
                    ['solicitud'=>$before,'target'=>$objBefore],
                    ['solicitud'=>$solicitud->fresh()->toArray(),'target'=>$obj->fresh()->toArray()],
                    $request
                );
            }

            return response()->json(['ok'=>true,'message'=>'Solicitud aprobada y aplicada.']);
        });
    }

    public function rechazar(Request $request, Solicitud $solicitud)
    {
        $data = $request->validate([
            'decision_motivo'=>['required','string','max:700'],
        ]);

        if ($solicitud->estatus !== 'PENDIENTE') {
            return response()->json(['ok'=>false,'message'=>'La solicitud ya fue atendida.'], 422);
        }

        $me = auth()->user();

        return DB::transaction(function() use ($solicitud, $data, $request, $me){
            $before = $solicitud->toArray();

            $solicitud->estatus = 'RECHAZADA';
            $solicitud->revisado_por = $me->id;
            $solicitud->revisado_at = now();
            $solicitud->decision_motivo = $data['decision_motivo'];
            $solicitud->save();

            if (class_exists(AuditService::class)) {
                AuditService::log($me->id,'RECHAZAR','solicitudes',$solicitud->id,
                    ['solicitud'=>$before],
                    ['solicitud'=>$solicitud->fresh()->toArray()],
                    $request
                );
            }

            return response()->json(['ok'=>true,'message'=>'Solicitud rechazada.']);
        });
    }

    private function resolveTarget(string $tabla): array
    {
        if ($tabla === 'boletas_pago') {
            // whitelist de campos modificables
            return [BoletaPago::class, [
                'oficina','fecha_contrato','tipo_venta',
                'costo_contado','costo_credito','enganche','comision_vendedor','meses',
                'observaciones','socio_id','vendedor_id'
            ]];
        }

        if ($tabla === 'boletas_partidas') {
            return [BoletaPartida::class, [
                'fecha_pago','monto','recargo','monto_recargo','tipo_pago','observacion'
            ]];
        }

        abort(422, 'Tabla objetivo no permitida.');
    }
}
