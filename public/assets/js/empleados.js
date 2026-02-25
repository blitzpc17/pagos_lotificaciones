$(function(){

  // ===== CSRF =====
  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  // ✅ MAYÚSCULAS (valor real)
  // - Convierte todo texto/textarea excepto emails.
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

  // ===== DataTable (Responsive) =====
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
  // CONTACTOS: plantillas filas
  // =========================
  function telRow(t = {}){
    const id = t.id || '';
    const etiqueta = t.etiqueta || 'principal';
    const telefono = t.telefono || '';
    const extension = t.extension || '';
    const pr = t.es_principal ? 'checked' : '';
    return `
      <div class="card tel-row" data-id="${id}" style="padding:12px;">
        <div class="grid">
          <div class="field" style="grid-column:span 3;">
            <label>Etiqueta</label>
            <input type="text" class="tel-et" value="${escapeHtml(etiqueta)}">
          </div>
          <div class="field" style="grid-column:span 4;">
            <label>Teléfono</label>
            <input type="tel" class="tel-num" value="${escapeHtml(telefono)}">
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>Ext</label>
            <input type="text" class="tel-ext" value="${escapeHtml(extension)}">
          </div>
          <div class="field" style="grid-column:span 2;">
            <label>Principal</label>
            <label style="display:flex;gap:10px;align-items:center;">
              <input type="checkbox" class="tel-principal" ${pr}>
              <span class="muted" style="margin:0;">Sí</span>
            </label>
          </div>
          <div class="field" style="grid-column:span 1;align-items:flex-end;">
            <button type="button" class="btn btnDelRow" data-kind="tel" title="Quitar">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
      </div>`;
  }

  function mailRow(c = {}){
    const id = c.id || '';
    const etiqueta = c.etiqueta || 'principal';
    const correo = c.correo || '';
    const pr = c.es_principal ? 'checked' : '';
    return `
      <div class="card mail-row" data-id="${id}" style="padding:12px;">
        <div class="grid">
          <div class="field" style="grid-column:span 4;">
            <label>Etiqueta</label>
            <input type="text" class="mail-et" value="${escapeHtml(etiqueta)}">
          </div>
          <div class="field" style="grid-column:span 6;">
            <label>Correo</label>
            <input type="email" class="mail-val" value="${escapeHtml(correo)}">
          </div>
          <div class="field" style="grid-column:span 1;">
            <label>Principal</label>
            <label style="display:flex;gap:10px;align-items:center;">
              <input type="checkbox" class="mail-principal" ${pr}>
              <span class="muted" style="margin:0;">Sí</span>
            </label>
          </div>
          <div class="field" style="grid-column:span 1;align-items:flex-end;">
            <button type="button" class="btn btnDelRow" data-kind="mail" title="Quitar">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
      </div>`;
  }

  function dirRow(d = {}){
    const id = d.id || '';
    const etiqueta = d.etiqueta || 'principal';
    const pr = d.es_principal ? 'checked' : '';
    return `
      <div class="card dir-row" data-id="${id}" style="padding:12px;">
        <div class="grid">
          <div class="field" style="grid-column:span 3;">
            <label>Etiqueta</label>
            <input type="text" class="dir-et" value="${escapeHtml(etiqueta)}">
          </div>
          <div class="field" style="grid-column:span 7;">
            <label>Calle</label>
            <input type="text" class="dir-calle" value="${escapeHtml(d.calle||'')}">
          </div>
          <div class="field" style="grid-column:span 1;">
            <label>Principal</label>
            <label style="display:flex;gap:10px;align-items:center;">
              <input type="checkbox" class="dir-principal" ${pr}>
              <span class="muted" style="margin:0;">Sí</span>
            </label>
          </div>
          <div class="field" style="grid-column:span 1;align-items:flex-end;">
            <button type="button" class="btn btnDelRow" data-kind="dir" title="Quitar">
              <i class="fa-solid fa-trash"></i>
            </button>
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

  function escapeHtml(s){
    return String(s ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  // ===== principal único por bloque =====
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

  // ===== agregar filas =====
  $('#btnAddTelAdd').on('click', ()=>$('#telListAdd').append(telRow({})));
  $('#btnAddMailAdd').on('click', ()=>$('#mailListAdd').append(mailRow({})));
  $('#btnAddDirAdd').on('click', ()=>$('#dirListAdd').append(dirRow({})));

  $('#btnAddTelEdit').on('click', ()=>$('#telListEdit').append(telRow({})));
  $('#btnAddMailEdit').on('click', ()=>$('#mailListEdit').append(mailRow({})));
  $('#btnAddDirEdit').on('click', ()=>$('#dirListEdit').append(dirRow({})));

  // ===== quitar filas (solo UI) =====
  $(document).on('click', '.btnDelRow', function(){
    $(this).closest('.card').remove();
  });

  // =========================
  // OPEN NEW
  // =========================
  $('#btnEmpleadoAdd').on('click', function(){
    // limpiar básicos
    $('#e_add_nombres,#e_add_apellido_paterno,#e_add_apellido_materno,#e_add_puesto_detalle,#e_add_observaciones').val('');
    $('#e_add_fecha_nacimiento').val('');
    $('#e_add_notas').val('');
    $('#e_add_puesto').val($('#e_add_puesto option:first').val());
    $('#e_add_numero_empleado').val('');
    $('#e_add_comision_default').val(0);
    $('#e_add_clave_vendedor').val('');

    // limpiar contactos y crear 1 fila por defecto (para que no se vea vacío)
    $('#telListAdd').html(telRow({es_principal:true}));
    $('#mailListAdd').html(mailRow({es_principal:true}));
    $('#dirListAdd').html(dirRow({es_principal:true}));

    toggleVendorBlock('add');

    openModal('#modalEmpleadoAdd');
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

      puesto: (isAdd ? $('#e_add_puesto').val() : $('#e_edit_puesto').val()) || '',
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

    // fallback: si no marcaron ninguno, marca el primero (para que backend no quede sin principal)
    if(payload.telefonos.length && !payload.telefonos.some(x=>x.es_principal===1)) payload.telefonos[0].es_principal = 1;
    if(payload.correos.length && !payload.correos.some(x=>x.es_principal===1)) payload.correos[0].es_principal = 1;
    if(payload.direcciones.length && !payload.direcciones.some(x=>x.es_principal===1)) payload.direcciones[0].es_principal = 1;

    return payload;
  }

  // =========================
  // SAVE ADD
  // =========================
  $('#btnEmpleadoSaveAdd').on('click', function(){
    const payload = buildPayload('add');

    $.ajax({
      url: '/empleados',
      type: 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      success: function(){
        closeModal('#modalEmpleadoAdd');
        dt.ajax.reload(null,false);
        Swal.fire({ icon:'success', title:'Guardado', text:'Empleado guardado correctamente.', timer:1200, showConfirmButton:false });
      },
      error: function(xhr){
        const res = xhr.responseJSON || {};
        const msg = res.message || 'Error al guardar';
        const e = res.errors ? Object.values(res.errors)[0]?.[0] : null;
        Swal.fire({ icon:'error', title: msg, text: e || 'Revisa datos.' });
      }
    });
  });

  // =========================
  // OPEN EDIT (cargar + reconstruir filas)
  // =========================
  $(document).on('click', '.btnEditEmpleado', function(){
    const id = $(this).data('id');
    $.get(`/empleados/${id}`, function(r){

      $('#e_edit_id').val(r.id);

      $('#e_edit_nombres').val(r.persona.nombres || '');
      $('#e_edit_apellido_paterno').val(r.persona.apellido_paterno || '');
      $('#e_edit_apellido_materno').val(r.persona.apellido_materno || '');
      $('#e_edit_fecha_nacimiento').val(r.persona.fecha_nacimiento || '');
      $('#e_edit_notas').val(r.persona.notas || '');

      $('#e_edit_puesto').val(r.puesto || 'OTRO');
      $('#e_edit_puesto_detalle').val(r.puesto_detalle || '');
      $('#e_edit_numero_empleado').val(r.numero_empleado || '');
      $('#e_edit_observaciones').val(r.observaciones || '');

      // vendedor
      const isVentas = (r.puesto === 'VENTAS');
      $('#e_edit_comision_default').val(r.vendedor?.comision_default ?? 0);
      $('#e_edit_clave_vendedor').val(r.vendedor?.clave ?? '');

      // contactos: reconstruir
      $('#telListEdit').html('');
      $('#mailListEdit').html('');
      $('#dirListEdit').html('');

      (r.telefonos || []).forEach(t => $('#telListEdit').append(telRow(t)));
      (r.correos || []).forEach(c => $('#mailListEdit').append(mailRow(c)));
      (r.direcciones || []).forEach(d => $('#dirListEdit').append(dirRow(d)));

      // si no vienen (vacíos), añade una fila base
      if(!r.telefonos || !r.telefonos.length) $('#telListEdit').append(telRow({es_principal:true}));
      if(!r.correos || !r.correos.length) $('#mailListEdit').append(mailRow({es_principal:true}));
      if(!r.direcciones || !r.direcciones.length) $('#dirListEdit').append(dirRow({es_principal:true}));

      toggleVendorBlock('edit');

      openModal('#modalEmpleadoEdit');
    });
  });

  // =========================
  // SAVE EDIT
  // =========================
  $('#btnEmpleadoSaveEdit').on('click', function(){
    const id = $('#e_edit_id').val();
    const payload = buildPayload('edit');

    $.ajax({
      url: `/empleados/${id}`,
      type: 'PUT',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      success: function(){
        closeModal('#modalEmpleadoEdit');
        dt.ajax.reload(null,false);
        Swal.fire({ icon:'success', title:'Actualizado', text:'Cambios guardados.', timer:1200, showConfirmButton:false });
      },
      error: function(xhr){
        const res = xhr.responseJSON || {};
        const msg = res.message || 'Error al actualizar';
        const e = res.errors ? Object.values(res.errors)[0]?.[0] : null;
        Swal.fire({ icon:'error', title: msg, text: e || 'Revisa datos.' });
      }
    });
  });

  // =========================
  // BAJA
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
    }).then((r)=>{
      if(!r.isConfirmed) return;
      $.post(`/empleados/${id}/baja`, function(){
        dt.ajax.reload(null,false);
        Swal.fire({ icon:'success', title:'Listo', text:'Empleado dado de baja.', timer:1200, showConfirmButton:false });
      }).fail(()=>{
        Swal.fire({ icon:'error', title:'Error', text:'No se pudo dar de baja.' });
      });
    });
  });

});
