$(function(){
  $.ajaxSetup({ headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const openModal=(s)=>$(s).fadeIn(120), closeModal=(s)=>$(s).fadeOut(120);
  $(document).on('click','[data-close]', function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target===this) $(this).fadeOut(120); });

  const dt = $('#tblVendedores').DataTable({
    pageLength:10, lengthChange:false,
    ajax:{ url:'/vendedores', data:{json:1}, dataSrc:(r)=>r?.data||[] },
    columns:[
      {data:'id'},{data:'nombre_completo'},{data:'clave'},{data:'comision_default'},
      {data:'estatus_html', orderable:false, searchable:false},
      {data:'acciones_html', orderable:false, searchable:false},
    ],
    language:{ search:"Buscar:", paginate:{previous:"‹",next:"›"}, info:"Mostrando _START_ a _END_ de _TOTAL_", infoEmpty:"Sin registros", zeroRecords:"No se encontraron coincidencias" }
  });

  $('#btnVendedorAdd').on('click', ()=>{ $('#modalVendedorAdd input, #modalVendedorAdd textarea').val(''); $('#v_add_comision').val('0'); openModal('#modalVendedorAdd'); });

  $('#btnVendedorSaveAdd').on('click', async ()=>{
    const payload = {
      clave: $('#v_add_clave').val().trim()||null,
      comision_default: parseFloat($('#v_add_comision').val()||'0'),
      persona:{
        nombres: $('#v_add_nombres').val().trim(),
        apellido_paterno: $('#v_add_apellido_paterno').val().trim(),
        apellido_materno: $('#v_add_apellido_materno').val().trim()||null,
        fecha_nacimiento: $('#v_add_fecha_nacimiento').val()||null,
        notas: $('#v_add_notas').val().trim()||null,
      }
    };
    try{
      const r = await $.ajax({ url:'/vendedores', method:'POST', contentType:'application/json', data:JSON.stringify(payload) });
      Swal.fire({icon:'success',title:'Listo',text:r.message||'Vendedor creado',timer:1200,showConfirmButton:false});
      closeModal('#modalVendedorAdd'); dt.ajax.reload(null,false);
    }catch(e){ Swal.fire({icon:'error',title:'Oops',text:e.responseJSON?.message||'Error'}); }
  });

  $(document).on('click','.btnVendedorEdit', async function(){
    const id=$(this).data('id'); const r=await $.get(`/vendedores/${id}`); const x=r.data;
    $('#v_edit_id').val(x.id);
    $('#v_edit_nombres').val(x.persona?.nombres||'');
    $('#v_edit_apellido_paterno').val(x.persona?.apellido_paterno||'');
    $('#v_edit_apellido_materno').val(x.persona?.apellido_materno||'');
    $('#v_edit_fecha_nacimiento').val(x.persona?.fecha_nacimiento||'');
    $('#v_edit_clave').val(x.clave||'');
    $('#v_edit_comision').val(x.comision_default||0);
    $('#v_edit_notas').val(x.persona?.notas||'');
    openModal('#modalVendedorEdit');
  });

  $('#btnVendedorSaveEdit').on('click', async ()=>{
    const id=$('#v_edit_id').val();
    const payload = {
      clave: $('#v_edit_clave').val().trim()||null,
      comision_default: parseFloat($('#v_edit_comision').val()||'0'),
      persona:{
        nombres: $('#v_edit_nombres').val().trim(),
        apellido_paterno: $('#v_edit_apellido_paterno').val().trim(),
        apellido_materno: $('#v_edit_apellido_materno').val().trim()||null,
        fecha_nacimiento: $('#v_edit_fecha_nacimiento').val()||null,
        notas: $('#v_edit_notas').val().trim()||null,
      }
    };
    try{
      const r = await $.ajax({ url:`/vendedores/${id}`, method:'PUT', contentType:'application/json', data:JSON.stringify(payload) });
      Swal.fire({icon:'success',title:'Actualizado',text:r.message||'Guardado',timer:1200,showConfirmButton:false});
      closeModal('#modalVendedorEdit'); dt.ajax.reload(null,false);
    }catch(e){ Swal.fire({icon:'error',title:'Oops',text:e.responseJSON?.message||'Error'}); }
  });

  $(document).on('click','.btnVendedorBaja', function(){
    const id=$(this).data('id');
    Swal.fire({icon:'warning',title:'¿Dar de baja?',text:'Baja lógica.',showCancelButton:true,confirmButtonText:'Sí',cancelButtonText:'Cancelar',confirmButtonColor:'#D9042B'})
      .then(async (r)=>{ if(!r.isConfirmed) return; await $.post(`/vendedores/${id}/baja`,{motivo:'Baja desde UI'}); Swal.fire({icon:'success',title:'Listo',timer:1000,showConfirmButton:false}); dt.ajax.reload(null,false); });
  });
});
