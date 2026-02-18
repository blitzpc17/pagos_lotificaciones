$(function(){

  // CSRF (si tienes meta en layout)
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const openModal = (sel)=> $(sel).fadeIn(120);
  const closeModal = (sel)=> $(sel).fadeOut(120);

  // cierre por data-close
  $(document).on('click','[data-close]', function(){
    closeModal($(this).data('close'));
  });
  $('.modal').on('click', function(e){
    if(e.target === this) $(this).fadeOut(120);
  });

  // ===== Roles para selects =====
  async function loadRolesSelect($sel){
    $sel.html('<option value="">Cargando...</option>');
    const r = await $.get('/roles', { json: 1 });
    const roles = r?.data || [];
    let html = '<option value="">Selecciona...</option>';
    roles.forEach(x=>{
      html += `<option value="${x.id}">${x.nombre}</option>`;
    });
    $sel.html(html);
  }

  // ===== DataTable =====
  const dt = $('#tblUsuarios').DataTable({
    pageLength: 10,
    lengthChange: false,
    autoWidth: false,
    ajax: {
      url: '/usuarios',
      dataSrc: function(resp){
        return resp?.data || [];
      }
    },
    columns: [
      { data: 'id' },
      { data: 'nombre_completo' },
      { data: 'username' },
      { data: 'email' },
      { data: 'rol' },
      { data: 'estatus_html', orderable:false, searchable:false },
      { data: 'acciones_html', orderable:false, searchable:false }
    ],
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias"
    }
  });

  // ===== Abrir modal ADD =====
  $('#btnUserAdd').on('click', async function(){
    $('#add_nombres, #add_apellido_paterno, #add_apellido_materno, #add_username, #add_email, #add_password, #add_notas').val('');
    $('#add_fecha_nacimiento').val('');
    await loadRolesSelect($('#add_role_id'));
    openModal('#modalUserAdd');
  });

  // ===== Guardar ADD =====
  $('#btnUserSaveAdd').on('click', async function(){
    const payload = {
      username: $('#add_username').val().trim() || null,
      email: $('#add_email').val().trim() || null,
      password: $('#add_password').val().trim(),
      role_id: $('#add_role_id').val(),
      persona: {
        nombres: $('#add_nombres').val().trim(),
        apellido_paterno: $('#add_apellido_paterno').val().trim(),
        apellido_materno: $('#add_apellido_materno').val().trim() || null,
        fecha_nacimiento: $('#add_fecha_nacimiento').val() || null,
        notas: $('#add_notas').val().trim() || null,
      }
    };

    try{
      const r = await $.ajax({
        url:'/usuarios',
        method:'POST',
        contentType:'application/json',
        data: JSON.stringify(payload)
      });
      Swal.fire({ icon:'success', title:'Listo', text: r.message || 'Usuario creado', timer: 1300, showConfirmButton:false });
      closeModal('#modalUserAdd');
      dt.ajax.reload(null,false);
    }catch(e){
      const msg = e.responseJSON?.message || 'Error al guardar';
      Swal.fire({ icon:'error', title:'Oops', text: msg });
    }
  });

  // ===== Edit (delegado) =====
  $(document).on('click', '.btnUserEdit', async function(){
    const id = $(this).data('id');
    try{
      const r = await $.get(`/usuarios/${id}`);
      const u = r.data;

      $('#edit_id').val(u.id);
      $('#edit_username').val(u.username || '');
      $('#edit_email').val(u.email || '');
      $('#edit_password').val('');

      $('#edit_nombres').val(u.persona?.nombres || '');
      $('#edit_apellido_paterno').val(u.persona?.apellido_paterno || '');
      $('#edit_apellido_materno').val(u.persona?.apellido_materno || '');
      $('#edit_fecha_nacimiento').val(u.persona?.fecha_nacimiento || '');
      $('#edit_notas').val(u.persona?.notas || '');

      await loadRolesSelect($('#edit_role_id'));
      $('#edit_role_id').val(u.role_id);

      openModal('#modalUserEdit');
    }catch(e){
      Swal.fire({ icon:'error', title:'Error', text:'No se pudo cargar el usuario.' });
    }
  });

  // ===== Guardar EDIT =====
  $('#btnUserSaveEdit').on('click', async function(){
    const id = $('#edit_id').val();

    const payload = {
      username: $('#edit_username').val().trim() || null,
      email: $('#edit_email').val().trim() || null,
      password: $('#edit_password').val().trim() || null,
      role_id: $('#edit_role_id').val(),
      persona: {
        nombres: $('#edit_nombres').val().trim(),
        apellido_paterno: $('#edit_apellido_paterno').val().trim(),
        apellido_materno: $('#edit_apellido_materno').val().trim() || null,
        fecha_nacimiento: $('#edit_fecha_nacimiento').val() || null,
        notas: $('#edit_notas').val().trim() || null,
      }
    };

    try{
      const r = await $.ajax({
        url:`/usuarios/${id}`,
        method:'PUT',
        contentType:'application/json',
        data: JSON.stringify(payload)
      });
      Swal.fire({ icon:'success', title:'Actualizado', text: r.message || 'Guardado', timer: 1300, showConfirmButton:false });
      closeModal('#modalUserEdit');
      dt.ajax.reload(null,false);
    }catch(e){
      const msg = e.responseJSON?.message || 'Error al actualizar';
      Swal.fire({ icon:'error', title:'Oops', text: msg });
    }
  });

  // ===== Baja (delegado) =====
  $(document).on('click', '.btnUserBaja', function(){
    const id = $(this).data('id');

    Swal.fire({
      icon:'warning',
      title:'¿Dar de baja?',
      text:'Se aplicará baja lógica (no se elimina).',
      showCancelButton:true,
      confirmButtonText:'Sí, dar de baja',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#D9042B'
    }).then(async (r)=>{
      if(!r.isConfirmed) return;

      try{
        const resp = await $.post(`/usuarios/${id}/baja`, { motivo: 'Baja desde UI' });
        Swal.fire({ icon:'success', title:'Listo', text: resp.message || 'Baja aplicada', timer: 1200, showConfirmButton:false });
        dt.ajax.reload(null,false);
      }catch(e){
        const msg = e.responseJSON?.message || 'Error al dar de baja';
        Swal.fire({ icon:'error', title:'Oops', text: msg });
      }
    });
  });

});
