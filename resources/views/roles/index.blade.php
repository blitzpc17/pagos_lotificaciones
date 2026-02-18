@extends('layouts.app')

@section('content')
<div class="breadcrumb">
  <div class="path">
    <span>Inicio</span><span>›</span><span>Seguridad</span><span>›</span><b>Roles</b>
  </div>
  <div class="actions">
    <button class="btn primary" type="button" id="btnRoleAdd">
      <i class="fa-solid fa-plus"></i> Nuevo
    </button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Roles</h3>
      <div class="sub">CRUD de roles (baja lógica)</div>
    </div>
    <span class="chip">Seguridad</span>
  </div>

  <table id="tblRoles" class="display" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL ADD -->
<div class="modal" id="modalRoleAdd">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-id-badge"></i> Nuevo Rol</b>
      <button class="close" type="button" data-close="#modalRoleAdd"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="mbody">
      <div class="field">
        <label>Nombre</label>
        <input type="text" id="add_role_nombre" placeholder="Ej. VENTAS" />
      </div>
      <div class="field">
        <label>Activo</label>
        <select id="add_role_active">
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>
      <div class="field" style="grid-column: span 12;">
        <label>Descripción</label>
        <textarea id="add_role_desc" placeholder="Descripción..."></textarea>
      </div>
    </div>
    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalRoleAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnRoleSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="modalRoleEdit">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-regular fa-pen-to-square"></i> Editar Rol</b>
      <button class="close" type="button" data-close="#modalRoleEdit"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="mbody">
      <div class="field">
        <label>ID</label>
        <input type="text" id="edit_role_id" disabled />
      </div>
      <div class="field">
        <label>Nombre</label>
        <input type="text" id="edit_role_nombre" />
      </div>
      <div class="field">
        <label>Activo</label>
        <select id="edit_role_active">
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>
      <div class="field" style="grid-column: span 12;">
        <label>Descripción</label>
        <textarea id="edit_role_desc"></textarea>
      </div>
    </div>
    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalRoleEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnRoleSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/roles.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/roles.js') }}"></script>
@endpush
