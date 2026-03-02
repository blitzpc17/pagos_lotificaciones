$(function(){
  $.ajaxSetup({ headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const R = window.ROUTES.lotes;
  const $modal = $('#modalLote');

  const tbl = $('#tblLotes').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    ajax: {
      url: R.datatable,
      data: function(d){
        d.lotificacion_id = $('#flt_lotificacion').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'lotificacion'},
      {data:'clave_lote'},
      {data:'manzana'},
      {data:'numero'},
      {data:'estado'},
      {data:'costo_contado'},
      {data:'costo_credito'},
      {data:'estatus', orderable:false, searchable:false},
      {data:'acciones', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });

  $('#flt_lotificacion').on('change', ()=> tbl.ajax.reload());

  function openModal(isEdit){
    $('#loteModalTitle').html(isEdit ? '<i class="fa-regular fa-pen-to-square"></i> Editar Lote' : '<i class="fa-solid fa-border-all"></i> Nuevo Lote');
    $modal.addClass('show');
  }

  function clearForm(){
    $('#lote_id').val('');
    $('#lote_clave,#lote_manzana,#lote_numero').val('');
    $('#lote_estado').val('LIBRE');
    $('#lote_contado,#lote_credito').val(0);
    $('#lote_notas').val('');
  }

  $('#btnLoteAdd').on('click', function(){
    clearForm();
    const lid = $('#flt_lotificacion').val();
    if(lid) $('#lote_lotificacion_id').val(lid);
    openModal(false);
  });

  $(document).on('click','.btnEditLote', async function(){
    const id = $(this).data('id');
    const url = R.show.replace('__ID__', id);
    const r = await $.get(url);
    clearForm();
    $('#lote_id').val(r.id);
    $('#lote_lotificacion_id').val(r.lotificacion_id);
    $('#lote_clave').val(r.clave_lote);
    $('#lote_manzana').val(r.manzana || '');
    $('#lote_numero').val(r.numero || '');
    $('#lote_estado').val(r.estado);
    $('#lote_contado').val(r.costo_contado || 0);
    $('#lote_credito').val(r.costo_credito || 0);
    $('#lote_notas').val(r.notas || '');
    openModal(true);
  });

  $('#btnLoteSave').on('click', async function(){
    const id = $('#lote_id').val();
    const payload = {
      lotificacion_id: parseInt($('#lote_lotificacion_id').val(),10),
      clave_lote: $('#lote_clave').val().trim(),
      manzana: $('#lote_manzana').val().trim() || null,
      numero: $('#lote_numero').val().trim() || null,
      estado: $('#lote_estado').val(),
      costo_contado: parseFloat($('#lote_contado').val()||'0'),
      costo_credito: parseFloat($('#lote_credito').val()||'0'),
      notas: $('#lote_notas').val().trim() || null,
    };

    try{
      if(!payload.clave_lote) return Swal.fire({icon:'warning', title:'Clave requerida'});
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

  $(document).on('click','.btnBajaLote', async function(){
    const id = $(this).data('id');
    const { value: motivo } = await Swal.fire({
      icon:'warning',
      title:'Dar de baja lote',
      input:'text',
      inputLabel:'Motivo de baja',
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