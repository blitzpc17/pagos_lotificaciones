@extends('layouts.app')
@section('title','Usuarios')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Usuarios</b></div>
  <div class="actions"><button class="btn primary" id="btnUserAdd"><i class="fa-solid fa-plus"></i> Nuevo</button></div>
</div>

<div class="card">
  <div class="panel-title"><div><h3>Usuarios</h3><div class="sub">Ahora se crean a partir de un Empleado existente (browser)</div></div><span class="chip">Seguridad</span></div>

  <table id="tblUsuarios" class="display" style="width:100%">
    <thead><tr>
      <th>ID</th><th>Empleado</th><th>Username</th><th>Email</th><th>Rol</th><th>Estatus</th><th style="text-align:right;">Acciones</th>
    </tr></thead>
    <tbody></tbody>
  </table>
</div>

<!-- ADD -->
<div class="modal" id="modalUserAdd">
  <div class="box">
    <div class="mhead"><b><i class="fa-solid fa-user-plus"></i> Nuevo Usuario</b><button class="close" data-close="#modalUserAdd"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mbody">
      <div class="field" style="grid-column:span 12;">
        <label>Empleado (sin usuario)</label>
        <select id="u_add_empleado_id"></select>
        <div class="muted">Se cargan empleados disponibles automáticamente.</div>
      </div>

      <div class="field"><label>Rol</label>
        <select id="u_add_role_id">
          @foreach($roles as $r)
            <option value="{{ $r->id }}">{{ $r->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div class="field"><label>Username</label><input id="u_add_username"></div>
      <div class="field"><label>Email</label><input id="u_add_email"></div>
      <div class="field"><label>Password</label><input id="u_add_password" type="password"></div>
    </div>
    <div class="mfoot">
      <button class="btn" data-close="#modalUserAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnUserSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- EDIT -->
<div class="modal" id="modalUserEdit">
  <div class="box">
    <div class="mhead"><b><i class="fa-regular fa-pen-to-square"></i> Editar Usuario</b><button class="close" data-close="#modalUserEdit"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="mbody">
      <div class="field"><label>ID</label><input id="u_edit_id" disabled></div>
      <div class="field" style="grid-column:span 12;"><label>Empleado</label><input id="u_edit_empleado_label" disabled></div>

      <div class="field"><label>Rol</label>
        <select id="u_edit_role_id">
          @foreach($roles as $r)
            <option value="{{ $r->id }}">{{ $r->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div class="field"><label>Username</label><input id="u_edit_username"></div>
      <div class="field"><label>Email</label><input id="u_edit_email"></div>
      <div class="field"><label>Password (opcional)</label><input id="u_edit_password" type="password" placeholder="Deja vacío para no cambiar"></div>

      <div class="field">
        <label>Estatus</label>
        <select id="u_edit_is_active">
          <option value="1">Activo</option>
          <option value="0">Inactivo</option>
        </select>
      </div>
    </div>
    <div class="mfoot">
      <button class="btn" data-close="#modalUserEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnUserSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.usuarios = {
    datatable: "{{ route('usuarios.datatable') }}",
    empleados: "{{ route('usuarios.empleados_disponibles') }}",
    store: "{{ route('usuarios.store') }}",
    show: "{{ route('usuarios.show', ['id'=>'__ID__']) }}",
    update: "{{ route('usuarios.update', ['id'=>'__ID__']) }}",
    baja: "{{ route('usuarios.baja', ['id'=>'__ID__']) }}"
  };
</script>
<script src="{{ asset('assets/js/usuarios.js') }}"></script>
@endpush
