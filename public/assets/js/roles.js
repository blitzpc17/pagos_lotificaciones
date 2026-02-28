$(function(){

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const openModal = (sel)=> $(sel).fadeIn(120);
  const closeModal = (sel)=> $(sel).fadeOut(120);

  $(document).on('click','[data-close]', function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target === this) $(this).fadeOut(120); });

  const dt = $('#tblRoles').DataTable({
    pageLength: 10,
    lengthChange: false,
    ajax: {
      url: '/roles',
      data: { json: 1 },
      dataSrc: (r)=> r?.data || []
    },
    columns: [
      { data:'id' },
      { data:'nombre' },
      { data:'descripcion' },
      { data:'estatus_html', orderable:false, searchable:false },
      { data:'acciones_html', orderable:false, searchable:false, className:'dt-body-right' },
    ],
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias"
    }
  });

  $('#btnRoleAdd').on('click', function(){
    $('#add_role_nombre,#add_role_desc').val('');
    $('#add_role_active').val('1');
    openModal('#modalRoleAdd');
  });

  $('#btnRoleSaveAdd').on('click', async function(){
    const payload = {
      nombre: $('#add_role_nombre').val().trim(),
      descripcion: $('#add_role_desc').val().trim() || null,
      is_active: $('#add_role_active').val() === '1'
    };
    try{
      const r = await $.ajax({ url:'/roles', method:'POST', contentType:'application/json', data: JSON.stringify(payload) });
      Swal.fire({ icon:'success', title:'Listo', text:r.message || 'Rol creado', timer:1200, showConfirmButton:false });
      closeModal('#modalRoleAdd');
      dt.ajax.reload(null,false);
    }catch(e){
      Swal.fire({ icon:'error', title:'Oops', text: e.responseJSON?.message || 'Error' });
    }
  });

  $(document).on('click', '.btnRoleEdit', async function(){
    const id = $(this).data('id');
    const r = await $.get(`/roles/${id}`);
    const x = r.data;
    $('#edit_role_id').val(x.id);
    $('#edit_role_nombre').val(x.nombre);
    $('#edit_role_desc').val(x.descripcion || '');
    $('#edit_role_active').val(x.is_active ? '1' : '0');
    openModal('#modalRoleEdit');
  });

  $('#btnRoleSaveEdit').on('click', async function(){
    const id = $('#edit_role_id').val();
    const payload = {
      nombre: $('#edit_role_nombre').val().trim(),
      descripcion: $('#edit_role_desc').val().trim() || null,
      is_active: $('#edit_role_active').val() === '1'
    };
    try{
      const r = await $.ajax({ url:`/roles/${id}`, method:'PUT', contentType:'application/json', data: JSON.stringify(payload) });
      Swal.fire({ icon:'success', title:'Actualizado', text:r.message || 'Guardado', timer:1200, showConfirmButton:false });
      closeModal('#modalRoleEdit');
      dt.ajax.reload(null,false);
    }catch(e){
      Swal.fire({ icon:'error', title:'Oops', text: e.responseJSON?.message || 'Error' });
    }
  });

  // ✅ Baja con motivo obligatorio
  $(document).on('click', '.btnRoleBaja', function(){
    const id = $(this).data('id');
    Swal.fire({
      icon:'warning',
      title:'¿Dar de baja?',
      text:'Baja lógica (no se elimina).',
      input:'textarea',
      inputPlaceholder:'Motivo de baja…',
      showCancelButton:true,
      confirmButtonText:'Sí, dar de baja',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#D9042B',
      reverseButtons:true,
      inputValidator:(v)=>{
        if(!v || String(v).trim().length < 3) return 'Motivo obligatorio (mínimo 3 caracteres).';
      }
    }).then(async (r)=>{
      if(!r.isConfirmed) return;
      await $.post(`/roles/${id}/baja`, { motivo: String(r.value).trim() });
      Swal.fire({ icon:'success', title:'Listo', timer:1000, showConfirmButton:false });
      dt.ajax.reload(null,false);
    });
  });

});