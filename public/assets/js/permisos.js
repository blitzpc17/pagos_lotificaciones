$(function(){
  $.ajaxSetup({ headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  // ---- ROL -> MODULOS ----
  $('#roleSelect').on('change', async function(){
    const rid = $(this).val();
    $('.chkRoleModule').prop('checked', false);
    if(!rid) return;

    const r = await $.get(`/permisos/roles/${rid}`);
    const ids = r.data || [];
    ids.forEach(id => $(`.chkRoleModule[value="${id}"]`).prop('checked', true));
  });

  $('#btnRoleClear').on('click', ()=> $('.chkRoleModule').prop('checked', false));

  $('#btnRoleSave').on('click', async ()=>{
    const rid = $('#roleSelect').val();
    if(!rid) return Swal.fire({icon:'warning', title:'Selecciona un rol'});
    const ids = $('.chkRoleModule:checked').map((_,el)=> parseInt(el.value,10)).get();

    try{
      const r = await $.post(`/permisos/roles/${rid}`, { modulo_ids: ids });
      Swal.fire({icon:'success', title:'Listo', text:r.message||'Guardado', timer:1200, showConfirmButton:false});
    }catch(e){
      Swal.fire({icon:'error', title:'Oops', text:e.responseJSON?.message||'Error'});
    }
  });

  // ---- USUARIO -> ACCIONES ----
  function clearUserActions(){
    $('#tblUserActions tbody tr').each(function(){
      $(this).find('.ua_ver,.ua_crear,.ua_modificar,.ua_baja').prop('checked', false);
    });
  }

  $('#userSelect').on('change', async function(){
    const uid = $(this).val();
    clearUserActions();
    if(!uid) return;

    const r = await $.get(`/permisos/usuarios/${uid}/acciones`);
    const map = r.data || {};

    Object.keys(map).forEach(mid=>{
      const row = $(`#tblUserActions tbody tr[data-mid="${mid}"]`);
      if(!row.length) return;
      row.find('.ua_ver').prop('checked', !!map[mid].puede_ver);
      row.find('.ua_crear').prop('checked', !!map[mid].puede_crear);
      row.find('.ua_modificar').prop('checked', !!map[mid].puede_modificar);
      row.find('.ua_baja').prop('checked', !!map[mid].puede_baja);
    });
  });

  $('#btnUserClear').on('click', clearUserActions);

  $('#btnUserSave').on('click', async ()=>{
    const uid = $('#userSelect').val();
    if(!uid) return Swal.fire({icon:'warning', title:'Selecciona un usuario'});

    const acciones = {};
    $('#tblUserActions tbody tr').each(function(){
      const mid = $(this).data('mid');
      acciones[mid] = {
        puede_ver: $(this).find('.ua_ver').is(':checked'),
        puede_crear: $(this).find('.ua_crear').is(':checked'),
        puede_modificar: $(this).find('.ua_modificar').is(':checked'),
        puede_baja: $(this).find('.ua_baja').is(':checked'),
      };
    });

    try{
      const r = await $.post(`/permisos/usuarios/${uid}/acciones`, { acciones });
      Swal.fire({icon:'success', title:'Listo', text:r.message||'Guardado', timer:1200, showConfirmButton:false});
    }catch(e){
      Swal.fire({icon:'error', title:'Oops', text:e.responseJSON?.message||'Error'});
    }
  });
});
