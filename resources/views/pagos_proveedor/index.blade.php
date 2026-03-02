@extends('layouts.app')
@section('title','Pagos a Proveedores')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Pagos a Proveedores</b></div>
  <div class="actions">
    <button class="btn primary" id="btnPagoProvAdd"><i class="fa-solid fa-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Pagos a Proveedores</h3>
      <div class="sub">Cabecera + partidas (abonos/anticipos/transferencias, etc.)</div>
    </div>
    <span class="chip">PAGOS</span>
  </div>

  <table id="tblPagoProveedor" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Folio</th>
        <th>Proveedor</th>
        <th>Fecha doc</th>
        <th>Monto total</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL ADD PAGO PROVEEDOR -->
<div class="modal" id="modalPagoProvAdd">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-hand-holding-dollar"></i> Nuevo pago a proveedor</b>
      <button class="close" type="button" data-close="#modalPagoProvAdd"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field" style="grid-column:span 12;">
        <label>Proveedor</label>
        <select id="pp_add_proveedor_id"></select>
      </div>

      <div class="field">
        <label>Fecha documento</label>
        <input type="date" id="pp_add_fecha_documento">
      </div>

      <div class="field">
        <label>Concepto</label>
        <input type="text" id="pp_add_concepto" placeholder="MATERIAL / SERVICIOS / etc.">
      </div>

      <div class="field">
        <label>Referencia</label>
        <input type="text" id="pp_add_referencia" placeholder="FOLIO FACTURA / OC / ...">
      </div>

      <div class="field">
        <label>Monto total</label>
        <input type="number" step="0.01" id="pp_add_monto_total" value="0">
      </div>

      <div class="field" style="grid-column:span 12;">
        <label>Observaciones</label>
        <textarea id="pp_add_observaciones"></textarea>
      </div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalPagoProvAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnPagoProvSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL PARTIDAS -->
<div class="modal" id="modalPagoProvPartidas">
  <div class="box" style="max-width: 1050px;">
    <div class="mhead">
      <b><i class="fa-solid fa-list"></i> Partidas del pago</b>
      <button class="close" type="button" data-close="#modalPagoProvPartidas"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <input type="hidden" id="pp_partidas_pago_id">
      <div class="grid" style="grid-template-columns: repeat(12, 1fr); gap: 12px;">
        <div class="field" style="grid-column:span 4;">
          <label>Fecha pago</label>
          <input type="date" id="pp_part_fecha_pago">
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Forma pago</label>
          <select id="pp_part_forma_pago">
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="TRANSFERENCIA" selected>TRANSFERENCIA</option>
            <option value="DEPOSITO">DEPOSITO</option>
            <option value="TARJETA">TARJETA</option>
            <option value="CHEQUE">CHEQUE</option>
            <option value="OTRO">OTRO</option>
          </select>
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Tipo partida</label>
          <select id="pp_part_tipo_partida">
            <option value="ABONO" selected>ABONO</option>
            <option value="ANTICIPO">ANTICIPO</option>
            <option value="PAGO_TOTAL">PAGO_TOTAL</option>
            <option value="RETENCION">RETENCION</option>
            <option value="OTRO">OTRO</option>
          </select>
        </div>

        <div class="field" style="grid-column:span 4;">
          <label>Monto</label>
          <input type="number" step="0.01" id="pp_part_monto" value="0">
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Referencia pago</label>
          <input type="text" id="pp_part_referencia_pago" placeholder="TRX / CHEQUE / ...">
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Observación</label>
          <input type="text" id="pp_part_observacion" placeholder="Opcional">
        </div>

        <div class="field" style="grid-column:span 12; display:flex; justify-content:flex-end; gap:10px;">
          <button class="btn primary" type="button" id="btnPPAddPartida"><i class="fa-solid fa-plus"></i> Agregar partida</button>
        </div>
      </div>

      <div class="card" style="margin-top:12px;">
        <table id="tblPagoProvPartidas" class="display nowrap" style="width:100%">
          <thead>
            <tr>
              <th>ID</th>
              <th>Folio</th>
              <th>Fecha</th>
              <th>Forma</th>
              <th>Tipo</th>
              <th>Monto</th>
              <th>Referencia</th>
              <th style="text-align:right;">Recibo</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalPagoProvPartidas"><i class="fa-regular fa-circle-xmark"></i> Cerrar</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.pagosProveedor = {
    datatable: "{{ route('pagos_proveedor.datatable') }}",
    proveedoresSelect: "{{ route('pagos_proveedor.proveedores_select') }}",
    store: "{{ route('pagos_proveedor.store') }}",
    show: "{{ route('pagos_proveedor.show', ['id' => '__ID__']) }}",
    partidas: "{{ route('pagos_proveedor.partidas', ['id' => '__ID__']) }}",
    addPartida: "{{ route('pagos_proveedor.partidas_add', ['id' => '__ID__']) }}",
    reciboPartida: "{{ route('pagos_proveedor.recibo_partida', ['partidaId' => '__ID__']) }}"
  };
</script>
<script src="{{ asset('assets/js/pagos_proveedor.js') }}"></script>
@endpush