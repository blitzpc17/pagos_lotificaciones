$(function(){
  $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const R = (window.ROUTES && window.ROUTES.clientes) ? window.ROUTES.clientes : null;
  if(!R){ console.error('ROUTES.clientes no definido'); return; }

  const urlWithId = (tpl, id)=> (tpl||'').replace('__ID__', id);

  function openModal(sel){ $(sel).fadeIn(120); }
  function closeModal(sel){ $(sel).fadeOut(120); }

  $(document).on('click','[data-close]',function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target===this) $(this).fadeOut(120); });

  // DataTable (usa helper global)
  const dt = window.initCmsDataTable('#tblClientes', {
    processing:true,
    serverSide:true,
    ajax:{ url:R.datatable, type:'GET' },
    columns:[
      {data:'id', name:'id'},
      {data:'nombre', name:'nombre'},
      {data:'rfc', name:'rfc'},
      {data:'tipo_cliente', name:'tipo_cliente'},
      {data:'estatus', name:'estatus', orderable:false, searchable:false},
      {data:'acciones', name:'acciones', orderable:false, searchable:false, className:'dt-body-right'}
    ]
  });

  function esc(s){
    return String(s ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");
  }

  // row templates
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

  enforceSinglePrincipal('#telListAddC','.tel-principal');
  enforceSinglePrincipal('#telListEditC','.tel-principal');
  enforceSinglePrincipal('#mailListAddC','.mail-principal');
  enforceSinglePrincipal('#mailListEditC','.mail-principal');
  enforceSinglePrincipal('#dirListAddC','.dir-principal');
  enforceSinglePrincipal('#dirListEditC','.dir-principal');

  $(document).on('click','.btnDelRow',function(){ $(this).closest('.card').remove(); });

  // Open add
  $('#btnClienteAdd').on('click', function(){
    $('#c_add_nombres,#c_add_apellido_paterno,#c_add_apellido_materno,#c_add_rfc,#c_add_tipo_cliente,#c_add_notas,#c_add_notas_cliente,#c_add_fecha_nacimiento').val('');
    $('#telListAddC').html(telRow({es_principal:true}));
    $('#mailListAddC').html(mailRow({es_principal:true}));
    $('#dirListAddC').html(dirRow({es_principal:true}));
    openModal('#modalClienteAdd');
  });

  $('#btnAddTelAddC').on('click', ()=>$('#telListAddC').append(telRow({})));
  $('#btnAddMailAddC').on('click', ()=>$('#mailListAddC').append(mailRow({})));
  $('#btnAddDirAddC').on('click', ()=>$('#dirListAddC').append(dirRow({})));

  $('#btnAddTelEditC').on('click', ()=>$('#telListEditC').append(telRow({})));
  $('#btnAddMailEditC').on('click', ()=>$('#mailListEditC').append(mailRow({})));
  $('#btnAddDirEditC').on('click', ()=>$('#dirListEditC').append(dirRow({})));

  function buildPayload(mode){
    const isAdd = mode==='add';
    const payload = {
      nombres: (isAdd?$('#c_add_nombres').val():$('#c_edit_nombres').val()) || '',
      apellido_paterno: (isAdd?$('#c_add_apellido_paterno').val():$('#c_edit_apellido_paterno').val()) || '',
      apellido_materno: (isAdd?$('#c_add_apellido_materno').val():$('#c_edit_apellido_materno').val()) || null,
      fecha_nacimiento: (isAdd?$('#c_add_fecha_nacimiento').val():$('#c_edit_fecha_nacimiento').val()) || null,
      rfc: (isAdd?$('#c_add_rfc').val():$('#c_edit_rfc').val()) || null,
      tipo_cliente: (isAdd?$('#c_add_tipo_cliente').val():$('#c_edit_tipo_cliente').val()) || 'general',
      notas: (isAdd?$('#c_add_notas_cliente').val():$('#c_edit_notas_cliente').val()) || null,
      persona_notas: (isAdd?$('#c_add_notas').val():$('#c_edit_notas').val()) || null,
      telefonos: [],
      correos: [],
      direcciones: []
    };

    const telList = isAdd ? '#telListAddC' : '#telListEditC';
    const mailList= isAdd ? '#mailListAddC': '#mailListEditC';
    const dirList = isAdd ? '#dirListAddC' : '#dirListEditC';

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

  $('#btnClienteSaveAdd').on('click', function(){
    const payload = buildPayload('add');
    $.ajax({
      url: R.store,
      type:'POST',
      data: JSON.stringify(payload),
      contentType:'application/json',
      success: function(){
        closeModal('#modalClienteAdd');
        dt.ajax.reload(null,false);
        Swal.fire({icon:'success',title:'Guardado',text:'Cliente guardado.',timer:1200,showConfirmButton:false});
      },
      error: function(xhr){
        const res=xhr.responseJSON||{};
        const e=res.errors?Object.values(res.errors)[0]?.[0]:null;
        Swal.fire({icon:'error',title:res.message||'Error',text:e||'Revisa datos.'});
      }
    });
  });

  // Edit click
  $(document).on('click','.btnEditCliente', function(){
    const id=$(this).data('id');
    $.get(urlWithId(R.show,id), function(r){
      $('#c_edit_id').val(r.id);
      $('#c_edit_nombres').val(r.persona?.nombres||'');
      $('#c_edit_apellido_paterno').val(r.persona?.apellido_paterno||'');
      $('#c_edit_apellido_materno').val(r.persona?.apellido_materno||'');
      $('#c_edit_fecha_nacimiento').val(r.persona?.fecha_nacimiento||'');
      $('#c_edit_rfc').val(r.rfc||'');
      $('#c_edit_tipo_cliente').val(r.tipo_cliente||'general');
      $('#c_edit_notas').val(r.persona?.notas||'');
      $('#c_edit_notas_cliente').val(r.notas||'');

      $('#telListEditC').html('');
      $('#mailListEditC').html('');
      $('#dirListEditC').html('');

      (r.telefonos||[]).forEach(t=>$('#telListEditC').append(telRow(t)));
      (r.correos||[]).forEach(c=>$('#mailListEditC').append(mailRow(c)));
      (r.direcciones||[]).forEach(d=>$('#dirListEditC').append(dirRow(d)));

      if(!r.telefonos?.length) $('#telListEditC').append(telRow({es_principal:true}));
      if(!r.correos?.length) $('#mailListEditC').append(mailRow({es_principal:true}));
      if(!r.direcciones?.length) $('#dirListEditC').append(dirRow({es_principal:true}));

      openModal('#modalClienteEdit');
    });
  });

  $('#btnClienteSaveEdit').on('click', function(){
    const id=$('#c_edit_id').val();
    const payload = buildPayload('edit');

    $.ajax({
      url: urlWithId(R.update,id),
      type:'PUT',
      data: JSON.stringify(payload),
      contentType:'application/json',
      success: function(){
        closeModal('#modalClienteEdit');
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

  // Baja (si existe ruta)
  $(document).on('click','.btnBajaCliente', function(){
    if(!R.baja){ Swal.fire({icon:'info', title:'Sin ruta', text:'Define ROUTES.clientes.baja'}); return; }
    const id=$(this).data('id');
    Swal.fire({
      icon:'warning',
      title:'¿Dar de baja?',
      text:'Baja lógica (no se elimina).',
      showCancelButton:true,
      confirmButtonText:'Sí, dar de baja',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#D9042B'
    }).then(async (r)=>{
      if(!r.isConfirmed) return;
      await $.post(urlWithId(R.baja,id), {motivo:'Baja desde UI'});
      Swal.fire({icon:'success', title:'Listo', timer:1000, showConfirmButton:false});
      dt.ajax.reload(null,false);
    });
  });

});
