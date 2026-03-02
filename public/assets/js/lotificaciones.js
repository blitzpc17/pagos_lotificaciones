$(function(){
  $.ajaxSetup({ headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const R = window.ROUTES.lotificaciones;
  const $modal = $('#modalLotificacion');

  const tbl = $('#tblLotificaciones').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    ajax: { url: R.datatable },
    columns:[
      {data:'id'},
      {data:'nombre'},
      {data:'numero_lotes'},
      {data:'oficina'},
      {data:'estado'},
      {data:'estatus', orderable:false, searchable:false},
      {data:'acciones', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });

  function openModal(isEdit){
    $('#lotModalTitle').html(isEdit ? '<i class="fa-regular fa-pen-to-square"></i> Editar Lotificación' : '<i class="fa-solid fa-map"></i> Nueva Lotificación');
    $modal.addClass('show');
  }

  function clearForm(){
    $('#lot_id').val('');
    $('#lot_nombre,#lot_oficina,#lot_estado').val('');
    $('#lot_numero_lotes').val(0);
    $('#lot_json').val('');
  }

  $('#btnLotificacionAdd').on('click', function(){
    clearForm();
    openModal(false);
  });

  $(document).on('click','.btnEditLotificacion', async function(){
    const id = $(this).data('id');
    const url = R.show.replace('__ID__', id);
    const r = await $.get(url);
    clearForm();
    $('#lot_id').val(r.id);
    $('#lot_nombre').val(r.nombre);
    $('#lot_oficina').val(r.oficina || '');
    $('#lot_estado').val(r.estado || '');
    $('#lot_numero_lotes').val(r.numero_lotes || 0);
    $('#lot_json').val(r.json_croquis ? JSON.stringify(r.json_croquis, null, 2) : '');
    openModal(true);
  });

  $('#btnLotificacionSave').on('click', async function(){
    const id = $('#lot_id').val();
    const payload = {
      nombre: $('#lot_nombre').val().trim(),
      oficina: $('#lot_oficina').val().trim() || null,
      estado: $('#lot_estado').val().trim() || null,
      numero_lotes: parseInt($('#lot_numero_lotes').val()||'0',10),
      json_croquis: $('#lot_json').val().trim() || null,
    };

    try{
      if(!payload.nombre) return Swal.fire({icon:'warning', title:'Nombre requerido'});

      if(payload.json_croquis){
        try { JSON.parse(payload.json_croquis); } catch(e){
          return Swal.fire({icon:'warning', title:'Croquis JSON inválido'});
        }
      }

      if(!id){
        await $.post(R.store, payload);
      }else{
        const url = R.update.replace('__ID__', id);
        await $.ajax({url, method:'PUT', data: payload});
      }

      $modal.removeClass('show');
      tbl.ajax.reload(null,false);
      Swal.fire({icon:'success', title:'Guardado'});
    }catch(err){
      const msg = err?.responseJSON?.message || 'Error';
      Swal.fire({icon:'error', title:msg});
    }
  });

  $(document).on('click','.btnBajaLotificacion', async function(){
    const id = $(this).data('id');
    const { value: motivo } = await Swal.fire({
      icon:'warning',
      title:'Dar de baja',
      input:'text',
      inputLabel:'Motivo de baja',
      inputPlaceholder:'Escribe el motivo...',
      showCancelButton:true,
      confirmButtonText:'Dar de baja',
      cancelButtonText:'Cancelar',
      inputValidator:(v)=> !v || v.trim().length<3 ? 'Motivo requerido (mín 3)' : undefined
    });
    if(!motivo) return;

    try{
      const url = R.baja.replace('__ID__', id);
      await $.post(url, {motivo});
      tbl.ajax.reload(null,false);
      Swal.fire({icon:'success', title:'Baja aplicada'});
    }catch(err){
      const msg = err?.responseJSON?.message || 'Error';
      Swal.fire({icon:'error', title:msg});
    }
  });
});