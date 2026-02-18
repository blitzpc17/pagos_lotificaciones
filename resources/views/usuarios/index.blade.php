@extends('layouts.app')

@section('content')
<div class="breadcrumb">
  <div class="path">
    <span>Inicio</span><span>›</span><span>Seguridad</span><span>›</span><b>Usuarios</b>
  </div>
  <div class="actions">
    <button class="btn primary" type="button" id="btnUserAdd">
      <i class="fa-solid fa-plus"></i> Nuevo
    </button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Usuarios</h3>
      <div class="sub">CRUD (Persona + Usuario en transaction)</div>
    </div>
    <span class="chip">Seguridad</span>
  </div>

  <table id="tblUsuarios" class="display" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Usuario</th>
        <th>Email</th>
        <th>Rol</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL ADD -->
<div class="modal" id="modalUserAdd">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-user-plus"></i> Nuevo Usuario</b>
      <button class="close" type="button" data-close="#modalUserAdd"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field">
        <label>Nombres</label>
        <input type="text" id="add_nombres" placeholder="Ej. Juan" />
      </div>
      <div class="field">
        <label>Apellido paterno</label>
        <input type="text" id="add_apellido_paterno" placeholder="Ej. Pérez" />
      </div>
      <div class="field">
        <label>Apellido materno</label>
        <input type="text" id="add_apellido_materno" placeholder="Ej. López" />
      </div>
      <div class="field">
        <label>Fecha nacimiento</label>
        <input type="date" id="add_fecha_nacimiento" />
      </div>

      <div class="field">
        <label>Username (opcional si hay email)</label>
        <input type="text" id="add_username" placeholder="Ej. jperez" />
      </div>
      <div class="field">
        <label>Email (opcional si hay username)</label>
        <input type="email" id="add_email" placeholder="Ej. jperez@dominio.com" />
      </div>

      <div class="field">
        <label>Rol</label>
        <select id="add_role_id"></select>
      </div>

      <div class="field">
        <label>Contraseña</label>
        <input type="password" id="add_password" placeholder="******" />
      </div>

      <div class="field" style="grid-column: span 12;">
        <label>Notas</label>
        <textarea id="add_notas" placeholder="Notas de la persona..."></textarea>
      </div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalUserAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnUserSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="modalUserEdit">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-regular fa-pen-to-square"></i> Editar Usuario</b>
      <button class="close" type="button" data-close="#modalUserEdit"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field">
        <label>ID</label>
        <input type="text" id="edit_id" disabled />
      </div>

      <div class="field">
        <label>Nombres</label>
        <input type="text" id="edit_nombres" />
      </div>
      <div class="field">
        <label>Apellido paterno</label>
        <input type="text" id="edit_apellido_paterno" />
      </div>
      <div class="field">
        <label>Apellido materno</label>
        <input type="text" id="edit_apellido_materno" />
      </div>
      <div class="field">
        <label>Fecha nacimiento</label>
        <input type="date" id="edit_fecha_nacimiento" />
      </div>

      <div class="field">
        <label>Username</label>
        <input type="text" id="edit_username" />
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" id="edit_email" />
      </div>

      <div class="field">
        <label>Rol</label>
        <select id="edit_role_id"></select>
      </div>

      <div class="field">
        <label>Nueva contraseña (opcional)</label>
        <input type="password" id="edit_password" placeholder="Dejar vacío para no cambiar" />
      </div>

      <div class="field" style="grid-column: span 12;">
        <label>Notas</label>
        <textarea id="edit_notas"></textarea>
      </div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalUserEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnUserSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/users.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/users.js') }}"></script>
@endpush
