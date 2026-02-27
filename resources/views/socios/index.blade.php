@extends('layouts.app')
@section('title','Socios')

@section('content')
<div class="breadcrumb">
  <div class="path"><span>Inicio</span><span>›</span><b>Socios</b></div>
  <div class="actions">
    <button class="btn primary" id="btnSocioAdd"><i class="fa-solid fa-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card">
  <div class="panel-title">
    <div>
      <h3>Socios</h3>
      <div class="sub">Catálogo + color identificador + contactos múltiples</div>
    </div>
    <span class="chip">SOCIOS</span>
  </div>

  <table id="tblSocios" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Color</th>
        <th>Estatus</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- MODAL ADD -->
<div class="modal" id="modalSocioAdd">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-solid fa-users"></i> Nuevo Socio</b>
      <button class="close" type="button" data-close="#modalSocioAdd"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">

      <div class="field" style="grid-column:span 8;">
        <label>Nombre</label>
        <input id="s_add_nombre" type="text" placeholder="NOMBRE DEL SOCIO"/>
      </div>

      <div class="field" style="grid-column:span 4;">
        <label>Color</label>
        <div style="display:flex; gap:10px; align-items:center;">
          <input id="s_add_color_picker" type="color" value="#2D6CDF"
            style="width:54px; height:44px; padding:0; border-radius:14px; border:1px solid var(--border); background:var(--input-bg);" />
          <input id="s_add_color_hex" type="text" placeholder="#2D6CDF" value="#2D6CDF"/>
        </div>
        <div class="muted">Puedes escoger o escribir el HEX.</div>
      </div>

      <div class="field" style="grid-column: span 12;">
        <div class="badge" style="justify-content:space-between; width:100%;">
          <span><i class="fa-solid fa-palette"></i> Preview</span>
          <span style="display:flex; align-items:center; gap:10px;">
            <span id="s_add_color_preview" style="width:20px;height:20px;border-radius:6px;border:1px solid rgba(0,0,0,.2); background:#2D6CDF;"></span>
            <b id="s_add_color_preview_text">#2D6CDF</b>
          </span>
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

          <div class="tabs" data-tabs="sociosAdd" style="margin-top:12px;">
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
                  <button class="btn" type="button" id="btnAddTelAddS"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body"><div id="telListAddS" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailAddS"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body"><div id="mailListAddS" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirAddS"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body"><div id="dirListAddS" class="stack"></div></div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <!-- /CONTACTOS -->

    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalSocioAdd"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnSocioSaveAdd"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="modalSocioEdit">
  <div class="box">
    <div class="mhead">
      <b><i class="fa-regular fa-pen-to-square"></i> Editar Socio</b>
      <button class="close" type="button" data-close="#modalSocioEdit"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mbody">
      <input type="hidden" id="s_edit_id"/>

      <div class="field" style="grid-column:span 8;">
        <label>Nombre</label>
        <input id="s_edit_nombre" type="text"/>
      </div>

      <div class="field" style="grid-column:span 4;">
        <label>Color</label>
        <div style="display:flex; gap:10px; align-items:center;">
          <input id="s_edit_color_picker" type="color" value="#2D6CDF"
            style="width:54px; height:44px; padding:0; border-radius:14px; border:1px solid var(--border); background:var(--input-bg);" />
          <input id="s_edit_color_hex" type="text" placeholder="#2D6CDF" value="#2D6CDF"/>
        </div>
      </div>

      <div class="field" style="grid-column: span 12;">
        <div class="badge" style="justify-content:space-between; width:100%;">
          <span><i class="fa-solid fa-palette"></i> Preview</span>
          <span style="display:flex; align-items:center; gap:10px;">
            <span id="s_edit_color_preview" style="width:20px;height:20px;border-radius:6px;border:1px solid rgba(0,0,0,.2); background:#2D6CDF;"></span>
            <b id="s_edit_color_preview_text">#2D6CDF</b>
          </span>
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

          <div class="tabs" data-tabs="sociosEdit" style="margin-top:12px;">
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
                  <button class="btn" type="button" id="btnAddTelEditS"><i class="fa-solid fa-plus"></i> Agregar teléfono</button>
                </div>
                <div class="tab-panel-body"><div id="telListEditS" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="mail">
                <div class="tab-panel-head">
                  <div class="left"><b>Correos</b><span>Agrega varios y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddMailEditS"><i class="fa-solid fa-plus"></i> Agregar correo</button>
                </div>
                <div class="tab-panel-body"><div id="mailListEditS" class="stack"></div></div>
              </div>

              <div class="tab-panel" data-panel="dir">
                <div class="tab-panel-head">
                  <div class="left"><b>Direcciones</b><span>Agrega varias y marca principal</span></div>
                  <button class="btn" type="button" id="btnAddDirEditS"><i class="fa-solid fa-plus"></i> Agregar dirección</button>
                </div>
                <div class="tab-panel-body"><div id="dirListEditS" class="stack"></div></div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <!-- /CONTACTOS -->

    </div>

    <div class="mfoot">
      <button class="btn" type="button" data-close="#modalSocioEdit"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
      <button class="btn primary" type="button" id="btnSocioSaveEdit"><i class="fa-regular fa-floppy-disk"></i> Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.ROUTES = window.ROUTES || {};
  window.ROUTES.socios = {
    datatable: "{{ route('socios.datatable') }}",
    store: "{{ route('socios.store') }}",
    show: "{{ url('/socios/__ID__') }}",
    update: "{{ url('/socios/__ID__') }}",
    baja: "{{ url('/socios/__ID__/baja') }}",

    // ✅ endpoints centralizados (PersonaContactosController)
    telAdd: "{{ url('/socios/__ID__/telefonos') }}",
    mailAdd: "{{ url('/socios/__ID__/correos') }}",
    dirAdd: "{{ url('/socios/__ID__/direcciones') }}",

    // ✅ baja/reactivar contacto (motivo requerido en baja)
    contactoBaja: "{{ route('socios.contacto.baja', ['tipo' => '__T__', 'cid' => '__CID__']) }}",
    contactoReactivar: "{{ route('socios.contacto.reactivar', ['tipo' => '__T__', 'cid' => '__CID__']) }}"
  };
</script>
<script src="{{ asset('assets/js/socios.js') }}"></script>
@endpush