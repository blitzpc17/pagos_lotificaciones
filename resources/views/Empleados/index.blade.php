@extends('layouts.app')
@section('title','Empleados')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Empleados</b></div>
  <div class="actions">
    <button class="btn primary" id="btnEmpleadoAdd"><i class="fa-solid fa-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Empleados</h3>
      <div class="sub">Persona + Empleado (con contactos)</div>
    </div>
    <span class="chip">RH</span>
  </div>

  <table id="tblEmpleados" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Puesto</th>
        <th>No. Empleado</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

@php
  $puestos = ['GERENTE','ADMINISTRACION','VENTAS','COBRANZA','AUXILIAR_ADMIN','CONTABILIDAD','SISTEMAS','SUPERVISOR','OTRO'];
@endphp

<!-- MODAL ADD -->
<div class="modal" id="modalEmpleadoAdd">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-user-plus"></i> Nuevo Empleado</b>
      <button class="close" data-close="#modalEmpleadoAdd"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field"><label>Nombres</label><input id="e_add_nombres" type="text"></div>
      <div class="field"><label>Apellido paterno</label><input id="e_add_apellido_paterno" type="text"></div>
      <div class="field"><label>Apellido materno</label><input id="e_add_apellido_materno" type="text"></div>
      <div class="field"><label>Fecha nacimiento</label><input type="date" id="e_add_fecha_nacimiento"></div>

      <div class="field">
        <label>Puesto</label>
        <select id="e_add_puesto">
          @foreach($puestos as $p)
            <option value="{{ $p }}">{{ $p }}</option>
          @endforeach
        </select>
      </div>

      <div class="field"><label>Puesto detalle</label><input id="e_add_puesto_detalle" type="text" placeholder="OPCIONAL"></div>

      <div class="field">
        <label>Número empleado</label>
        <input id="e_add_numero_empleado" type="text" disabled placeholder="(SE GENERA AUTOMÁTICAMENTE)">
      </div>

      <div class="field"><label>Observaciones</label><input id="e_add_observaciones" type="text"></div>

      <div class="field" style="grid-column:span 12;">
        <label>Notas persona</label>
        <textarea id="e_add_notas"></textarea>
      </div>

      <!-- VENDEDOR (solo puesto VENTAS) -->
      <div class="card" id="vendorBlockAdd" style="grid-column:span 12; display:none; padding:12px;">
        <div class="panel-title" style="margin-bottom:8px;">
          <div>
            <h3 style="margin:0;">Datos de Vendedor</h3>
            <div class="sub">Solo aplica para puesto VENTAS</div>
          </div>
          <span class="chip"><i class="fa-solid fa-tags"></i> VENTAS</span>
        </div>

        <div class="field" style="grid-column:span 4;">
          <label>Comisión default (%)</label>
          <input id="e_add_comision_default" type="number" step="0.01" min="0" value="0">
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Clave vendedor</label>
          <input id="e_add_clave_vendedor" type="text" disabled placeholder="(SE GENERA AUTOMÁTICAMENTE)">
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Nota</label>
          <input type="text" value="LA CLAVE SE ASIGNA AL GUARDAR" disabled>
        </div>
      </div>

      <!-- CONTACTOS EN TABS -->
      <div class="field" style="grid-column:span 12;">
        <div class="card" style="background:var(--panel2);">
          <div class="panel-title" style="margin:0;">
            <div>
              <h3>Contactos</h3>
              <div class="sub">Teléfonos / Correos / Direcciones</div>
            </div>
          </div>

          <div class="tabs" data-tabs="empleadosAdd" style="margin-top:12px;">
            <div class="tabs-bar">
              <div class="tab-btns">
                <button type="button" class="tab-btn active" data-tab="tel"><i class="fa-solid fa-phone"></i> TELÉFONOS</button>
                <button type="button" class="tab-btn" data-tab="mail"><i class="fa-solid fa-envelope"></i> CORREOS</button>
                <button type="button" class="tab-btn" data-tab="dir"><i class="fa-solid fa-location-dot"></i> DIRECCIONES</button>
              </div>
            </div>

            <div class="tab-panels">
              <div class="tab-panel active" data-panel="tel">
                <div class="tab-panel-head">
                  <div class="left"><b>Teléfonos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddTelAdd"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body"><div id="telListAdd" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailAdd"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body"><div id="mailListAdd" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirAdd"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body"><div id="dirListAdd" class="stack"></div></div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <!-- /CONTACTOS -->

    </div>

    <div class="mfoot">
      <button class="btn" data-close="#modalEmpleadoAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnEmpleadoSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="modalEmpleadoEdit">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-regular fa-pen-to-square"></i> Editar Empleado</b>
      <button class="close" data-close="#modalEmpleadoEdit"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field"><label>ID</label><input id="e_edit_id" disabled></div>
      <div class="field"><label>Nombres</label><input id="e_edit_nombres" type="text"></div>
      <div class="field"><label>Apellido paterno</label><input id="e_edit_apellido_paterno" type="text"></div>
      <div class="field"><label>Apellido materno</label><input id="e_edit_apellido_materno" type="text"></div>
      <div class="field"><label>Fecha nacimiento</label><input type="date" id="e_edit_fecha_nacimiento"></div>

      <div class="field">
        <label>Puesto</label>
        <select id="e_edit_puesto">
          @foreach($puestos as $p)
            <option value="{{ $p }}">{{ $p }}</option>
          @endforeach
        </select>
      </div>

      <div class="field"><label>Puesto detalle</label><input id="e_edit_puesto_detalle" type="text"></div>

      <div class="field">
        <label>Número empleado</label>
        <input id="e_edit_numero_empleado" type="text" disabled>
      </div>

      <div class="field"><label>Observaciones</label><input id="e_edit_observaciones" type="text"></div>

      <div class="field" style="grid-column:span 12;">
        <label>Notas persona</label>
        <textarea id="e_edit_notas"></textarea>
      </div>

      <!-- VENDEDOR -->
      <div class="card" id="vendorBlockEdit" style="grid-column:span 12; display:none; padding:12px;">
        <div class="panel-title" style="margin-bottom:8px;">
          <div>
            <h3 style="margin:0;">Datos de Vendedor</h3>
            <div class="sub">Solo aplica para puesto VENTAS</div>
          </div>
          <span class="chip"><i class="fa-solid fa-tags"></i> VENTAS</span>
        </div>

        <div class="field" style="grid-column:span 4;">
          <label>Comisión default (%)</label>
          <input id="e_edit_comision_default" type="number" step="0.01" min="0" value="0">
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Clave vendedor</label>
          <input id="e_edit_clave_vendedor" type="text" disabled>
        </div>
        <div class="field" style="grid-column:span 4;">
          <label>Nota</label>
          <input type="text" value="LA CLAVE NO ES EDITABLE" disabled>
        </div>
      </div>

      <!-- CONTACTOS EN TABS -->
      <div class="field" style="grid-column:span 12;">
        <div class="card" style="background:var(--panel2);">
          <div class="panel-title" style="margin:0;">
            <div>
              <h3>Contactos</h3>
              <div class="sub">Teléfonos / Correos / Direcciones</div>
            </div>
          </div>

          <div class="tabs" data-tabs="empleadosEdit" style="margin-top:12px;">
            <div class="tabs-bar">
              <div class="tab-btns">
                <button type="button" class="tab-btn active" data-tab="tel"><i class="fa-solid fa-phone"></i> TELÉFONOS</button>
                <button type="button" class="tab-btn" data-tab="mail"><i class="fa-solid fa-envelope"></i> CORREOS</button>
                <button type="button" class="tab-btn" data-tab="dir"><i class="fa-solid fa-location-dot"></i> DIRECCIONES</button>
              </div>
            </div>

            <div class="tab-panels">
              <div class="tab-panel active" data-panel="tel">
                <div class="tab-panel-head">
                  <div class="left"><b>Teléfonos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddTelEdit"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body"><div id="telListEdit" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailEdit"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body"><div id="mailListEdit" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirEdit"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body"><div id="dirListEdit" class="stack"></div></div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <!-- /CONTACTOS -->

    </div>

    <div class="mfoot">
      <button class="btn" data-close="#modalEmpleadoEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" id="btnEmpleadoSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.empleados = window.ROUTES.empleados || {};
  window.ROUTES.empleados.telAdd = "{{ url('/empleados/__ID__/telefonos') }}";
  window.ROUTES.empleados.mailAdd = "{{ url('/empleados/__ID__/correos') }}";
  window.ROUTES.empleados.dirAdd = "{{ url('/empleados/__ID__/direcciones') }}";
  window.ROUTES.empleados.contactoBaja = "{{ route('empleados.contacto.baja', ['tipo'=>'__T__','cid'=>'__CID__']) }}";
  window.ROUTES.empleados.contactoReactivar = "{{ route('empleados.contacto.reactivar', ['tipo'=>'__T__','cid'=>'__CID__']) }}";
</script>
<script src="{{ asset('assets/js/empleados.js') }}"></script>
@endpush