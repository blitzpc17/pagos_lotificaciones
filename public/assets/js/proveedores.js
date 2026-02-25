$(function(){
  $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const R = (window.ROUTES && window.ROUTES.proveedores) ? window.ROUTES.proveedores : null;
  if(!R){ console.error('ROUTES.proveedores no definido'); return; }

  const urlWithId = (tpl, id)=> (tpl||'').replace('__ID__', id);

  function openModal(sel){ $(sel).fadeIn(120); }
  function closeModal(sel){ $(sel).fadeOut(120); }

  $(document).on('click','[data-close]',function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target===this) $(this).fadeOut(120); });

  const dt = window.initCmsDataTable('#tblProveedores', {
    processing:true,
    serverSide:true,
    ajax:{ url:R.datatable, type:'GET' },
    columns:[
      {data:'id', name:'id'},
      {data:'nombre', name:'nombre'},
      {data:'rfc', name:'rfc'},
      {data:'razon_social', name:'razon_social'},
      {data:'estatus', name:'estatus', orderable:false, searchable:false},
      {data:'acciones', name:'acciones', orderable:false, searchable:false, className:'dt-body-right'}
    ]
  });

  function esc(s){
    return String(s ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");
  }

  function telRow(t={}){
    return `
    <div class="card tel-row" data-id="${t.id||''}" style="padding:12px;">
      <div class="grid" style="grid-template-columns:repeat(12,1fr);gap:12px;">
        <div class="field" style="grid-column:span 3;"><label>Etiqueta</label><input type="text" class="tel-et" value="${esc(t.etiqueta||'principal')}"></div>
        <div class="field" style="grid-column:span 4;"><label>Teléfono</label><input type="tel" class="tel-num" value="${esc(t.telefono||'')}"></div>
        <div class="field" style="grid-column:span 2;"><label>Ext</label><input type="text" class="tel-ext" value="${esc(t.extension||'')}"></div>
        <div class="field" style="grid-column:span 2;">
          <label>Principal</label>
          <label style="display:flex;gap:10px;align-items:center;">
            <input type="checkbox" class="tel-principal" ${t.es_principal ? 'checked':''}>
            <span class="muted" style="margin:0;">Sí</span>
          </label>
        </div>
        <div class="field" style="grid-column:span 1;align-items:flex-end;">
          <button type="button" class="btn btnDelRow" data-kind="tel" title="Quitar"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
    </div>`;
  }

  function mailRow(c={}){
    return `
    <div class="card mail-row" data-id="${c.id||''}" style="padding:12px;">
      <div class="grid" style="grid-template-columns:repeat(12,1fr);gap:12px;">
        <div class="field" style="grid-column:span 4;"><label>Etiqueta</label><input type="text" class="mail-et" value="${esc(c.etiqueta||'principal')}"></div>
        <div class="field" style="grid-column:span 6;"><label>Correo</label><input type="email" class="mail-val" value="${esc(c.correo||'')}"></div>
        <div class="field" style="grid-column:span 1;">
          <label>Principal</label>
          <label style="display:flex;gap:10px;align-items:center;">
            <input type="checkbox" class="mail-principal" ${c.es_principal ? 'checked':''}>
            <span class="muted" style="margin:0;">Sí</span>
          </label>
        </div>
        <div class="field" style="grid-column:span 1;align-items:flex-end;">
          <button type="button" class="btn btnDelRow" data-kind="mail" title="Quitar"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
    </div>`;
  }

  function dirRow(d={}){
    return `
    <div class="card dir-row" data-id="${d.id||''}" style="padding:12px;">
      <div class="grid" style="grid-template-columns:repeat(12,1fr);gap:12px;">
        <div class="field" style="grid-column:span 3;"><label>Etiqueta</label><input type="text" class="dir-et" value="${esc(d.etiqueta||'principal')}"></div>
        <div class="field" style="grid-column:span 7;"><label>Calle</label><input type="text" class="dir-calle" value="${esc(d.calle||'')}"></div>
        <div class="field" style="grid-column:span 1;">
          <label>Principal</label>
          <label style="display:flex;gap:10px;align-items:center;">
            <input type="checkbox" class="dir-principal" ${d.es_principal ? 'checked':''}>
            <span class="muted" style="margin:0;">Sí</span>
          </label>
        </div>
        <div class="field" style="grid-column:span 1;align-items:flex-end;">
          <button type="button" class="btn btnDelRow" data-kind="dir" title="Quitar"><i class="fa-solid fa-trash"></i></button>
        </div>

        <div class="field" style="grid-column:span 3;"><label>No. Ext</label><input type="text" class="dir-ext" value="${esc(d.numero_ext||'')}"></div>
        <div class="field" style="grid-column:span 3;"><label>No. Int</label><input type="text" class="dir-int" value="${esc(d.numero_int||'')}"></div>
        <div class="field" style="grid-column:span 3;"><label>Colonia</label><input type="text" class="dir-col" value="${esc(d.colonia||'')}"></div>
        <div class="field" style="grid-column:span 3;"><label>CP</label><input type="text" class="dir-cp" value="${esc(d.cp||'')}"></div>

        <div class="field" style="grid-column:span 4;"><label>Municipio</label><input type="text" class="dir-mun" value="${esc(d.municipio||'')}"></div>
        <div class="field" style="grid-column:span 4;"><label>Estado</label><input type="text" class="dir-edo" value="${esc(d.estado||'')}"></div>
        <div class="field" style="grid-column:span 4;"><label>Referencias</label><input type="text" class="dir-ref" value="${esc(d.referencias||'')}"></div>
      </div>
    </div>`;
  }

  function enforceSinglePrincipal(containerSel, checkboxSel){
    $(document).on('change', containerSel+' '+checkboxSel, function(){
      if(this.checked) $(containerSel+' '+checkboxSel).not(this).prop('checked', false);
    });
  }

  enforceSinglePrincipal('#telListAddP','.tel-principal');
  enforceSinglePrincipal('#telListEditP','.tel-principal');
  enforceSinglePrincipal('#mailListAddP','.mail-principal');
  enforceSinglePrincipal('#mailListEditP','.mail-principal');
  enforceSinglePrincipal('#dirListAddP','.dir-principal');
  enforceSinglePrincipal('#dirListEditP','.dir-principal');

  $(document).on('click','.btnDelRow',function(){ $(this).closest('.card').remove(); });

  // OPEN ADD
  $('#btnProveedorAdd').on('click', function(){
    $('#p_add_nombres,#p_add_apellido_paterno,#p_add_apellido_materno,#p_add_rfc,#p_add_razon_social').val('');
    $('#p_add_fecha_nacimiento').val('');
    $('#p_add_persona_notas,#p_add_notas').val('');

    $('#telListAddP').html(telRow({es_principal:true}));
    $('#mailListAddP').html(mailRow({es_principal:true}));
    $('#dirListAddP').html(dirRow({es_principal:true}));

    openModal('#modalProveedorAdd');
  });

  $('#btnAddTelAddP').on('click', ()=>$('#telListAddP').append(telRow({})));
  $('#btnAddMailAddP').on('click', ()=>$('#mailListAddP').append(mailRow({})));
  $('#btnAddDirAddP').on('click', ()=>$('#dirListAddP').append(dirRow({})));

  $('#btnAddTelEditP').on('click', ()=>$('#telListEditP').append(telRow({})));
  $('#btnAddMailEditP').on('click', ()=>$('#mailListEditP').append(mailRow({})));
  $('#btnAddDirEditP').on('click', ()=>$('#dirListEditP').append(dirRow({})));

  function buildPayload(mode){
    const isAdd = mode==='add';
    const payload = {
      nombres: (isAdd?$('#p_add_nombres').val():$('#p_edit_nombres').val()) || '',
      apellido_paterno: (isAdd?$('#p_add_apellido_paterno').val():$('#p_edit_apellido_paterno').val()) || '',
      apellido_materno: (isAdd?$('#p_add_apellido_materno').val():$('#p_edit_apellido_materno').val()) || null,
      fecha_nacimiento: (isAdd?$('#p_add_fecha_nacimiento').val():$('#p_edit_fecha_nacimiento').val()) || null,
      persona_notas: (isAdd?$('#p_add_persona_notas').val():$('#p_edit_persona_notas').val()) || null,

      rfc: (isAdd?$('#p_add_rfc').val():$('#p_edit_rfc').val()) || null,
      razon_social: (isAdd?$('#p_add_razon_social').val():$('#p_edit_razon_social').val()) || null,
      notas: (isAdd?$('#p_add_notas').val():$('#p_edit_notas').val()) || null,

      telefonos: [],
      correos: [],
      direcciones: []
    };

    const telList = isAdd ? '#telListAddP' : '#telListEditP';
    const mailList= isAdd ? '#mailListAddP': '#mailListEditP';
    const dirList = isAdd ? '#dirListAddP' : '#dirListEditP';

    $(telList+' .tel-row').each(function(){
      const $r = $(this);
      const tel = ($r.find('.tel-num').val()||'').trim();
      if(!tel) return;
      payload.telefonos.push({
        id: $r.data('id')||null,
        etiqueta: ($r.find('.tel-et').val()||'principal').trim(),
        telefono: tel,
        extension: ($r.find('.tel-ext').val()||'').trim()||null,
        es_principal: $r.find('.tel-principal').is(':checked') ? 1 : 0
      });
    });

    $(mailList+' .mail-row').each(function(){
      const $r=$(this);
      const mail = ($r.find('.mail-val').val()||'').trim();
      if(!mail) return;
      payload.correos.push({
        id: $r.data('id')||null,
        etiqueta: ($r.find('.mail-et').val()||'principal').trim(),
        correo: mail,
        es_principal: $r.find('.mail-principal').is(':checked') ? 1 : 0
      });
    });

    $(dirList+' .dir-row').each(function(){
      const $r=$(this);
      const calle = ($r.find('.dir-calle').val()||'').trim();
      const cp = ($r.find('.dir-cp').val()||'').trim();
      if(!calle && !cp) return;
      payload.direcciones.push({
        id: $r.data('id')||null,
        etiqueta: ($r.find('.dir-et').val()||'principal').trim(),
        calle: calle||null,
        numero_ext: ($r.find('.dir-ext').val()||'').trim()||null,
        numero_int: ($r.find('.dir-int').val()||'').trim()||null,
        colonia: ($r.find('.dir-col').val()||'').trim()||null,
        municipio: ($r.find('.dir-mun').val()||'').trim()||null,
        estado: ($r.find('.dir-edo').val()||'').trim()||null,
        cp: cp||null,
        referencias: ($r.find('.dir-ref').val()||'').trim()||null,
        es_principal: $r.find('.dir-principal').is(':checked') ? 1 : 0
      });
    });

    if(payload.telefonos.length && !payload.telefonos.some(x=>x.es_principal===1)) payload.telefonos[0].es_principal=1;
    if(payload.correos.length && !payload.correos.some(x=>x.es_principal===1)) payload.correos[0].es_principal=1;
    if(payload.direcciones.length && !payload.direcciones.some(x=>x.es_principal===1)) payload.direcciones[0].es_principal=1;

    return payload;
  }

  $('#btnProveedorSaveAdd').on('click', function(){
    const payload = buildPayload('add');
    $.ajax({
      url: R.store,
      type:'POST',
      data: JSON.stringify(payload),
      contentType:'application/json',
      success: function(){
        closeModal('#modalProveedorAdd');
        dt.ajax.reload(null,false);
        Swal.fire({icon:'success',title:'Guardado',text:'Proveedor guardado.',timer:1200,showConfirmButton:false});
      },
      error: function(xhr){
        const res=xhr.responseJSON||{};
        const e=res.errors?Object.values(res.errors)[0]?.[0]:null;
        Swal.fire({icon:'error',title:res.message||'Error',text:e||'Revisa datos.'});
      }
    });
  });

  $(document).on('click','.btnEditProveedor', function(){
    const id=$(this).data('id');
    $.get(urlWithId(R.show,id), function(r){
      $('#p_edit_id').val(r.id);

      $('#p_edit_nombres').val(r.persona?.nombres||'');
      $('#p_edit_apellido_paterno').val(r.persona?.apellido_paterno||'');
      $('#p_edit_apellido_materno').val(r.persona?.apellido_materno||'');
      $('#p_edit_fecha_nacimiento').val(r.persona?.fecha_nacimiento||'');
      $('#p_edit_persona_notas').val(r.persona?.notas||'');

      $('#p_edit_rfc').val(r.rfc||'');
      $('#p_edit_razon_social').val(r.razon_social||'');
      $('#p_edit_notas').val(r.notas||'');

      $('#telListEditP').html('');
      $('#mailListEditP').html('');
      $('#dirListEditP').html('');

      (r.telefonos||[]).forEach(t=>$('#telListEditP').append(telRow(t)));
      (r.correos||[]).forEach(c=>$('#mailListEditP').append(mailRow(c)));
      (r.direcciones||[]).forEach(d=>$('#dirListEditP').append(dirRow(d)));

      if(!r.telefonos?.length) $('#telListEditP').append(telRow({es_principal:true}));
      if(!r.correos?.length) $('#mailListEditP').append(mailRow({es_principal:true}));
      if(!r.direcciones?.length) $('#dirListEditP').append(dirRow({es_principal:true}));

      openModal('#modalProveedorEdit');
    });
  });

  $('#btnProveedorSaveEdit').on('click', function(){
    const id=$('#p_edit_id').val();
    const payload = buildPayload('edit');

    $.ajax({
      url: urlWithId(R.update,id),
      type:'PUT',
      data: JSON.stringify(payload),
      contentType:'application/json',
      success: function(){
        closeModal('#modalProveedorEdit');
        dt.ajax.reload(null,false);
        Swal.fire({icon:'success',title:'Actualizado',text:'Cambios guardados.',timer:1200,showConfirmButton:false});
      },
      error: function(xhr){
        const res=xhr.responseJSON||{};
        const e=res.errors?Object.values(res.errors)[0]?.[0]:null;
        Swal.fire({icon:'error',title:res.message||'Error',text:e||'Revisa datos.'});
      }
    });
  });

  $(document).on('click','.btnBajaProveedor', function(){
    const id=$(this).data('id');
    Swal.fire({
      icon:'warning',
      title:'¿Dar de baja?',
      text:'Baja lógica (no se elimina).',
      showCancelButton:true,
      confirmButtonText:'Sí, dar de baja',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#D9042B'
    }).then((r)=>{
      if(!r.isConfirmed) return;
      $.post(urlWithId(R.baja,id), {motivo:'Baja desde UI'}).done(()=>{
        dt.ajax.reload(null,false);
        Swal.fire({icon:'success',title:'Listo',timer:1000,showConfirmButton:false});
      }).fail(()=>{
        Swal.fire({icon:'error',title:'Error',text:'No se pudo dar de baja.'});
      });
    });
  });

});