@extends('layouts.app')

@section('content')
<div class="breadcrumb">
  <div class="path">
    <span>Inicio</span><span>›</span><span>Seguridad</span><span>›</span><b>Auditoría</b>
  </div>
  <div class="actions">
    <button class="btn" type="button" id="btnAuditRefresh">
      <i class="fa-solid fa-arrows-rotate"></i> Refrescar
    </button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Auditoría</h3>
      <div class="sub">Solo lectura (acciones del sistema)</div>
    </div>
    <span class="chip">Logs</span>
  </div>

  <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
    <input class="audit-in" id="fTabla" placeholder="Tabla (ej. usuarios)" />
    <input class="audit-in" id="fAccion" placeholder="Acción (CREAR/MODIFICAR/BAJA)" />
    <input class="audit-in" id="fUsuario" placeholder="Usuario ID" />
    <input class="audit-in" id="fDesde" type="date" />
    <input class="audit-in" id="fHasta" type="date" />
    <button class="btn primary" id="btnAuditFilter"><i class="fa-solid fa-filter"></i> Filtrar</button>
  </div>

  <table id="tblAudit" class="display" style="width:100%">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Usuario</th>
        <th>Acción</th>
        <th>Tabla</th>
        <th>Registro</th>
        <th>IP</th>
        <th style="text-align:right;">Detalle</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL DETALLE -->
<div class="modal" id="modalAuditDetail">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-file-circle-check"></i> Detalle auditoría</b>
      <button class="close" type="button" data-close="#modalAuditDetail"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="mbody" style="grid-template-columns: 1fr; gap:12px;">
      <div class="field" style="grid-column: span 12;">
        <label>Before</label>
        <textarea id="audit_before" style="min-height:160px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;"></textarea>
      </div>
      <div class="field" style="grid-column: span 12;">
        <label>After</label>
        <textarea id="audit_after" style="min-height:160px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;"></textarea>
      </div>
    </div>
    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalAuditDetail"><i class="fa-regular fa-circle-xmark"></i> Cerrar</button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/audit.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/audit.js') }}"></script>
@endpush
