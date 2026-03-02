@extends('layouts.app')
@section('title','Resumen por lotificación')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><span>Reportes</span><span>›</span><b>Resumen por lotificación</b></div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3 id="ttlRes">Resumen por lotificación</h3>
      <div class="sub">Contratos / Enganches / Cobrado / Resto / Ingreso mensual</div>
    </div>
    <span class="chip">REPORTE</span>
  </div>

  <div class="grid" style="grid-template-columns: repeat(12, 1fr); gap:12px; margin-bottom:12px;">
    <div class="field" style="grid-column:span 2;">
      <label>Mes</label>
      <select id="rl_mes">
        @for($m=1;$m<=12;$m++)
          <option value="{{ $m }}" {{ $m==now()->month ? 'selected' : '' }}>
            {{ \Carbon\Carbon::create(null,$m,1)->translatedFormat('F') }}
          </option>
        @endfor
      </select>
    </div>

    <div class="field" style="grid-column:span 2;">
      <label>Año</label>
      <select id="rl_anio">
        @for($y=now()->year-3;$y<=now()->year+1;$y++)
          <option value="{{ $y }}" {{ $y==now()->year ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
      </select>
    </div>

    <div class="field" style="grid-column:span 4;">
      <label>Oficina</label>
      <select id="rl_oficina">
        <option value="">Todas</option>
        @foreach($oficinas as $o)
          <option value="{{ $o }}">{{ $o }}</option>
        @endforeach
      </select>
    </div>

    <div class="field" style="grid-column:span 4; display:flex; align-items:flex-end; justify-content:flex-end; gap:10px;">
      <button class="btn" id="btnRLExportXlsx"><i class="fa-solid fa-file-excel"></i> Excel</button>
      <button class="btn" id="btnRLExportCsv"><i class="fa-solid fa-file-csv"></i> CSV</button>
      <button class="btn primary" id="btnRLFiltrar"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
    </div>
  </div>

  <div style="overflow:auto;">
    <table id="tblLotResumen" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>OFICINA</th>
          <th>LOTIFICACION</th>
          <th>CONTRATOS</th>
          <th>ENGANCHES</th>
          <th>COBRADO</th>
          <th>RESTO POR COBRAR</th>
          <th id="thIngresoMes">INGRESO MENSUAL</th>
        </tr>
      </thead>
      <tbody></tbody>
      <tfoot>
        <tr>
          <th colspan="2" style="text-align:right;">TOTAL</th>
          <th id="ftContratos" style="text-align:right;"></th>
          <th id="ftEnganches" style="text-align:right;"></th>
          <th id="ftCobrado" style="text-align:right;"></th>
          <th id="ftResto" style="text-align:right;"></th>
          <th id="ftIngreso" style="text-align:right;"></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
@endsection

@push('styles')
<style>
  #tblLotResumen thead th{
    background:#334155;
    color:#fff;
    font-weight:800;
    text-transform:uppercase;
    font-size:12px;
    border-color:#334155;
    white-space:nowrap;
  }
  #tblLotResumen tfoot th{ background:#f3f4f6; font-weight:900; }
</style>
@endpush

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.lotResumen = {
    datatable: "{{ route('reportes.lotificaciones_resumen.datatable') }}",
    totales: "{{ route('reportes.lotificaciones_resumen.totales') }}",
    exportCsv: "{{ route('reportes.lotificaciones_resumen.export.csv') }}",
    exportXlsx: "{{ route('reportes.lotificaciones_resumen.export.xlsx') }}"
  };
</script>
<script src="{{ asset('assets/js/lotificaciones_resumen.js') }}"></script>
@endpush