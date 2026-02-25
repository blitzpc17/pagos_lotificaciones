@extends('layouts.app')
@section('title','Clientes')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Clientes</b></div>
  <div class="actions">
    <button class="btn primary" id="btnClienteAdd"><i class="fa-solid fa-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Clientes</h3>
      <div class="sub">Persona + Cliente + contactos múltiples</div>
    </div>
    <span class="chip">CRM</span>
  </div>

  <table id="tblClientes" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>RFC</th>
        <th>Tipo</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- =========================
     MODAL ADD
========================= -->
<div class="modal" id="modalClienteAdd">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-user-plus"></i> Nuevo Cliente</b>
      <button class="close" type="button" data-close="#modalClienteAdd"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field"><label>Nombres</label><input id="c_add_nombres" type="text"></div>
      <div class="field"><label>Apellido paterno</label><input id="c_add_apellido_paterno" type="text"></div>
      <div class="field"><label>Apellido materno</label><input id="c_add_apellido_materno" type="text"></div>
      <div class="field"><label>Fecha nacimiento</label><input id="c_add_fecha_nacimiento" type="date"></div>

      <div class="field"><label>RFC</label><input id="c_add_rfc" type="text"></div>
      <div class="field"><label>Tipo</label><input id="c_add_tipo_cliente" type="text" value="GENERAL"></div>

      <div class="field" style="grid-column:span 12;"><label>Notas persona</label><textarea id="c_add_notas"></textarea></div>
      <div class="field" style="grid-column:span 12;"><label>Notas cliente</label><textarea id="c_add_notas_cliente"></textarea></div>

      <div class="field" style="grid-column:span 12;">
        <div class="card" style="background:var(--panel2);">
          <div class="panel-title" style="margin:0;">
            <div>
              <h3>Contactos</h3>
              <div class="sub">Teléfonos / Correos / Direcciones</div>
            </div>
          </div>

          <!-- ✅ TABS -->
          <div class="tabs" data-tabs="clientesAdd" style="margin-top:12px;">
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
                  <button class="btn" type="button" id="btnAddTelAddC"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body">
                  <div id="telListAddC" class="stack"></div>
                </div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailAddC"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body">
                  <div id="mailListAddC" class="stack"></div>
                </div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirAddC"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body">
                  <div id="dirListAddC" class="stack"></div>
                </div>
              </div>
            </div>
          </div>
          <!-- ✅ /TABS -->

        </div>
      </div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalClienteAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnClienteSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- =========================
     MODAL EDIT
========================= -->
<div class="modal" id="modalClienteEdit">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-regular fa-pen-to-square"></i> Editar Cliente</b>
      <button class="close" type="button" data-close="#modalClienteEdit"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <div class="field"><label>ID</label><input id="c_edit_id" type="text" disabled></div>
      <div class="field"><label>Nombres</label><input id="c_edit_nombres" type="text"></div>
      <div class="field"><label>Apellido paterno</label><input id="c_edit_apellido_paterno" type="text"></div>
      <div class="field"><label>Apellido materno</label><input id="c_edit_apellido_materno" type="text"></div>
      <div class="field"><label>Fecha nacimiento</label><input id="c_edit_fecha_nacimiento" type="date"></div>

      <div class="field"><label>RFC</label><input id="c_edit_rfc" type="text"></div>
      <div class="field"><label>Tipo</label><input id="c_edit_tipo_cliente" type="text"></div>

      <div class="field" style="grid-column:span 12;"><label>Notas persona</label><textarea id="c_edit_notas"></textarea></div>
      <div class="field" style="grid-column:span 12;"><label>Notas cliente</label><textarea id="c_edit_notas_cliente"></textarea></div>

      <div class="field" style="grid-column:span 12;">
        <div class="card" style="background:var(--panel2);">
          <div class="panel-title" style="margin:0;">
            <div>
              <h3>Contactos</h3>
              <div class="sub">Teléfonos / Correos / Direcciones</div>
            </div>
          </div>

          <!-- ✅ TABS -->
          <div class="tabs" data-tabs="clientesEdit" style="margin-top:12px;">
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
                  <button class="btn" type="button" id="btnAddTelEditC"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body">
                  <div id="telListEditC" class="stack"></div>
                </div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailEditC"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body">
                  <div id="mailListEditC" class="stack"></div>
                </div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirEditC"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body">
                  <div id="dirListEditC" class="stack"></div>
                </div>
              </div>
            </div>
          </div>
          <!-- ✅ /TABS -->

        </div>
      </div>
    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalClienteEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnClienteSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.clientes = {
    datatable: "{{ route('clientes.datatable') }}",
    store: "{{ route('clientes.store') }}",
    show: "{{ route('clientes.show', ['id' => '__ID__']) }}",
    update: "{{ route('clientes.update', ['id' => '__ID__']) }}",
    baja: "{{ route('clientes.baja', ['id' => '__ID__']) }}",

    contactos: "{{ route('clientes.contactos', ['id' => '__ID__']) }}",
    addTel: "{{ route('clientes.tel.add', ['id' => '__ID__']) }}",
    addMail: "{{ route('clientes.mail.add', ['id' => '__ID__']) }}",
    addDir: "{{ route('clientes.dir.add', ['id' => '__ID__']) }}",
    bajaContacto: "{{ route('clientes.contacto.baja', ['tipo' => '__T__', 'cid' => '__CID__']) }}"
  };
</script>
<script src="{{ asset('assets/js/clientes.js') }}"></script>
@endpush