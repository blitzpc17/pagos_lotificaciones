$(function(){

  // ===== CSRF =====
  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  // ✅ MAYÚSCULAS (texto)
  $(document).on('input', 'input[type="text"], input[type="search"], input[type="tel"], textarea', function(){
    this.value = (this.value || '').toUpperCase();
  });

  // ===== Helpers modals =====
  function openModal(sel){ $(sel).fadeIn(120); }
  function closeModal(sel){ $(sel).fadeOut(120); }

  $(document).on('click', '[data-close]', function(){
    closeModal($(this).data('close'));
  });

  $('.modal').on('click', function(e){
    if(e.target === this) $(this).fadeOut(120);
  });

  // ===== DataTable =====
  const dt = $('#tblEmpleados').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias"
    },
    ajax: { url: '/empleados/datatable', type: 'GET' },
    columns: [
      {data:'id', name:'id'},
      {data:'nombre', name:'nombre'},
      {data:'puesto', name:'puesto'},
      {data:'numero_empleado', name:'numero_empleado'},
      {data:'estatus', name:'estatus', orderable:false, searchable:false},
      {data:'acciones', name:'acciones', orderable:false, searchable:false, className:'dt-body-right'}
    ]
  });

  // =========================
  // Helpers comunes
  // =========================
  const R = window.ROUTES?.empleados || {};
  const urlWithId = (tpl, id)=> (tpl || '').replace('__ID__', id);

  const postJson = (url, body)=> $.ajax({
    url, type:'POST', data: JSON.stringify(body), contentType:'application/json'
  });

  function escapeHtml(s){
    return String(s ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function toastOk(msg){
    Swal.fire({ icon:'success', title: msg || 'Listo', timer:1200, showConfirmButton:false });
  }
  function toastErr(xhr, fallback){
    Swal.fire({ icon:'error', title: (xhr?.responseJSON?.message || fallback || 'Error') });
  }

  async function pedirMotivoBaja(titulo){
    const r = await Swal.fire({
      title: titulo || 'Motivo de baja',
      input: 'textarea',
      inputPlaceholder: 'Escribe el motivo…',
      showCancelButton: true,
      confirmButtonText: 'Confirmar baja',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      inputValidator: (v)=>{
        if(!v || String(v).trim().length < 3) return 'Motivo obligatorio (mínimo 3 caracteres).';
      }
    });
    return r.isConfirmed ? String(r.value).trim() : null;
  }

  function urlContactoBaja(tipo, cid){
    const tpl = R.contactoBaja;
    return tpl ? tpl.replace('__T__', tipo).replace('__CID__', cid) : `/empleados/contacto/${tipo}/${cid}/baja`;
  }
  function urlContactoReactivar(tipo, cid){
    const tpl = R.contactoReactivar;
    return tpl ? tpl.replace('__T__', tipo).replace('__CID__', cid) : `/empleados/contacto/${tipo}/${cid}/reactivar`;
  }

  // =========================
  //  VENDEDOR: toggle por puesto
  // =========================
  function toggleVendorBlock(mode){
    const puesto = (mode === 'add' ? $('#e_add_puesto').val() : $('#e_edit_puesto').val());
    const isVentas = (puesto === 'VENTAS');

    if(mode === 'add'){
      $('#vendorBlockAdd').toggle(isVentas);
      if(!isVentas){
        $('#e_add_comision_default').val(0);
        $('#e_add_clave_vendedor').val('');
      }
    }else{
      $('#vendorBlockEdit').toggle(isVentas);
      if(!isVentas){
        $('#e_edit_comision_default').val(0);
        $('#e_edit_clave_vendedor').val('');
      }
    }
  }

  $('#e_add_puesto').on('change', ()=>toggleVendorBlock('add'));
  $('#e_edit_puesto').on('change', ()=>toggleVendorBlock('edit'));

  // =========================
  // CONTACTOS: templates + acciones
  // =========================
  function actionBtn(kind, obj){
    const isBaja = !!obj.baja;
    const icon = isBaja ? 'fa-rotate-left' : 'fa-trash';
    const title = isBaja ? 'Reactivar' : 'Dar de baja';
    const motivo = isBaja ? `<div class="muted" style="margin-top:6px;font-size:12px;">Motivo: ${escapeHtml(obj.baja_motivo||'—')}</div>` : '';
    return `
      <button type="button" class="btn btnContactAction" data-kind="${kind}" data-baja="${isBaja?1:0}" title="${title}">
        <i class="fa-solid ${icon}"></i>
      </button>
      ${motivo}
    `;
  }

  function telRow(t = {}){
    return `
      <div class="card tel-row" data-id="${t.id||''}" style="padding:12px;">
        <div class="grid">
          <div class="field" style="grid-column:span 3;">
            <label>Etiqueta</label>
            <input type="text" class="tel-et" value="${escapeHtml(t.etiqueta||'principal')}">
          </div>
          <div class="field" style="grid-column:span 4;">
            <label>Teléfono</label>
            <input type="tel" class="tel-num" value="${escapeHtml(t.telefono||'')}">
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>Ext</label>
            <input type="text" class="tel-ext" value="${escapeHtml(t.extension||'')}">
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>Principal</label>
            <label style="display:flex;gap:10px;align-items:center;">
              <input type="checkbox" class="tel-principal" ${t.es_principal ? 'checked':''}>
              <span class="muted" style="margin:0;">Sí</span>
            </label>
          </div>
          <div class="field" style="grid-column:span 1;align-items:flex-end;">
            ${actionBtn('tel', t)}
          </div>
        </div>
      </div>`;
  }

  function mailRow(c = {}){
    return `
      <div class="card mail-row" data-id="${c.id||''}" style="padding:12px;">
        <div class="grid">
          <div class="field" style="grid-column:span 4;">
            <label>Etiqueta</label>
            <input type="text" class="mail-et" value="${escapeHtml(c.etiqueta||'principal')}">
          </div>
          <div class="field" style="grid-column:span 6;">
            <label>Correo</label>
            <input type="email" class="mail-val" value="${escapeHtml(c.correo||'')}">
          </div>
          <div class="field" style="grid-column:span 1;">
            <label>Principal</label>
            <label style="display:flex;gap:10px;align-items:center;">
              <input type="checkbox" class="mail-principal" ${c.es_principal ? 'checked':''}>
              <span class="muted" style="margin:0;">Sí</span>
            </label>
          </div>
          <div class="field" style="grid-column:span 1;align-items:flex-end;">
            ${actionBtn('mail', c)}
          </div>
        </div>
      </div>`;
  }

  function dirRow(d = {}){
    return `
      <div class="card dir-row" data-id="${d.id||''}" style="padding:12px;">
        <div class="grid">
          <div class="field" style="grid-column:span 3;">
            <label>Etiqueta</label>
            <input type="text" class="dir-et" value="${escapeHtml(d.etiqueta||'principal')}">
          </div>
          <div class="field" style="grid-column:span 7;">
            <label>Calle</label>
            <input type="text" class="dir-calle" value="${escapeHtml(d.calle||'')}">
          </div>
          <div class="field" style="grid-column:span 1;">
            <label>Principal</label>
            <label style="display:flex;gap:10px;align-items:center;">
              <input type="checkbox" class="dir-principal" ${d.es_principal ? 'checked':''}>
              <span class="muted" style="margin:0;">Sí</span>
            </label>
          </div>
          <div class="field" style="grid-column:span 1;align-items:flex-end;">
            ${actionBtn('dir', d)}
          </div>

          <div class="field" style="grid-column:span 3;"><label>No. Ext</label><input type="text" class="dir-ext" value="${escapeHtml(d.numero_ext||'')}"></div>
          <div class="field" style="grid-column:span 3;"><label>No. Int</label><input type="text" class="dir-int" value="${escapeHtml(d.numero_int||'')}"></div>
          <div class="field" style="grid-column:span 3;"><label>Colonia</label><input type="text" class="dir-col" value="${escapeHtml(d.colonia||'')}"></div>
          <div class="field" style="grid-column:span 3;"><label>CP</label><input type="text" class="dir-cp" value="${escapeHtml(d.cp||'')}"></div>

          <div class="field" style="grid-column:span 4;"><label>Municipio</label><input type="text" class="dir-mun" value="${escapeHtml(d.municipio||'')}"></div>
          <div class="field" style="grid-column:span 4;"><label>Estado</label><input type="text" class="dir-edo" value="${escapeHtml(d.estado||'')}"></div>
          <div class="field" style="grid-column:span 4;"><label>Referencias</label><input type="text" class="dir-ref" value="${escapeHtml(d.referencias||'')}"></div>
        </div>
      </div>`;
  }

  // principal único
  function enforceSinglePrincipal(containerSel, checkboxSel){
    $(document).on('change', containerSel+' '+checkboxSel, function(){
      if(this.checked){
        $(containerSel+' '+checkboxSel).not(this).prop('checked', false);
      }
    });
  }
  enforceSinglePrincipal('#telListAdd',  '.tel-principal');
  enforceSinglePrincipal('#telListEdit', '.tel-principal');
  enforceSinglePrincipal('#mailListAdd', '.mail-principal');
  enforceSinglePrincipal('#mailListEdit','.mail-principal');
  enforceSinglePrincipal('#dirListAdd',  '.dir-principal');
  enforceSinglePrincipal('#dirListEdit', '.dir-principal');

  // Agregar filas
  $('#btnAddTelAdd').on('click', ()=>$('#telListAdd').append(telRow({})));
  $('#btnAddMailAdd').on('click', ()=>$('#mailListAdd').append(mailRow({})));
  $('#btnAddDirAdd').on('click', ()=>$('#dirListAdd').append(dirRow({})));

  $('#btnAddTelEdit').on('click', ()=>$('#telListEdit').append(telRow({})));
  $('#btnAddMailEdit').on('click', ()=>$('#mailListEdit').append(mailRow({})));
  $('#btnAddDirEdit').on('click', ()=>$('#dirListEdit').append(dirRow({})));

  // Click contacto: si no hay id => remove; si hay => baja/reactivar
  $(document).on('click', '.btnContactAction', async function(){
    const kind = $(this).data('kind'); // tel|mail|dir
    const isBaja = String($(this).data('baja')) === '1';
    const $card = $(this).closest('.card');
    const cid = $card.data('id');

    if(!cid){ $card.remove(); return; }

    if(isBaja){
      const url = urlContactoReactivar(kind, cid);
      $.post(url, {})
        .done(()=>{ refreshContactosEdit(); toastOk('Contacto reactivado'); })
        .fail((xhr)=> toastErr(xhr,'No se pudo reactivar'));
    }else{
      const motivo = await pedirMotivoBaja('Motivo de baja del contacto');
      if(!motivo) return;
      const url = urlContactoBaja(kind, cid);
      $.post(url, {motivo})
        .done(()=>{ refreshContactosEdit(); toastOk('Contacto dado de baja'); })
        .fail((xhr)=> toastErr(xhr,'No se pudo dar de baja'));
    }
  });

  // =========================
  // BUILD PAYLOAD
  // =========================
  function buildPayload(mode){
    const isAdd = mode === 'add';

    const payload = {
      nombres: (isAdd ? $('#e_add_nombres').val() : $('#e_edit_nombres').val()) || '',
      apellido_paterno: (isAdd ? $('#e_add_apellido_paterno').val() : $('#e_edit_apellido_paterno').val()) || '',
      apellido_materno: (isAdd ? $('#e_add_apellido_materno').val() : $('#e_edit_apellido_materno').val()) || null,
      fecha_nacimiento: (isAdd ? $('#e_add_fecha_nacimiento').val() : $('#e_edit_fecha_nacimiento').val()) || null,
      notas: (isAdd ? $('#e_add_notas').val() : $('#e_edit_notas').val()) || null,

      puesto: (isAdd ? $('#e_add_puesto').val() : $('#e_edit_puesto').val()) || 'OTRO',
      puesto_detalle: (isAdd ? $('#e_add_puesto_detalle').val() : $('#e_edit_puesto_detalle').val()) || null,
      observaciones: (isAdd ? $('#e_add_observaciones').val() : $('#e_edit_observaciones').val()) || null,

      comision_default: parseFloat(isAdd ? $('#e_add_comision_default').val() : $('#e_edit_comision_default').val()) || 0,

      telefonos: [],
      correos: [],
      direcciones: [],
    };

    const telList = isAdd ? '#telListAdd' : '#telListEdit';
    const mailList = isAdd ? '#mailListAdd' : '#mailListEdit';
    const dirList = isAdd ? '#dirListAdd' : '#dirListEdit';

    $(telList+' .tel-row').each(function(){
      const $r = $(this);
      const tel = ($r.find('.tel-num').val() || '').trim();
      if(!tel) return;
      payload.telefonos.push({
        id: $r.data('id') || null,
        etiqueta: ($r.find('.tel-et').val() || 'principal').trim(),
        telefono: tel,
        extension: ($r.find('.tel-ext').val() || '').trim() || null,
        es_principal: $r.find('.tel-principal').is(':checked') ? 1 : 0
      });
    });

    $(mailList+' .mail-row').each(function(){
      const $r = $(this);
      const mail = ($r.find('.mail-val').val() || '').trim();
      if(!mail) return;
      payload.correos.push({
        id: $r.data('id') || null,
        etiqueta: ($r.find('.mail-et').val() || 'principal').trim(),
        correo: mail,
        es_principal: $r.find('.mail-principal').is(':checked') ? 1 : 0
      });
    });

    $(dirList+' .dir-row').each(function(){
      const $r = $(this);
      const calle = ($r.find('.dir-calle').val() || '').trim();
      const cp = ($r.find('.dir-cp').val() || '').trim();
      if(!calle && !cp) return;
      payload.direcciones.push({
        id: $r.data('id') || null,
        etiqueta: ($r.find('.dir-et').val() || 'principal').trim(),
        calle: calle || null,
        numero_ext: ($r.find('.dir-ext').val() || '').trim() || null,
        numero_int: ($r.find('.dir-int').val() || '').trim() || null,
        colonia: ($r.find('.dir-col').val() || '').trim() || null,
        municipio: ($r.find('.dir-mun').val() || '').trim() || null,
        estado: ($r.find('.dir-edo').val() || '').trim() || null,
        cp: cp || null,
        referencias: ($r.find('.dir-ref').val() || '').trim() || null,
        es_principal: $r.find('.dir-principal').is(':checked') ? 1 : 0
      });
    });

    if(payload.telefonos.length && !payload.telefonos.some(x=>x.es_principal===1)) payload.telefonos[0].es_principal = 1;
    if(payload.correos.length && !payload.correos.some(x=>x.es_principal===1)) payload.correos[0].es_principal = 1;
    if(payload.direcciones.length && !payload.direcciones.some(x=>x.es_principal===1)) payload.direcciones[0].es_principal = 1;

    return payload;
  }

  // UPSERT contactos por PersonaContactosController
  async function upsertContactosEmpleado(empId, payload){
    for(const t of (payload.telefonos||[])){
      await postJson(urlWithId(R.telAdd, empId), {
        id: t.id || null,
        etiqueta: t.etiqueta,
        telefono: t.telefono,
        extension: t.extension,
        es_principal: !!t.es_principal
      });
    }
    for(const c of (payload.correos||[])){
      await postJson(urlWithId(R.mailAdd, empId), {
        id: c.id || null,
        etiqueta: c.etiqueta,
        correo: c.correo,
        es_principal: !!c.es_principal
      });
    }
    for(const d of (payload.direcciones||[])){
      await postJson(urlWithId(R.dirAdd, empId), {
        id: d.id || null,
        etiqueta: d.etiqueta,
        calle: d.calle,
        numero_ext: d.numero_ext,
        numero_int: d.numero_int,
        colonia: d.colonia,
        municipio: d.municipio,
        estado: d.estado,
        cp: d.cp,
        referencias: d.referencias,
        es_principal: !!d.es_principal
      });
    }
  }

  // refrescar contactos en edit desde show()
  function refreshContactosEdit(){
    const id = $('#e_edit_id').val();
    if(!id) return;

    $.get(`/empleados/${id}`, function(r){
      $('#telListEdit').html('');
      $('#mailListEdit').html('');
      $('#dirListEdit').html('');

      (r.telefonos || []).forEach(t => $('#telListEdit').append(telRow(t)));
      (r.correos || []).forEach(c => $('#mailListEdit').append(mailRow(c)));
      (r.direcciones || []).forEach(d => $('#dirListEdit').append(dirRow(d)));

      if(!r.telefonos || !r.telefonos.length) $('#telListEdit').append(telRow({es_principal:true}));
      if(!r.correos || !r.correos.length) $('#mailListEdit').append(mailRow({es_principal:true}));
      if(!r.direcciones || !r.direcciones.length) $('#dirListEdit').append(dirRow({es_principal:true}));
    });
  }

  // =========================
  // OPEN NEW
  // =========================
  $('#btnEmpleadoAdd').on('click', function(){
    $('#e_add_nombres,#e_add_apellido_paterno,#e_add_apellido_materno,#e_add_puesto_detalle,#e_add_observaciones').val('');
    $('#e_add_fecha_nacimiento').val('');
    $('#e_add_notas').val('');
    $('#e_add_puesto').val($('#e_add_puesto option:first').val());
    $('#e_add_numero_empleado').val('');
    $('#e_add_comision_default').val(0);
    $('#e_add_clave_vendedor').val('');

    $('#telListAdd').html(telRow({es_principal:true}));
    $('#mailListAdd').html(mailRow({es_principal:true}));
    $('#dirListAdd').html(dirRow({es_principal:true}));

    toggleVendorBlock('add');
    openModal('#modalEmpleadoAdd');
  });

  // =========================
  // SAVE ADD (Opción A)
  // =========================
  $('#btnEmpleadoSaveAdd').on('click', async function(){
    const payload = buildPayload('add');

    // base SIN arrays (Opción A)
    const base = {
      nombres: payload.nombres,
      apellido_paterno: payload.apellido_paterno,
      apellido_materno: payload.apellido_materno,
      fecha_nacimiento: payload.fecha_nacimiento,
      notas: payload.notas,

      puesto: payload.puesto,
      puesto_detalle: payload.puesto_detalle,
      observaciones: payload.observaciones,

      comision_default: payload.comision_default
    };

    try{
      const res = await $.ajax({
        url: '/empleados',
        type: 'POST',
        data: JSON.stringify(base),
        contentType: 'application/json'
      });

      const empId = res?.id;
      if(!empId) throw new Error('No regresó id del empleado');

      await upsertContactosEmpleado(empId, payload);

      closeModal('#modalEmpleadoAdd');
      dt.ajax.reload(null,false);
      Swal.fire({ icon:'success', title:'Guardado', text:'Empleado guardado correctamente.', timer:1200, showConfirmButton:false });
    }catch(xhr){
      const res = xhr?.responseJSON || {};
      const msg = res.message || 'Error al guardar';
      const e = res.errors ? Object.values(res.errors)[0]?.[0] : null;
      Swal.fire({ icon:'error', title: msg, text: e || 'Revisa datos.' });
    }
  });

  // =========================
  // OPEN EDIT
  // =========================
  $(document).on('click', '.btnEditEmpleado', function(){
    const id = $(this).data('id');
    $.get(`/empleados/${id}`, function(r){

      $('#e_edit_id').val(r.id);

      $('#e_edit_nombres').val(r.persona?.nombres || '');
      $('#e_edit_apellido_paterno').val(r.persona?.apellido_paterno || '');
      $('#e_edit_apellido_materno').val(r.persona?.apellido_materno || '');
      $('#e_edit_fecha_nacimiento').val(r.persona?.fecha_nacimiento || '');
      $('#e_edit_notas').val(r.persona?.notas || '');

      $('#e_edit_puesto').val(r.puesto || 'OTRO');
      $('#e_edit_puesto_detalle').val(r.puesto_detalle || '');
      $('#e_edit_numero_empleado').val(r.numero_empleado || '');
      $('#e_edit_observaciones').val(r.observaciones || '');

      $('#e_edit_comision_default').val(r.vendedor?.comision_default ?? 0);
      $('#e_edit_clave_vendedor').val(r.vendedor?.clave ?? '');

      $('#telListEdit').html('');
      $('#mailListEdit').html('');
      $('#dirListEdit').html('');

      (r.telefonos || []).forEach(t => $('#telListEdit').append(telRow(t)));
      (r.correos || []).forEach(c => $('#mailListEdit').append(mailRow(c)));
      (r.direcciones || []).forEach(d => $('#dirListEdit').append(dirRow(d)));

      if(!r.telefonos || !r.telefonos.length) $('#telListEdit').append(telRow({es_principal:true}));
      if(!r.correos || !r.correos.length) $('#mailListEdit').append(mailRow({es_principal:true}));
      if(!r.direcciones || !r.direcciones.length) $('#dirListEdit').append(dirRow({es_principal:true}));

      toggleVendorBlock('edit');
      openModal('#modalEmpleadoEdit');
    });
  });

  // =========================
  // SAVE EDIT (Opción A)
  // =========================
  $('#btnEmpleadoSaveEdit').on('click', async function(){
    const id = $('#e_edit_id').val();
    const payload = buildPayload('edit');

    const base = {
      nombres: payload.nombres,
      apellido_paterno: payload.apellido_paterno,
      apellido_materno: payload.apellido_materno,
      fecha_nacimiento: payload.fecha_nacimiento,
      notas: payload.notas,

      puesto: payload.puesto,
      puesto_detalle: payload.puesto_detalle,
      observaciones: payload.observaciones,

      comision_default: payload.comision_default
    };

    try{
      await $.ajax({
        url: `/empleados/${id}`,
        type: 'PUT',
        data: JSON.stringify(base),
        contentType: 'application/json'
      });

      await upsertContactosEmpleado(id, payload);

      refreshContactosEdit();
      closeModal('#modalEmpleadoEdit');
      dt.ajax.reload(null,false);

      Swal.fire({ icon:'success', title:'Actualizado', text:'Cambios guardados.', timer:1200, showConfirmButton:false });
    }catch(xhr){
      const res = xhr?.responseJSON || {};
      const msg = res.message || 'Error al actualizar';
      const e = res.errors ? Object.values(res.errors)[0]?.[0] : null;
      Swal.fire({ icon:'error', title: msg, text: e || 'Revisa datos.' });
    }
  });

  // =========================
  // BAJA EMPLEADO
  // =========================
  $(document).on('click', '.btnBajaEmpleado', function(){
    const id = $(this).data('id');
    Swal.fire({
      icon:'warning',
      title:'¿Dar de baja?',
      text:'Esto dará de baja al empleado (baja lógica).',
      showCancelButton:true,
      confirmButtonText:'Sí, dar de baja',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#D9042B'
    }).then(async (r)=>{
      if(!r.isConfirmed) return;

      const motivo = await pedirMotivoBaja('Motivo de baja del empleado');
      if(!motivo) return;

      $.post(`/empleados/${id}/baja`, {motivo})
        .done(()=>{ dt.ajax.reload(null,false); toastOk('Empleado dado de baja'); })
        .fail((xhr)=> toastErr(xhr,'No se pudo dar de baja'));
    });
  });

});