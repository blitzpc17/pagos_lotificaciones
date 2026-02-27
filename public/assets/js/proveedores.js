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

  // ---------- helpers Opción A ----------
  const postJson = (url, body)=> $.ajax({ url, type:'POST', data: JSON.stringify(body), contentType:'application/json' });

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

  function toastOk(msg){ Swal.fire({icon:'success', title: msg || 'Listo', timer:1200, showConfirmButton:false}); }
  function toastErr(xhr, fallback){ Swal.fire({icon:'error', title: (xhr?.responseJSON?.message || fallback || 'Error')}); }

  function urlContactoBaja(tipo, cid){
    const tpl = R.contactoBaja;
    return tpl ? tpl.replace('__T__', tipo).replace('__CID__', cid) : null;
  }
  function urlContactoReactivar(tipo, cid){
    const tpl = R.contactoReactivar;
    return tpl ? tpl.replace('__T__', tipo).replace('__CID__', cid) : null;
  }

  async function upsertContactosProveedor(provId, payload){
    // TEL
    for(const t of (payload.telefonos||[])){
      await postJson(urlWithId(R.telAdd, provId), {
        id: t.id || null,
        etiqueta: t.etiqueta,
        telefono: t.telefono,
        extension: t.extension,
        es_principal: !!t.es_principal
      });
    }
    // MAIL
    for(const c of (payload.correos||[])){
      await postJson(urlWithId(R.mailAdd, provId), {
        id: c.id || null,
        etiqueta: c.etiqueta,
        correo: c.correo,
        es_principal: !!c.es_principal
      });
    }
    // DIR
    for(const d of (payload.direcciones||[])){
      await postJson(urlWithId(R.dirAdd, provId), {
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

  // ---------- templates (con baja/reactivar) ----------
  function actionBtn(kind, obj){
    const isBaja = !!obj.baja;
    const icon = isBaja ? 'fa-rotate-left' : 'fa-trash';
    const title = isBaja ? 'Reactivar' : 'Dar de baja';
    const motivo = isBaja ? `<div class="muted" style="margin-top:6px;font-size:12px;">Motivo: ${esc(obj.baja_motivo||'—')}</div>` : '';
    return `
      <button type="button" class="btn btnContactAction" data-kind="${kind}" data-baja="${isBaja?1:0}" title="${title}">
        <i class="fa-solid ${icon}"></i>
      </button>
      ${motivo}
    `;
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
          ${actionBtn('tel', t)}
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
          ${actionBtn('mail', c)}
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
          ${actionBtn('dir', d)}
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

  // click en contacto: si no tiene id => remove; si tiene => baja/reactivar
  $(document).on('click','.btnContactAction', async function(){
    const kind = $(this).data('kind'); // tel|mail|dir
    const isBaja = String($(this).data('baja')) === '1';
    const $card = $(this).closest('.card');
    const cid = $card.data('id');

    if(!cid){ $card.remove(); return; }

    if(isBaja){
      const url = urlContactoReactivar(kind, cid);
      if(!url) return toastErr(null,'No existe ROUTE contactoReactivar');
      $.post(url, {})
        .done(()=>{ refreshContactosEdit(); toastOk('Contacto reactivado'); })
        .fail((xhr)=> toastErr(xhr,'No se pudo reactivar'));
    }else{
      const motivo = await pedirMotivoBaja('Motivo de baja del contacto');
      if(!motivo) return;
      const url = urlContactoBaja(kind, cid);
      if(!url) return toastErr(null,'No existe ROUTE contactoBaja');
      $.post(url, {motivo})
        .done(()=>{ refreshContactosEdit(); toastOk('Contacto dado de baja'); })
        .fail((xhr)=> toastErr(xhr,'No se pudo dar de baja'));
    }
  });

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

  // refresca contactos en edit (vía show)
  function refreshContactosEdit(){
    const id = $('#p_edit_id').val();
    if(!id) return;
    $.get(urlWithId(R.show,id), function(r){
      $('#telListEditP').html('');
      $('#mailListEditP').html('');
      $('#dirListEditP').html('');
      (r.telefonos||[]).forEach(t=>$('#telListEditP').append(telRow(t)));
      (r.correos||[]).forEach(c=>$('#mailListEditP').append(mailRow(c)));
      (r.direcciones||[]).forEach(d=>$('#dirListEditP').append(dirRow(d)));
      if(!r.telefonos?.length) $('#telListEditP').append(telRow({es_principal:true}));
      if(!r.correos?.length) $('#mailListEditP').append(mailRow({es_principal:true}));
      if(!r.direcciones?.length) $('#dirListEditP').append(dirRow({es_principal:true}));
    });
  }

  // SAVE ADD (Opción A): guardar proveedor base y luego contactos por PersonaContactosController
  $('#btnProveedorSaveAdd').on('click', async function(){
    const payload = buildPayload('add');

    // base sin arrays
    const base = {
      nombres: payload.nombres,
      apellido_paterno: payload.apellido_paterno,
      apellido_materno: payload.apellido_materno,
      fecha_nacimiento: payload.fecha_nacimiento,
      persona_notas: payload.persona_notas,

      rfc: payload.rfc,
      razon_social: payload.razon_social,
      notas: payload.notas,
    };

    try{
      const res = await $.ajax({
        url: R.store,
        type:'POST',
        data: JSON.stringify(base),
        contentType:'application/json'
      });

      const provId = res?.id;
      if(!provId) throw new Error('No regresó id del proveedor');

      await upsertContactosProveedor(provId, payload);

      closeModal('#modalProveedorAdd');
      dt.ajax.reload(null,false);
      Swal.fire({icon:'success',title:'Guardado',text:'Proveedor guardado.',timer:1200,showConfirmButton:false});
    }catch(xhr){
      const res=xhr?.responseJSON||{};
      const e=res.errors?Object.values(res.errors)[0]?.[0]:null;
      Swal.fire({icon:'error',title:res.message||'Error',text:e||'Revisa datos.'});
    }
  });

  // EDIT click
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

  // SAVE EDIT (Opción A): update base + upsert contactos por endpoints
  $('#btnProveedorSaveEdit').on('click', async function(){
    const id=$('#p_edit_id').val();
    const payload = buildPayload('edit');

    const base = {
      nombres: payload.nombres,
      apellido_paterno: payload.apellido_paterno,
      apellido_materno: payload.apellido_materno,
      fecha_nacimiento: payload.fecha_nacimiento,
      persona_notas: payload.persona_notas,

      rfc: payload.rfc,
      razon_social: payload.razon_social,
      notas: payload.notas,
    };

    try{
      await $.ajax({
        url: urlWithId(R.update,id),
        type:'PUT',
        data: JSON.stringify(base),
        contentType:'application/json'
      });

      await upsertContactosProveedor(id, payload);

      refreshContactosEdit();
      closeModal('#modalProveedorEdit');
      dt.ajax.reload(null,false);
      Swal.fire({icon:'success',title:'Actualizado',text:'Cambios guardados.',timer:1200,showConfirmButton:false});
    }catch(xhr){
      const res=xhr?.responseJSON||{};
      const e=res.errors?Object.values(res.errors)[0]?.[0]:null;
      Swal.fire({icon:'error',title:res.message||'Error',text:e||'Revisa datos.'});
    }
  });

  // Baja proveedor
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