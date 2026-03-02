@extends('layouts.app')
@section('title','Reporte de pagos')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><span>Reportes</span><span>›</span><b>Reporte de pagos</b></div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Reporte de pagos (mes)</h3>
      <div class="sub">Pagos del mes desde boletas_partidas + recargos + enganches</div>
    </div>
    <span class="chip">REPORTE</span>
  </div>

  <div class="grid" style="grid-template-columns: repeat(12, 1fr); gap:12px; margin-bottom:12px;">
    <div class="field" style="grid-column:span 2;">
      <label>Mes</label>
      <select id="rep_mes">
        @for($m=1;$m<=12;$m++)
          <option value="{{ $m }}" {{ $m==now()->month ? 'selected' : '' }}>
            {{ \Carbon\Carbon::create(null,$m,1)->translatedFormat('F') }}
          </option>
        @endfor
      </select>
    </div>

    <div class="field" style="grid-column:span 2;">
      <label>Año</label>
      <select id="rep_anio">
        @for($y=now()->year-3;$y<=now()->year+1;$y++)
          <option value="{{ $y }}" {{ $y==now()->year ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
      </select>
    </div>

    <div class="field" style="grid-column:span 4;">
      <label>Oficina</label>
      <select id="rep_oficina">
        <option value="">Todas</option>
        @foreach($oficinas as $o)
          <option value="{{ $o }}">{{ $o }}</option>
        @endforeach
      </select>
    </div>

    <div class="field" style="grid-column:span 4;">
      <label>Lotificación</label>
      <select id="rep_lotificacion">
        <option value="0">Todas</option>
        @foreach($lotificaciones as $l)
          <option value="{{ $l->id }}">{{ $l->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div class="field" style="grid-column:span 12; display:flex; justify-content:flex-end; gap:10px;">
      <button class="btn" id="btnRepExportCsv"><i class="fa-solid fa-file-csv"></i> Exportar CSV</button>
      <button class="btn primary" id="btnRepFiltrar"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
    </div>
  </div>

  <table id="tblReportePagos" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>OFICINA</th>
        <th>LOTIFICACION</th>
        <th>LOTE</th>
        <th>NOMBRE DEL CLIENTE</th>
        <th>NUM</th>
        <th>MENSUALIDAD</th>
        <th id="thRealPagado">REAL PAGADO</th>
        <th>APARTADO/ENGANCHE</th>
        <th>COBRO DE RECARGO</th>
        <th>FOLIO</th>
        <th>OBSERVACION</th>
      </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
      <tr>
        <th colspan="6" style="text-align:right;">TOTAL</th>
        <th id="ftRealPagado" style="text-align:right;"></th>
        <th id="ftEnganche" style="text-align:right;"></th>
        <th id="ftRecargo" style="text-align:right;"></th>
        <th colspan="2"></th>
      </tr>
    </tfoot>
  </table>
</div>
@endsection

@push('styles')
<style>
  #tblReportePagos thead th{
    background:#E87722; color:#fff; border-color:#E87722;
    font-weight:700; text-transform:uppercase; font-size:12px;
  }
  #tblReportePagos tfoot th{
    background:#f3f4f6;
    font-weight:800;
  }
</style>
@endpush

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.reportePagos = {
    datatable: "{{ route('reportes.pagos.datatable') }}",
    totales: "{{ route('reportes.pagos.totales') }}",
    exportCsv: "{{ route('reportes.pagos.export.csv') }}"
  };
</script>
<script src="{{ asset('assets/js/reporte_pagos.js') }}"></script>
@endpush