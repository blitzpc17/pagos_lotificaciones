$(function(){
  $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const R = (window.ROUTES && window.ROUTES.socios) ? window.ROUTES.socios : null;
  if(!R){ console.error('ROUTES.socios no definido'); return; }

  const urlWithId = (tpl, id)=> (tpl||'').replace('__ID__', id);
  const openModal = s => $(s).fadeIn(120);
  const closeModal = s => $(s).fadeOut(120);

  $(document).on('click','[data-close]', function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target===this) $(this).fadeOut(120); });

  function esc(s){
    return String(s ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");
  }

  function toastOk(msg){
    Swal.fire({icon:'success', title: msg || 'Listo', timer: 1200, showConfirmButton:false});
  }
  function toastErr(xhr, fallback){
    Swal.fire({icon:'error', title: (xhr?.responseJSON?.message || fallback || 'Error')});
  }

  // ---- Color helpers ----
  const normalizeHex = (v) => {
    if(!v) return '#2D6CDF';
    let h = String(v).trim();
    if(!h) return '#2D6CDF';
    if(h[0] !== '#') h = '#'+h;
    h = h.toUpperCase();
    if(!/^#[0-9A-F]{6}$/.test(h)) return null;
    return h;
  };

  const setColorUI = (prefix, hex) => {
    const h = normalizeHex(hex) || '#2D6CDF';
    $(`#${prefix}_color_picker`).val(h);
    $(`#${prefix}_color_hex`).val(h);
    $(`#${prefix}_color_preview`).css('background', h);
    $(`#${prefix}_color_preview_text`).text(h);
  };

  // sync add
  $('#s_add_color_picker').on('input change', function(){ setColorUI('s_add', $(this).val()); });
  $('#s_add_color_hex').on('input', function(){
    const ok = normalizeHex($(this).val());
    if(ok){
      $('#s_add_color_picker').val(ok);
      $('#s_add_color_preview').css('background', ok);
      $('#s_add_color_preview_text').text(ok);
      $(this).css('border-color', '');
    }else{
      $(this).css('border-color', 'rgba(239,68,68,.65)');
    }
  });
  $('#s_add_color_hex').on('blur', function(){
    setColorUI('s_add', normalizeHex($(this).val()) || '#2D6CDF');
    $(this).css('border-color', '');
  });

  // sync edit
  $('#s_edit_color_picker').on('input change', function(){ setColorUI('s_edit', $(this).val()); });
  $('#s_edit_color_hex').on('input', function(){
    const ok = normalizeHex($(this).val());
    if(ok){
      $('#s_edit_color_picker').val(ok);
      $('#s_edit_color_preview').css('background', ok);
      $('#s_edit_color_preview_text').text(ok);
      $(this).css('border-color', '');
    }else{
      $(this).css('border-color', 'rgba(239,68,68,.65)');
    }
  });
  $('#s_edit_color_hex').on('blur', function(){
    setColorUI('s_edit', normalizeHex($(this).val()) || '#2D6CDF');
    $(this).css('border-color', '');
  });

  // ---- Baja / Reactivar contacto ----
  async function pedirMotivoBajaContacto(){
    const r = await Swal.fire({
      title: 'Motivo de baja del contacto',
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

  const urlContactoBaja = (tipo, cid)=> (R.contactoBaja||'').replace('__T__', tipo).replace('__CID__', cid);
  const urlContactoReactivar = (tipo, cid)=> (R.contactoReactivar||'').replace('__T__', tipo).replace('__CID__', cid);

  // ---- templates contactos ----
  function contactActionBtn(kind, obj){
    const isBaja = !!obj.baja;
    const icon = isBaja ? 'fa-rotate-left' : 'fa-trash';
    const title = isBaja ? 'Reactivar' : 'Dar de baja';
    const motivo = isBaja ? `<div class="muted" style="margin-top:6px;font-size:12px;">Motivo: ${esc(obj.baja_motivo||'—')}</div>` : ``;

    return `
      <button type="button" class="btn btnContactAction"
              data-kind="${kind}"
              data-baja="${isBaja ? 1 : 0}"
              title="${title}">
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
          ${contactActionBtn('tel', t)}
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
          ${contactActionBtn('mail', c)}
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
          ${contactActionBtn('dir', d)}
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

  enforceSinglePrincipal('#telListAddS','.tel-principal');
  enforceSinglePrincipal('#telListEditS','.tel-principal');
  enforceSinglePrincipal('#mailListAddS','.mail-principal');
  enforceSinglePrincipal('#mailListEditS','.mail-principal');
  enforceSinglePrincipal('#dirListAddS','.dir-principal');
  enforceSinglePrincipal('#dirListEditS','.dir-principal');

  // Acción contacto: si no hay id => quitar del DOM; si hay id => baja/reactivar
  $(document).on('click', '.btnContactAction', async function(){
    const $btn = $(this);
    const kind = $btn.data('kind'); // tel|mail|dir
    const isBaja = String($btn.data('baja')) === '1';
    const $card = $btn.closest('.card');
    const cid = $card.data('id');

    // fila nueva (sin id en BD)
    if(!cid){
      $card.remove();
      return;
    }

    if(isBaja){
      $.post(urlContactoReactivar(kind, cid), {})
        .done(()=>{ refreshContactosEdit(); toastOk('Contacto reactivado'); })
        .fail((xhr)=> toastErr(xhr,'No se pudo reactivar'));
    }else{
      const motivo = await pedirMotivoBajaContacto();
      if(!motivo) return;

      $.post(urlContactoBaja(kind, cid), {motivo})
        .done(()=>{ refreshContactosEdit(); toastOk('Contacto dado de baja'); })
        .fail((xhr)=> toastErr(xhr,'No se pudo dar de baja'));
    }
  });

  // ---- DataTable ----
  const dt = window.initCmsDataTable('#tblSocios', {
    processing:true,
    serverSide:true,
    ajax:{ url: R.datatable, type:'GET' },
    columns:[
      {data:'id', name:'id'},
      {data:'nombre', name:'nombre'},
      {data:'color', name:'color', orderable:false, searchable:false},
      {data:'estatus', name:'estatus', orderable:false, searchable:false},
      {data:'acciones', name:'acciones', orderable:false, searchable:false, className:'dt-body-right'}
    ]
  });

  // ---- Add ----
  $('#btnSocioAdd').on('click', function(){
    $('#s_add_nombre').val('');
    setColorUI('s_add', '#2D6CDF');

    $('#telListAddS').html(telRow({es_principal:true}));
    $('#mailListAddS').html(mailRow({es_principal:true}));
    $('#dirListAddS').html(dirRow({es_principal:true}));

    openModal('#modalSocioAdd');
  });

  $('#btnAddTelAddS').on('click', ()=>$('#telListAddS').append(telRow({})));
  $('#btnAddMailAddS').on('click', ()=>$('#mailListAddS').append(mailRow({})));
  $('#btnAddDirAddS').on('click', ()=>$('#dirListAddS').append(dirRow({})));

  $('#btnAddTelEditS').on('click', ()=>$('#telListEditS').append(telRow({})));
  $('#btnAddMailEditS').on('click', ()=>$('#mailListEditS').append(mailRow({})));
  $('#btnAddDirEditS').on('click', ()=>$('#dirListEditS').append(dirRow({})));

  function buildPayload(mode){
    const isAdd = mode==='add';
    const payload = {
      nombre: (isAdd?$('#s_add_nombre').val():$('#s_edit_nombre').val()) || '',
      color: (normalizeHex(isAdd?$('#s_add_color_hex').val():$('#s_edit_color_hex').val()) || '#2D6CDF'),
      telefonos: [],
      correos: [],
      direcciones: []
    };

    const telList = isAdd ? '#telListAddS' : '#telListEditS';
    const mailList= isAdd ? '#mailListAddS': '#mailListEditS';
    const dirList = isAdd ? '#dirListAddS' : '#dirListEditS';

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

    // asegúrate que haya principal
    if(payload.telefonos.length && !payload.telefonos.some(x=>x.es_principal===1)) payload.telefonos[0].es_principal=1;
    if(payload.correos.length && !payload.correos.some(x=>x.es_principal===1)) payload.correos[0].es_principal=1;
    if(payload.direcciones.length && !payload.direcciones.some(x=>x.es_principal===1)) payload.direcciones[0].es_principal=1;

    return payload;
  }

  // Helper POST JSON
  const postJson = (url, body) => $.ajax({ url, type:'POST', data: JSON.stringify(body), contentType:'application/json' });

  // ---- Guardar ADD (Opción A) ----
  $('#btnSocioSaveAdd').on('click', async function(){
    const payload = buildPayload('add');
    const socioPayload = { nombre: payload.nombre, color: payload.color };

    try{
      const res = await $.ajax({
        url: R.store,
        type:'POST',
        data: JSON.stringify(socioPayload),
        contentType:'application/json',
      });

      const socioId = res?.id;
      if(!socioId) throw new Error('No regresó id del socio');

      // teléfonos
      for(const t of (payload.telefonos||[])){
        await postJson(urlWithId(R.telAdd, socioId), {
          etiqueta: t.etiqueta,
          telefono: t.telefono,
          extension: t.extension,
          es_principal: !!t.es_principal
        });
      }
      // correos
      for(const c of (payload.correos||[])){
        await postJson(urlWithId(R.mailAdd, socioId), {
          etiqueta: c.etiqueta,
          correo: c.correo,
          es_principal: !!c.es_principal
        });
      }
      // direcciones
      for(const d of (payload.direcciones||[])){
        await postJson(urlWithId(R.dirAdd, socioId), {
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

      closeModal('#modalSocioAdd');
      dt.ajax.reload(null,false);
      toastOk('Socio guardado');

    }catch(xhr){
      const res=xhr?.responseJSON||{};
      const e=res.errors?Object.values(res.errors)[0]?.[0]:null;
      Swal.fire({icon:'error',title:res.message||'Error',text:e||'Revisa datos.'});
    }
  });

  // ---- Edit ----
  $(document).on('click','.btnEditSocio', function(){
    const id=$(this).data('id');
    $.get(urlWithId(R.show,id), function(r){
      $('#s_edit_id').val(r.id);
      $('#s_edit_nombre').val(r.nombre || '');
      setColorUI('s_edit', r.color || '#2D6CDF');

      $('#telListEditS').html('');
      $('#mailListEditS').html('');
      $('#dirListEditS').html('');

      (r.telefonos||[]).forEach(t=>$('#telListEditS').append(telRow(t)));
      (r.correos||[]).forEach(c=>$('#mailListEditS').append(mailRow(c)));
      (r.direcciones||[]).forEach(d=>$('#dirListEditS').append(dirRow(d)));

      if(!r.telefonos?.length) $('#telListEditS').append(telRow({es_principal:true}));
      if(!r.correos?.length) $('#mailListEditS').append(mailRow({es_principal:true}));
      if(!r.direcciones?.length) $('#dirListEditS').append(dirRow({es_principal:true}));

      openModal('#modalSocioEdit');
    });
  });

  // refrescar contactos edit (vía show)
  function refreshContactosEdit(){
    const id = $('#s_edit_id').val();
    if(!id) return;
    $.get(urlWithId(R.show, id), function(r){
      $('#telListEditS').html('');
      $('#mailListEditS').html('');
      $('#dirListEditS').html('');
      (r.telefonos||[]).forEach(t=>$('#telListEditS').append(telRow(t)));
      (r.correos||[]).forEach(c=>$('#mailListEditS').append(mailRow(c)));
      (r.direcciones||[]).forEach(d=>$('#dirListEditS').append(dirRow(d)));
      if(!r.telefonos?.length) $('#telListEditS').append(telRow({es_principal:true}));
      if(!r.correos?.length) $('#mailListEditS').append(mailRow({es_principal:true}));
      if(!r.direcciones?.length) $('#dirListEditS').append(dirRow({es_principal:true}));
    });
  }

  // ---- Guardar EDIT (Opción A) ----
  $('#btnSocioSaveEdit').on('click', async function(){
    const id = $('#s_edit_id').val();
    const payload = buildPayload('edit');

    const socioPayload = { nombre: payload.nombre, color: payload.color };

    try{
      // 1) actualiza socio
      await $.ajax({
        url: urlWithId(R.update,id),
        type:'PUT',
        data: JSON.stringify(socioPayload),
        contentType:'application/json'
      });

      const postJson = (url, body) => $.ajax({
        url, type:'POST', data: JSON.stringify(body), contentType:'application/json'
      });

      // 2) upsert teléfonos (TODOS: con id y sin id)
      for(const t of (payload.telefonos||[])){
        await postJson(urlWithId(R.telAdd, id), {
          id: t.id || null,
          etiqueta: t.etiqueta,
          telefono: t.telefono,
          extension: t.extension,
          es_principal: !!t.es_principal
        });
      }

      // 3) upsert correos
      for(const c of (payload.correos||[])){
        await postJson(urlWithId(R.mailAdd, id), {
          id: c.id || null,
          etiqueta: c.etiqueta,
          correo: c.correo,
          es_principal: !!c.es_principal
        });
      }

      // 4) upsert direcciones
      for(const d of (payload.direcciones||[])){
        await postJson(urlWithId(R.dirAdd, id), {
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

      // 5) refresca contactos (para traer ids nuevos)
      refreshContactosEdit();

      closeModal('#modalSocioEdit');
      dt.ajax.reload(null,false);
      toastOk('Cambios guardados');

    }catch(xhr){
      const res=xhr.responseJSON||{};
      const e=res.errors?Object.values(res.errors)[0]?.[0]:null;
      Swal.fire({icon:'error',title:res.message||'Error',text:e||'Revisa datos.'});
    }
  });

  // ---- Baja socio (motivo) ----
  $(document).on('click','.btnBajaSocio', function(){
    const id=$(this).data('id');
    Swal.fire({
      icon:'warning',
      title:'¿Dar de baja al socio?',
      text:'Baja lógica (no se elimina).',
      showCancelButton:true,
      confirmButtonText:'Sí, dar de baja',
      cancelButtonText:'Cancelar',
      reverseButtons:true
    }).then(async (r)=>{
      if(!r.isConfirmed) return;

      const mot = await Swal.fire({
        title: 'Motivo de baja del socio',
        input: 'textarea',
        inputPlaceholder: 'Escribe el motivo…',
        showCancelButton: true,
        confirmButtonText: 'Aplicar baja',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        inputValidator: (v)=>{
          if(!v || String(v).trim().length < 3) return 'Motivo obligatorio (mínimo 3 caracteres).';
        }
      });

      if(!mot.isConfirmed) return;

      $.post(urlWithId(R.baja,id), {motivo: String(mot.value).trim()})
        .done(()=>{ toastOk('Baja aplicada'); dt.ajax.reload(null,false); })
        .fail((xhr)=> toastErr(xhr,'No se pudo dar de baja'));
    });
  });

});