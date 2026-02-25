@extends('layouts.app')
@section('title','Vendedores')
@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Vendedores</b></div>
  <div class="actions"><button class="btn primary" id="btnVendedorAdd"><i class="fa-solid fa-plus"></i> Nuevo</button></div>
</div>

<div class="card">
  <div class="panel-title"><div><h3>Vendedores</h3><div class="sub">Persona + Vendedor</div></div><span class="chip">Ventas</span></div>
  <table id="tblVendedores" class="display" style="width:100%">
    <thead><tr>
      <th>ID</th><th>Nombre</th><th>Clave</th><th>Comisión</th><th>Estatus</th><th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody></tbody>
  </table>
</div>

<div class="modal" id="modalVendedorAdd">
  <div class="box">
    <div class="mhead"><b><i class="fa-solid fa-user-plus"></i> Nuevo Vendedor</b><button class="close" data-close="#modalVendedorAdd"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mbody">
      <div class="field"><label>Nombres</label><input id="v_add_nombres"></div>
      <div class="field"><label>Apellido paterno</label><input id="v_add_apellido_paterno"></div>
      <div class="field"><label>Apellido materno</label><input id="v_add_apellido_materno"></div>
      <div class="field"><label>Fecha nacimiento</label><input type="date" id="v_add_fecha_nacimiento"></div>

      <div class="field"><label>Clave</label><input id="v_add_clave"></div>
      <div class="field"><label>Comisión default</label><input type="number" step="0.01" id="v_add_comision" value="0"></div>

      <div class="field" style="grid-column:span 12;"><label>Notas</label><textarea id="v_add_notas"></textarea></div>
    </div>
    <div class="mfoot">
      <button class="btn" data-close="#modalVendedorAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnVendedorSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<div class="modal" id="modalVendedorEdit">
  <div class="box">
    <div class="mhead"><b><i class="fa-regular fa-pen-to-square"></i> Editar Vendedor</b><button class="close" data-close="#modalVendedorEdit"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mbody">
      <div class="field"><label>ID</label><input id="v_edit_id" disabled></div>
      <div class="field"><label>Nombres</label><input id="v_edit_nombres"></div>
      <div class="field"><label>Apellido paterno</label><input id="v_edit_apellido_paterno"></div>
      <div class="field"><label>Apellido materno</label><input id="v_edit_apellido_materno"></div>
      <div class="field"><label>Fecha nacimiento</label><input type="date" id="v_edit_fecha_nacimiento"></div>

      <div class="field"><label>Clave</label><input id="v_edit_clave"></div>
      <div class="field"><label>Comisión default</label><input type="number" step="0.01" id="v_edit_comision"></div>

      <div class="field" style="grid-column:span 12;"><label>Notas</label><textarea id="v_edit_notas"></textarea></div>
    </div>
    <div class="mfoot">
      <button class="btn" data-close="#modalVendedorEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnVendedorSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/vendedores.js') }}"></script>
@endpush
