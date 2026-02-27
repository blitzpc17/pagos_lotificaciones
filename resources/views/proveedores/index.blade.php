@extends('layouts.app')
@section('title','Proveedores')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Proveedores</b></div>
  <div class="actions">
    <button class="btn primary" id="btnProveedorAdd"><i class="fa-solid fa-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Proveedores</h3>
      <div class="sub">Persona + Proveedor + contactos múltiples</div>
    </div>
    <span class="chip">PROVEEDORES</span>
  </div>

  <table id="tblProveedores" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>RFC</th>
        <th>Razón social</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL ADD -->
<div class="modal" id="modalProveedorAdd">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-truck-field"></i> Nuevo Proveedor</b>
      <button class="close" type="button" data-close="#modalProveedorAdd"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field"><label>Nombres</label><input id="p_add_nombres" type="text"></div>
      <div class="field"><label>Apellido paterno</label><input id="p_add_apellido_paterno" type="text"></div>
      <div class="field"><label>Apellido materno</label><input id="p_add_apellido_materno" type="text"></div>
      <div class="field"><label>Fecha nacimiento</label><input id="p_add_fecha_nacimiento" type="date"></div>

      <div class="field"><label>RFC</label><input id="p_add_rfc" type="text"></div>
      <div class="field"><label>Razón social</label><input id="p_add_razon_social" type="text" placeholder="OPCIONAL (PERSONA MORAL)"></div>

      <div class="field" style="grid-column:span 12;"><label>Notas persona</label><textarea id="p_add_persona_notas"></textarea></div>
      <div class="field" style="grid-column:span 12;"><label>Notas proveedor</label><textarea id="p_add_notas"></textarea></div>

      <div class="field" style="grid-column:span 12;">
        <div class="card" style="background:var(--panel2);">
          <div class="panel-title" style="margin:0;">
            <div>
              <h3>Contactos</h3>
              <div class="sub">Teléfonos / Correos / Direcciones</div>
            </div>
          </div>

          <div class="tabs" data-tabs="proveedoresAdd" style="margin-top:12px;">
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
                  <button class="btn" type="button" id="btnAddTelAddP"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body"><div id="telListAddP" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailAddP"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body"><div id="mailListAddP" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirAddP"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body"><div id="dirListAddP" class="stack"></div></div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalProveedorAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnProveedorSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="modalProveedorEdit">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-regular fa-pen-to-square"></i> Editar Proveedor</b>
      <button class="close" type="button" data-close="#modalProveedorEdit"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <input type="hidden" id="p_edit_id">

      <div class="field"><label>Nombres</label><input id="p_edit_nombres" type="text"></div>
      <div class="field"><label>Apellido paterno</label><input id="p_edit_apellido_paterno" type="text"></div>
      <div class="field"><label>Apellido materno</label><input id="p_edit_apellido_materno" type="text"></div>
      <div class="field"><label>Fecha nacimiento</label><input id="p_edit_fecha_nacimiento" type="date"></div>

      <div class="field"><label>RFC</label><input id="p_edit_rfc" type="text"></div>
      <div class="field"><label>Razón social</label><input id="p_edit_razon_social" type="text"></div>

      <div class="field" style="grid-column:span 12;"><label>Notas persona</label><textarea id="p_edit_persona_notas"></textarea></div>
      <div class="field" style="grid-column:span 12;"><label>Notas proveedor</label><textarea id="p_edit_notas"></textarea></div>

      <div class="field" style="grid-column:span 12;">
        <div class="card" style="background:var(--panel2);">
          <div class="panel-title" style="margin:0;">
            <div>
              <h3>Contactos</h3>
              <div class="sub">Teléfonos / Correos / Direcciones</div>
            </div>
          </div>

          <div class="tabs" data-tabs="proveedoresEdit" style="margin-top:12px;">
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
                  <button class="btn" type="button" id="btnAddTelEditP"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body"><div id="telListEditP" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailEditP"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body"><div id="mailListEditP" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirEditP"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body"><div id="dirListEditP" class="stack"></div></div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalProveedorEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnProveedorSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.proveedores = {
    datatable: "{{ route('proveedores.datatable') }}",
    store: "{{ route('proveedores.store') }}",
    show: "{{ route('proveedores.show', ['id' => '__ID__']) }}",
    update: "{{ route('proveedores.update', ['id' => '__ID__']) }}",
    baja: "{{ route('proveedores.baja', ['id' => '__ID__']) }}",

    contactos: "{{ route('proveedores.contactos', ['id' => '__ID__']) }}",
    telAdd: "{{ url('/proveedores/__ID__/telefonos') }}",
    mailAdd: "{{ url('/proveedores/__ID__/correos') }}",
    dirAdd: "{{ url('/proveedores/__ID__/direcciones') }}",

    contactoBaja: "{{ route('proveedores.contacto.baja', ['tipo'=>'__T__','cid'=>'__CID__']) }}",
    contactoReactivar: "{{ route('proveedores.contacto.reactivar', ['tipo'=>'__T__','cid'=>'__CID__']) }}",
      };
</script>
<script src="{{ asset('assets/js/proveedores.js') }}"></script>
@endpush