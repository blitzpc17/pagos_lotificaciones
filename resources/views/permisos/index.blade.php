@extends('layouts.app')
@section('title','Permisos')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Permisos</b></div>
</div>

<div class="grid">
  <div class="card" style="grid-column: span 6;">
    <div class="panel-title">
      <div><h3>Rol → Módulos</h3><div class="sub">Define el drawer para cada rol</div></div>
      <span class="chip">roles_modulos</span>
    </div>

    <div class="field" style="grid-column: span 12;">
      <label>Rol</label>
      <select id="roleSelect">
        <option value="">-- Selecciona --</option>
        @foreach($roles as $r)
          <option value="{{ $r->id }}">{{ $r->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div class="card" style="margin-top:12px;">
      <b>Lista de módulos</b>
      <div class="muted">Marca lo que el rol podrá ver en el menú.</div>

      <div id="roleModulesList" style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
        @foreach($modulos as $m)
          <label class="check" style="display:flex; gap:10px; align-items:center;">
            <input type="checkbox" class="chkRoleModule" value="{{ $m->id }}">
            <span><b>{{ $m->nombre }}</b> <span class="muted" style="margin-left:6px;">{{ $m->ruta }}</span></span>
          </label>
        @endforeach
      </div>

      <div style="margin-top:12px; display:flex; gap:10px; justify-content:flex-end;">
        <button class="btn" id="btnRoleClear"><i class="fa-regular fa-circle-xmark"></i> Limpiar</button>
        <button class="btn primary" id="btnRoleSave"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
      </div>
    </div>
  </div>

  <div class="card" style="grid-column: span 6;">
    <div class="panel-title">
      <div><h3>Usuario → Acciones</h3><div class="sub">Ver/Crear/Modificar/Baja por módulo</div></div>
      <span class="chip">usuarios_acciones_modulo</span>
    </div>

    <div class="field" style="grid-column: span 12;">
      <label>Usuario</label>
      <select id="userSelect">
        <option value="">-- Selecciona --</option>
        @foreach($usuarios as $u)
          <option value="{{ $u->id }}">{{ $u->username ?? $u->email ?? ('#'.$u->id) }}</option>
        @endforeach
      </select>
    </div>

    <div class="card" style="margin-top:12px;">
      <b>Acciones por módulo</b>
      <div class="muted">Si no existe fila para un módulo, se interpreta como “sin permisos especiales”.</div>

      <div style="overflow:auto; margin-top:10px;">
        <table class="display" style="width:100%" id="tblUserActions">
          <thead>
            <tr>
              <th>Módulo</th>
              <th>Ver</th><th>Crear</th><th>Modificar</th><th>Baja</th>
            </tr>
          </thead>
          <tbody>
            @foreach($modulos as $m)
              <tr data-mid="{{ $m->id }}">
                <td><b>{{ $m->nombre }}</b><div class="muted">{{ $m->ruta }}</div></td>
                <td><input type="checkbox" class="ua_ver"></td>
                <td><input type="checkbox" class="ua_crear"></td>
                <td><input type="checkbox" class="ua_modificar"></td>
                <td><input type="checkbox" class="ua_baja"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="margin-top:12px; display:flex; gap:10px; justify-content:flex-end;">
        <button class="btn" id="btnUserClear"><i class="fa-regular fa-circle-xmark"></i> Limpiar</button>
        <button class="btn primary" id="btnUserSave"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/permisos.js') }}"></script>
@endpush
