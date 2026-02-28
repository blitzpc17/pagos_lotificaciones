$(function(){
  const CSRF = $('meta[name="csrf-token"]').attr('content');
  const R = window.ROUTES?.usuarios || {};
  const url = (tpl, id) => (tpl || '').replace('__ID__', id);

  function openModal(sel){ $(sel).fadeIn(120); }
  function closeModal(sel){ $(sel).fadeOut(120); }

  $(document).on('click','[data-close]', function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target === this) $(this).fadeOut(120); });

  const dt = $('#tblUsuarios').DataTable({
    ajax: { url: R.datatable, dataSrc:'data' },
    pageLength: 10,
    lengthChange:false,
    columns: [
      { data:'id' },
      { data:'empleado' },
      { data:'username' },
      { data:'email' },
      { data:'rol' },
      { data:'estatus', orderable:false, searchable:false }, // ✅ ya viene HTML
      { data:null, orderable:false, searchable:false, className:'dt-body-right', render:(row)=> `
        <div class="dt-actions">
          <button class="mini primary btnUserEdit" data-id="${row.id}"><i class="fa-regular fa-pen-to-square"></i> Editar</button>
          ${row._is_baja ? '' : `<button class="mini danger btnUserBaja" data-id="${row.id}"><i class="fa-regular fa-trash-can"></i> Baja</button>`}
        </div>
      `}
    ],
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias"
    }
  });

  async function loadEmpleadosDisponibles(){
    const res = await $.get(R.empleados);
    const data = res.data || [];
    const $sel = $('#u_add_empleado_id');
    $sel.empty();
    data.forEach(e=>{
      $sel.append(`<option value="${e.id}">${e.numero_empleado} · ${e.nombre} · ${e.puesto}</option>`);
    });
  }

  $('#btnUserAdd').on('click', async function(){
    $('#u_add_username,#u_add_email,#u_add_password').val('');
    await loadEmpleadosDisponibles();
    openModal('#modalUserAdd');
  });

  $('#btnUserSaveAdd').on('click', async function(){
    const payload = {
      _token: CSRF,
      empleado_id: $('#u_add_empleado_id').val(),
      role_id: $('#u_add_role_id').val(),
      username: $('#u_add_username').val().trim(),
      email: $('#u_add_email').val().trim(),
      password: $('#u_add_password').val()
    };

    try{
      await $.post(R.store, payload);
      Swal.fire({ icon:'success', title:'Guardado', text:'Usuario creado.', timer:1200, showConfirmButton:false });
      closeModal('#modalUserAdd');
      dt.ajax.reload(null,false);
    }catch(err){
      const r = err.responseJSON || {};
      Swal.fire({ icon:'error', title:'Error', text: r.message || 'No se pudo guardar' });
      if(r.errors) console.log(r.errors);
    }
  });

  let currentId = null;

  $(document).on('click', '.btnUserEdit', async function(){
    currentId = $(this).data('id');
    const res = await $.get(url(R.show, currentId));

    $('#u_edit_id').val(res.id);
    $('#u_edit_empleado_label').val(res.empleado_label);
    $('#u_edit_role_id').val(res.role_id);
    $('#u_edit_username').val(res.username);
    $('#u_edit_email').val(res.email || '');
    $('#u_edit_password').val('');
    $('#u_edit_is_active').val(res.is_active ? '1':'0');

    openModal('#modalUserEdit');
  });

  $('#btnUserSaveEdit').on('click', async function(){
    if(!currentId) return;

    const payload = {
      _token: CSRF,
      _method: 'PUT',
      role_id: $('#u_edit_role_id').val(),
      username: $('#u_edit_username').val().trim(),
      email: $('#u_edit_email').val().trim(),
      password: $('#u_edit_password').val(),
      is_active: $('#u_edit_is_active').val()
    };

    try{
      await $.ajax({ url: url(R.update, currentId), method:'POST', data: payload });
      Swal.fire({ icon:'success', title:'Actualizado', text:'Cambios guardados.', timer:1200, showConfirmButton:false });
      closeModal('#modalUserEdit');
      dt.ajax.reload(null,false);
    }catch(err){
      const r = err.responseJSON || {};
      Swal.fire({ icon:'error', title:'Error', text: r.message || 'No se pudo actualizar' });
      if(r.errors) console.log(r.errors);
    }
  });

  // ✅ Baja con motivo obligatorio
  $(document).on('click','.btnUserBaja', function(){
    const id = $(this).data('id');
    Swal.fire({
      icon:'warning',
      title:'¿Dar de baja usuario?',
      input:'textarea',
      inputPlaceholder:'Motivo…',
      showCancelButton:true,
      confirmButtonText:'Sí, baja',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#D9042B',
      reverseButtons:true,
      inputValidator:(v)=>{
        if(!v || String(v).trim().length < 3) return 'Motivo obligatorio (mínimo 3 caracteres).';
      }
    }).then(async (r)=>{
      if(!r.isConfirmed) return;
      await $.post(url(R.baja, id), { _token: CSRF, motivo: String(r.value).trim() });
      Swal.fire({ icon:'success', title:'Listo', text:'Usuario dado de baja.', timer:1200, showConfirmButton:false });
      dt.ajax.reload(null,false);
    });
  });

});