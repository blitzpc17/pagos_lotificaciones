$(function(){

  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  function openModal(sel){ $(sel).fadeIn(120); }
  function closeModal(sel){ $(sel).fadeOut(120); }

  $(document).on('click', '[data-close]', function(){
    closeModal($(this).data('close'));
  });

  $('.modal').on('click', function(e){
    if(e.target === this) $(this).fadeOut(120);
  });

  const R = window.ROUTES?.boletas || {};
  const urlWithId = (tpl, id)=> (tpl || '').replace('__ID__', id);

  function toastOk(msg){
    Swal.fire({ icon:'success', title: msg || 'Listo', timer:1200, showConfirmButton:false });
  }
  function toastErr(xhr, fallback){
    Swal.fire({ icon:'error', title: (xhr?.responseJSON?.message || fallback || 'Error') });
  }

  // ====== DATATABLE BOLETAS ======
  const dt = $('#tblBoletas').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias"
    },
    ajax: { url: R.datatable, type: 'GET' },
    columns: [
      {data:'id', name:'id'},
      {data:'folio', name:'folio'},
      {data:'cliente', name:'cliente'},
      {data:'lotificacion', name:'lotificacion'},
      {data:'lote', name:'lote'},
      {data:'tipo_venta', name:'tipo_venta'},
      {data:'saldo', name:'saldo', orderable:false, searchable:false},
      {data:'acciones', name:'acciones', orderable:false, searchable:false, className:'dt-body-right'}
    ]
  });

  // ====== MODAL PARTIDAS ======
  let dtPartidas = null;

  async function openPagosModal(boletaId){
    $('#bp_boleta_id').val(boletaId);
    $('#bp_fecha_pago').val(new Date().toISOString().slice(0,10));
    $('#bp_tipo_pago').val('ABONO');
    $('#bp_monto').val('0');
    $('#bp_recargo').val('0');
    $('#bp_monto_recargo').val('0');
    $('#bp_observacion').val('');

    openModal('#modalBoletaPagos');
    await reloadPartidas(boletaId);
  }

  async function reloadPartidas(boletaId){
    const res = await $.get(urlWithId(R.partidas, boletaId));
    const rows = res.data || [];

    if(dtPartidas){
      dtPartidas.clear().rows.add(rows).draw();
      return;
    }

    dtPartidas = $('#tblBoletaPartidas').DataTable({
      data: rows,
      responsive: true,
      autoWidth: false,
      pageLength: 8,
      language: {
        search: "Buscar:",
        paginate: { previous: "‹", next: "›" },
        info: "Mostrando _START_ a _END_ de _TOTAL_",
        infoEmpty: "Sin registros",
        zeroRecords: "No se encontraron coincidencias"
      },
      columns: [
        {data:'id'},
        {data:'folio_partida'},
        {data:'fecha_pago'},
        {data:'tipo_pago'},
        {data:'monto'},
        {data:'recargo'},
        {data:'total'},
        {data:'recibo', orderable:false, searchable:false, className:'dt-body-right'}
      ]
    });
  }

  // abrir modal pagos
  $(document).on('click', '.btnBoletaPagos', async function(){
    const id = $(this).data('id');
    await openPagosModal(id);
  });

  // agregar pago
  $('#btnBPAddPago').on('click', async function(){
    const boletaId = $('#bp_boleta_id').val();
    if(!boletaId) return;

    try{
      const rec = $('#bp_recargo').val() === '1';

      const body = {
        fecha_pago: $('#bp_fecha_pago').val(),
        tipo_pago: $('#bp_tipo_pago').val(),
        monto: $('#bp_monto').val(),
        recargo: rec,
        monto_recargo: rec ? $('#bp_monto_recargo').val() : 0,
        observacion: $('#bp_observacion').val()
      };

      const r = await $.ajax({
        url: urlWithId(R.addPago, boletaId),
        type: 'POST',
        data: JSON.stringify(body),
        contentType: 'application/json'
      });

      toastOk('Pago agregado');
      await reloadPartidas(boletaId);
      dt.ajax.reload(null,false);

      if(r?.partida_id){
        window.open(urlWithId(R.reciboPartida, r.partida_id), '_blank');
      }

    }catch(xhr){
      toastErr(xhr,'No se pudo agregar el pago');
    }
  });

  // imprimir recibo desde tabla
  $(document).on('click', '.btnBPRecibo', function(){
    const pid = $(this).data('id');
    window.open(urlWithId(R.reciboPartida, pid), '_blank');
  });

});