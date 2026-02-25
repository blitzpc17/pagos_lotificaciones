$(function(){
  $.ajaxSetup({ headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const openModal = s => $(s).fadeIn(120);
  const closeModal = s => $(s).fadeOut(120);
  $(document).on('click','[data-close]', function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target===this) $(this).fadeOut(120); });

  const dt = $('#tblSolicitudes').DataTable({
    pageLength:10, lengthChange:false,
    ajax:{ url:'/autorizaciones', data:{json:1}, dataSrc:r=>r?.data||[] },
    columns:[
      {data:'id'},{data:'tipo'},{data:'tabla_objetivo'},{data:'registro_id'},{data:'motivo'},
      {data:'solicitado_at'},{data:'solicitado_por'},
      {data:'estatus_html', orderable:false, searchable:false},
      {data:'acciones_html', orderable:false, searchable:false},
    ],
    language:{ search:"Buscar:", paginate:{previous:"‹",next:"›"}, info:"Mostrando _START_ a _END_ de _TOTAL_", infoEmpty:"Sin registros", zeroRecords:"No se encontraron coincidencias" }
  });

  $(document).on('click','.btnSolView', async function(){
    const id = $(this).data('id');
    const r = await $.get(`/autorizaciones/${id}`);
    $('#sol_json').val(JSON.stringify(r.data, null, 2));
    openModal('#modalSolView');
  });

  $(document).on('click','.btnSolApprove', function(){
    const id = $(this).data('id');
    Swal.fire({
      icon:'question',
      title:'Aprobar solicitud',
      input:'textarea',
      inputLabel:'Comentario (opcional)',
      showCancelButton:true,
      confirmButtonText:'Aprobar',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#16a34a'
    }).then(async (r)=>{
      if(!r.isConfirmed) return;
      await $.post(`/autorizaciones/${id}/aprobar`, { decision_motivo: r.value || null });
      Swal.fire({icon:'success', title:'Aprobada', timer:1200, showConfirmButton:false});
      dt.ajax.reload(null,false);
    });
  });

  $(document).on('click','.btnSolReject', function(){
    const id = $(this).data('id');
    Swal.fire({
      icon:'warning',
      title:'Rechazar solicitud',
      input:'textarea',
      inputLabel:'Motivo (requerido)',
      inputValidator: v => !v ? 'Debes capturar un motivo.' : null,
      showCancelButton:true,
      confirmButtonText:'Rechazar',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#D9042B'
    }).then(async (r)=>{
      if(!r.isConfirmed) return;
      await $.post(`/autorizaciones/${id}/rechazar`, { decision_motivo: r.value });
      Swal.fire({icon:'success', title:'Rechazada', timer:1200, showConfirmButton:false});
      dt.ajax.reload(null,false);
    });
  });
});
