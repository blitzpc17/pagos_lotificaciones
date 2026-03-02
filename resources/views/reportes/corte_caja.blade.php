@extends('layouts.app')
@section('title','Corte de caja')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><span>Reportes</span><span>›</span><b>Corte de caja</b></div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3 id="ttlCorte">CORTE DE CAJA</h3>
      <div class="sub">Agrupado por cliente → boleta (mes)</div>
    </div>
    <span class="chip">CORTE</span>
  </div>

  <div class="grid" style="grid-template-columns: repeat(12, 1fr); gap:12px; margin-bottom:12px;">
    <div class="field" style="grid-column:span 2;">
      <label>Mes</label>
      <select id="cc_mes">
        @for($m=1;$m<=12;$m++)
          <option value="{{ $m }}" {{ $m==now()->month ? 'selected' : '' }}>
            {{ \Carbon\Carbon::create(null,$m,1)->translatedFormat('F') }}
          </option>
        @endfor
      </select>
    </div>

    <div class="field" style="grid-column:span 2;">
      <label>Año</label>
      <select id="cc_anio">
        @for($y=now()->year-3;$y<=now()->year+1;$y++)
          <option value="{{ $y }}" {{ $y==now()->year ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
      </select>
    </div>

    <div class="field" style="grid-column:span 4;">
      <label>Oficina</label>
      <select id="cc_oficina">
        <option value="">Todas</option>
        @foreach($oficinas as $o)
          <option value="{{ $o }}">{{ $o }}</option>
        @endforeach
      </select>
    </div>

    <div class="field" style="grid-column:span 4;">
      <label>Lotificación</label>
      <select id="cc_lotificacion">
        <option value="0">Todas</option>
        @foreach($lotificaciones as $l)
          <option value="{{ $l->id }}">{{ $l->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div class="field" style="grid-column:span 12; display:flex; justify-content:flex-end; gap:10px;">
      <button class="btn" id="btnCCExportCsv"><i class="fa-solid fa-file-csv"></i> Exportar CSV</button>
      <button class="btn primary" id="btnCCFiltrar"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
    </div>
  </div>

  <div style="overflow:auto;">
    <table id="tblCorteCaja" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>LUGAR</th>
          <th>NO. DE LOTES</th>
          <th>FECHA</th>
          <th>SOCIO</th>
          <th>VENDEDOR</th>
          <th>LOTIFICACION</th>
          <th>LOTE</th>
          <th>MZ</th>
          <th>NOMBRE DEL CLIENTE</th>
          <th>ESTATUS</th>
          <th>COSTO CONTADO</th>
          <th>COSTO CREDITO</th>
          <th>ENGANCHE</th>
          <th>COMISION</th>
          <th><b>INGRESO CARTERA GLOBAL</b></th>
          <th id="thIngresoMes">INGRESO</th>
          <th>MESES</th>
          <th id="thCarteraMes">CARTERA</th>
        </tr>
      </thead>
      <tbody></tbody>
      <tfoot>
        <tr>
          <th colspan="15" style="text-align:right;">TOTALES</th>
          <th id="ftIngresoMes" style="text-align:right;"></th>
          <th></th>
          <th id="ftCarteraMes" style="text-align:right;"></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
@endsection

@push('styles')
<style>
  /* header azul como imagen */
  #tblCorteCaja thead th{
    background:#7DB3E6;
    color:#fff;
    font-weight:800;
    text-transform:uppercase;
    font-size:12px;
    border-color:#7DB3E6;
    white-space:nowrap;
  }
  #tblCorteCaja tfoot th{
    background:#f3f4f6;
    font-weight:800;
  }
  /* resaltar columna ingreso cartera global */
  #tblCorteCaja tbody td.col-icg{
    font-weight:900;
  }
</style>
@endpush

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.corteCaja = {
    datatable: "{{ route('reportes.corte_caja.datatable') }}",
    totales: "{{ route('reportes.corte_caja.totales') }}",
    exportCsv: "{{ route('reportes.corte_caja.export.csv') }}"
  };
</script>
<script src="{{ asset('assets/js/corte_caja.js') }}"></script>
@endpush