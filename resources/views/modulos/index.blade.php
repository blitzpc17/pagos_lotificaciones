@extends('layouts.app')
@section('title','Módulos')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Módulos</b></div>
  <div class="actions"><button class="btn primary" id="btnModuloAdd"><i class="fa-solid fa-plus"></i> Nuevo</button></div>
</div>

<div class="card">
  <div class="panel-title"><div><h3>Módulos</h3><div class="sub">Menú + rutas + jerarquía padre/hijo</div></div><span class="chip">Seguridad</span></div>

  <table id="tblModulos" class="display" style="width:100%">
    <thead><tr>
      <th>ID</th><th>Padre</th><th>Nombre</th><th>Ruta</th><th>Icono</th><th>Menú</th><th>Orden</th><th>Estatus</th><th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody></tbody>
  </table>
</div>

<div class="modal" id="modalModuloAdd">
  <div class="box">
    <div class="mhead"><b><i class="fa-solid fa-plus"></i> Nuevo Módulo</b><button class="close" data-close="#modalModuloAdd"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mbody">
      <div class="field"><label>Nombre</label><input id="m_add_nombre"></div>
      <div class="field"><label>Ruta</label><input id="m_add_ruta" placeholder="/clientes"></div>
      <div class="field"><label>Icono</label><input id="m_add_icono" placeholder="fa-solid fa-users"></div>
      <div class="field">
        <label>Padre</label>
        <select id="m_add_parent_id"></select>
        <div class="muted">Solo lista módulos padre (categorías).</div>
      </div>
      <div class="field"><label>Es menú</label><select id="m_add_es_menu"><option value="1">Sí</option><option value="0">No</option></select></div>
      <div class="field"><label>Orden</label><input id="m_add_orden" type="number" value="0"></div>
      <div class="field"><label>Activo</label><select id="m_add_is_active"><option value="1">Sí</option><option value="0">No</option></select></div>
    </div>
    <div class="mfoot">
      <button class="btn" data-close="#modalModuloAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnModuloSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<div class="modal" id="modalModuloEdit">
  <div class="box">
    <div class="mhead"><b><i class="fa-regular fa-pen-to-square"></i> Editar Módulo</b><button class="close" data-close="#modalModuloEdit"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mbody">
      <div class="field"><label>ID</label><input id="m_edit_id" disabled></div>
      <div class="field"><label>Nombre</label><input id="m_edit_nombre"></div>
      <div class="field"><label>Ruta</label><input id="m_edit_ruta"></div>
      <div class="field"><label>Icono</label><input id="m_edit_icono"></div>
      <div class="field">
        <label>Padre</label>
        <select id="m_edit_parent_id"></select>
      </div>
      <div class="field"><label>Es menú</label><select id="m_edit_es_menu"><option value="1">Sí</option><option value="0">No</option></select></div>
      <div class="field"><label>Orden</label><input id="m_edit_orden" type="number"></div>
      <div class="field"><label>Activo</label><select id="m_edit_is_active"><option value="1">Sí</option><option value="0">No</option></select></div>
    </div>
    <div class="mfoot">
      <button class="btn" data-close="#modalModuloEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnModuloSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/modulos.js') }}"></script>
@endpush
