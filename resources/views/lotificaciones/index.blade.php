@extends('layouts.app')
@section('title','Lotificaciones')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><span>Catálogos</span><span>›</span><b>Lotificaciones</b></div>
  <div class="actions">
    <button class="btn primary" id="btnLotificacionAdd"><i class="fa-solid fa-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Lotificaciones</h3>
      <div class="sub">Gestión de fraccionamientos / lotificaciones</div>
    </div>
    <span class="chip">LOTIFICACIONES</span>
  </div>

  <table id="tblLotificaciones" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th># Lotes</th>
        <th>Oficina</th>
        <th>Estado</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL ADD/EDIT -->
<div class="modal" id="modalLotificacion">
  <div class="box">
    <div class="mhead">
      <b id="lotModalTitle"><i class="fa-solid fa-map"></i> Lotificación</b>
      <button class="close" type="button" data-close="#modalLotificacion"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <input type="hidden" id="lot_id">
      <div class="field" style="grid-column:span 12;"><label>Nombre</label><input id="lot_nombre" type="text"></div>
      <div class="field"><label>Oficina</label><input id="lot_oficina" type="text" placeholder="Puebla / CDMX / etc"></div>
      <div class="field"><label>Estado</label><input id="lot_estado" type="text" placeholder="Puebla / Veracruz / etc"></div>
      <div class="field"><label># Lotes (opcional)</label><input id="lot_numero_lotes" type="number" min="0" value="0"></div>
      <div class="field" style="grid-column:span 12;"><label>Croquis JSON (opcional)</label><textarea id="lot_json" placeholder='{"poligonos":[]}'></textarea></div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalLotificacion"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnLotificacionSave"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.lotificaciones = {
    datatable: "{{ route('lotificaciones.datatable') }}",
    store: "{{ route('lotificaciones.store') }}",
    show: "{{ route('lotificaciones.show', ['id' => '__ID__']) }}",
    update: "{{ route('lotificaciones.update', ['id' => '__ID__']) }}",
    baja: "{{ route('lotificaciones.baja', ['id' => '__ID__']) }}",
  };
</script>
<script src="{{ asset('assets/js/lotificaciones.js') }}"></script>
@endpush