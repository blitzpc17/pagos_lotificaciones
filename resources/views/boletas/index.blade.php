@extends('layouts.app')
@section('title','Boletas de Pago')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Boletas de Pago</b></div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Boletas de Pago</h3>
      <div class="sub">Listado + registro de pagos (partidas) + recibos</div>
    </div>
    <span class="chip">BOLETAS</span>
  </div>

  <table id="tblBoletas" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Folio</th>
        <th>Cliente</th>
        <th>Lotificación</th>
        <th>Lote</th>
        <th>Tipo venta</th>
        <th>Saldo</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL PAGOS -->
<div class="modal" id="modalBoletaPagos">
  <div class="box" style="max-width:1050px;">
    <div class="mhead">
      <b><i class="fa-solid fa-receipt"></i> Pagos de boleta</b>
      <button class="close" type="button" data-close="#modalBoletaPagos"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <input type="hidden" id="bp_boleta_id">

      <div class="grid" style="grid-template-columns: repeat(12, 1fr); gap: 12px;">
        <div class="field" style="grid-column:span 3;">
          <label>Fecha pago</label>
          <input type="date" id="bp_fecha_pago">
        </div>

        <div class="field" style="grid-column:span 3;">
          <label>Tipo</label>
          <select id="bp_tipo_pago">
            <option value="ABONO" selected>ABONO</option>
            <option value="ENGANCHE">ENGANCHE</option>
            <option value="RECARGO">RECARGO</option>
            <option value="OTRO">OTRO</option>
          </select>
        </div>

        <div class="field" style="grid-column:span 3;">
          <label>Monto</label>
          <input type="number" step="0.01" id="bp_monto" value="0">
        </div>

        <div class="field" style="grid-column:span 3;">
          <label>Recargo</label>
          <select id="bp_recargo">
            <option value="0" selected>NO</option>
            <option value="1">SÍ</option>
          </select>
        </div>

        <div class="field" style="grid-column:span 3;">
          <label>Monto recargo</label>
          <input type="number" step="0.01" id="bp_monto_recargo" value="0">
        </div>

        <div class="field" style="grid-column:span 9;">
          <label>Observación</label>
          <input type="text" id="bp_observacion" placeholder="Opcional">
        </div>

        <div class="field" style="grid-column:span 12; display:flex; justify-content:flex-end; gap:10px;">
          <button class="btn primary" type="button" id="btnBPAddPago"><i class="fa-solid fa-plus"></i> Agregar pago</button>
        </div>
      </div>

      <div class="card" style="margin-top:12px;">
        <table id="tblBoletaPartidas" class="display nowrap" style="width:100%">
          <thead>
            <tr>
              <th>ID</th>
              <th>Folio</th>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Monto</th>
              <th>Recargo</th>
              <th>Total</th>
              <th style="text-align:right;">Recibo</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalBoletaPagos"><i class="fa-regular fa-circle-xmark"></i> Cerrar</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.boletas = {
    datatable: "{{ route('boletas.datatable') }}",
    partidas: "{{ route('boletas.partidas', ['id' => '__ID__']) }}",
    addPago: "{{ route('boletas.pagos_add', ['id' => '__ID__']) }}",
    reciboPartida: "{{ route('boletas.recibo_partida', ['partidaId' => '__ID__']) }}"
  };
</script>
<script src="{{ asset('assets/js/boletas.js') }}"></script>
@endpush