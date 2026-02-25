$(function(){
  $.ajaxSetup({ headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const openModal = s => $(s).fadeIn(120);
  const closeModal = s => $(s).fadeOut(120);

  $(document).on('click','[data-close]', function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target===this) $(this).fadeOut(120); });

  const dt = $('#tblModulos').DataTable({
    pageLength:10, lengthChange:false,
    ajax:{ url:'/modulos', data:{json:1}, dataSrc:r=>r?.data||[] },
    columns:[
      {data:'id'},{data:'nombre'},{data:'ruta'},{data:'icono'},
      {data:'parent_nombre'},{data:'es_menu'},{data:'orden'},
      {data:'estatus_html', orderable:false, searchable:false},
      {data:'acciones_html', orderable:false, searchable:false},
    ],
    language:{ search:"Buscar:", paginate:{previous:"‹",next:"›"}, info:"Mostrando _START_ a _END_ de _TOTAL_", infoEmpty:"Sin registros", zeroRecords:"No se encontraron coincidencias" }
  });

  $('#btnModuloAdd').on('click', ()=>{ $('#modalModuloAdd input').val(''); $('#m_add_orden').val('0'); $('#m_add_es_menu').val('1'); $('#m_add_is_active').val('1'); openModal('#modalModuloAdd'); });

  $('#btnModuloSaveAdd').on('click', async ()=>{
    const payload = {
      nombre: $('#m_add_nombre').val().trim(),
      ruta: $('#m_add_ruta').val().trim()||null,
      icono: $('#m_add_icono').val().trim()||null,
      parent_id: $('#m_add_parent_id').val()? parseInt($('#m_add_parent_id').val(),10) : null,
      es_menu: $('#m_add_es_menu').val()==='1',
      orden: parseInt($('#m_add_orden').val()||'0',10),
      is_active: $('#m_add_is_active').val()==='1'
    };
    try{
      const r = await $.ajax({ url:'/modulos', method:'POST', contentType:'application/json', data:JSON.stringify(payload) });
      Swal.fire({icon:'success',title:'Listo',text:r.message||'Creado',timer:1200,showConfirmButton:false});
      closeModal('#modalModuloAdd'); dt.ajax.reload(null,false);
    }catch(e){ Swal.fire({icon:'error',title:'Oops',text:e.responseJSON?.message||'Error'}); }
  });

  $(document).on('click','.btnModuloEdit', async function(){
    const id = $(this).data('id');
    const r = await $.get(`/modulos/${id}`);
    const x = r.data;

    $('#m_edit_id').val(x.id);
    $('#m_edit_nombre').val(x.nombre||'');
    $('#m_edit_ruta').val(x.ruta||'');
    $('#m_edit_icono').val(x.icono||'');
    $('#m_edit_parent_id').val(x.parent_id||'');
    $('#m_edit_es_menu').val(x.es_menu ? '1':'0');
    $('#m_edit_orden').val(x.orden||0);
    $('#m_edit_is_active').val(x.is_active ? '1':'0');

    openModal('#modalModuloEdit');
  });

  $('#btnModuloSaveEdit').on('click', async ()=>{
    const id = $('#m_edit_id').val();
    const payload = {
      nombre: $('#m_edit_nombre').val().trim(),
      ruta: $('#m_edit_ruta').val().trim()||null,
      icono: $('#m_edit_icono').val().trim()||null,
      parent_id: $('#m_edit_parent_id').val()? parseInt($('#m_edit_parent_id').val(),10) : null,
      es_menu: $('#m_edit_es_menu').val()==='1',
      orden: parseInt($('#m_edit_orden').val()||'0',10),
      is_active: $('#m_edit_is_active').val()==='1'
    };
    try{
      const r = await $.ajax({ url:`/modulos/${id}`, method:'PUT', contentType:'application/json', data:JSON.stringify(payload) });
      Swal.fire({icon:'success',title:'Actualizado',text:r.message||'Guardado',timer:1200,showConfirmButton:false});
      closeModal('#modalModuloEdit'); dt.ajax.reload(null,false);
    }catch(e){ Swal.fire({icon:'error',title:'Oops',text:e.responseJSON?.message||'Error'}); }
  });

  $(document).on('click','.btnModuloBaja', function(){
    const id = $(this).data('id');
    Swal.fire({icon:'warning',title:'¿Dar de baja?',text:'Baja lógica.',showCancelButton:true,confirmButtonText:'Sí',cancelButtonText:'Cancelar',confirmButtonColor:'#D9042B'})
      .then(async r=>{
        if(!r.isConfirmed) return;
        await $.post(`/modulos/${id}/baja`, {motivo:'Baja desde UI'});
        Swal.fire({icon:'success',title:'Listo',timer:1000,showConfirmButton:false});
        dt.ajax.reload(null,false);
      });
  });
});
