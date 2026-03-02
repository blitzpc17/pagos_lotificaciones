@extends('layouts.app')
@section('title','Lotes')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><span>Catálogos</span><span>›</span><b>Lotes</b></div>
  <div class="actions">
    <button class="btn primary" id="btnLoteAdd"><i class="fa-solid fa-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Lotes</h3>
      <div class="sub">Lotes por lotificación (LIBRE / OCUPADO / LIBERADO)</div>
    </div>
    <span class="chip">LOTES</span>
  </div>

  <div class="row" style="gap:12px;align-items:end;margin:8px 0 14px;">
    <div class="field" style="min-width:280px;">
      <label>Filtrar por lotificación</label>
      <select id="flt_lotificacion" class="select">
        <option value="">-- Todas --</option>
        @foreach($lotificaciones as $l)
          <option value="{{ $l->id }}">{{ $l->nombre }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <table id="tblLotes" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Lotificación</th>
        <th>Clave</th>
        <th>Manzana</th>
        <th>Número</th>
        <th>Estado</th>
        <th>Contado</th>
        <th>Crédito</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL ADD/EDIT -->
<div class="modal" id="modalLote">
  <div class="box">
    <div class="mhead">
      <b id="loteModalTitle"><i class="fa-solid fa-border-all"></i> Lote</b>
      <button class="close" type="button" data-close="#modalLote"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <input type="hidden" id="lote_id">
      <div class="field" style="grid-column:span 12;">
        <label>Lotificación</label>
        <select id="lote_lotificacion_id" class="select">
          @foreach($lotificaciones as $l)
            <option value="{{ $l->id }}">{{ $l->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div class="field"><label>Clave lote</label><input id="lote_clave" type="text" placeholder="L-001"></div>
      <div class="field"><label>Manzana</label><input id="lote_manzana" type="text"></div>
      <div class="field"><label>Número</label><input id="lote_numero" type="text"></div>

      <div class="field">
        <label>Estado</label>
        <select id="lote_estado" class="select">
          <option value="LIBRE">LIBRE</option>
          <option value="OCUPADO">OCUPADO</option>
          <option value="LIBERADO">LIBERADO</option>
        </select>
      </div>

      <div class="field"><label>Costo contado</label><input id="lote_contado" type="number" min="0" step="0.01" value="0"></div>
      <div class="field"><label>Costo crédito</label><input id="lote_credito" type="number" min="0" step="0.01" value="0"></div>
      <div class="field" style="grid-column:span 12;"><label>Notas</label><textarea id="lote_notas"></textarea></div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalLote"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnLoteSave"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.lotes = {
    datatable: "{{ route('lotes.datatable') }}",
    store: "{{ route('lotes.store') }}",
    show: "{{ route('lotes.show', ['id' => '__ID__']) }}",
    update: "{{ route('lotes.update', ['id' => '__ID__']) }}",
    baja: "{{ route('lotes.baja', ['id' => '__ID__']) }}",
  };
</script>
<script src="{{ asset('assets/js/lotes.js') }}"></script>
@endpush