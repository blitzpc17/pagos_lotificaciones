@extends('layouts.app')
@section('title','Autorizaciones')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Autorizaciones</b></div>
</div>

<div class="card">
  <div class="panel-title">
    <div><h3>Solicitudes</h3><div class="sub">BAJA / MODIFICACIÓN para boletas_pago y boletas_partidas</div></div>
    <span class="chip">solicitudes</span>
  </div>

  <table id="tblSolicitudes" class="display" style="width:100%">
    <thead><tr>
      <th>ID</th><th>Tipo</th><th>Tabla</th><th>Registro</th><th>Motivo</th><th>Solicitado</th><th>Por</th><th>Estatus</th><th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody></tbody>
  </table>
</div>

<div class="modal" id="modalSolView">
  <div class="box">
    <div class="mhead"><b><i class="fa-regular fa-eye"></i> Detalle de solicitud</b><button class="close" data-close="#modalSolView"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mbody">
      <div class="field" style="grid-column:span 12;">
        <label>Detalle (JSON)</label>
        <textarea id="sol_json" style="min-height:260px; font-family:ui-monospace,Consolas,monospace;"></textarea>
      </div>
    </div>
    <div class="mfoot">
      <button class="btn" data-close="#modalSolView"><i class="fa-regular fa-circle-xmark"></i> Cerrar</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/autorizaciones.js') }}"></script>
@endpush
