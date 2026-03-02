$(function(){

  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  // MAYÚSCULAS
  $(document).on('input', 'input[type="text"], input[type="search"], textarea', function(){
    this.value = (this.value || '').toUpperCase();
  });

  function openModal(sel){ $(sel).fadeIn(120); }
  function closeModal(sel){ $(sel).fadeOut(120); }

  $(document).on('click', '[data-close]', function(){
    closeModal($(this).data('close'));
  });

  $('.modal').on('click', function(e){
    if(e.target === this) $(this).fadeOut(120);
  });

  const R = window.ROUTES?.pagosProveedor || {};
  const urlWithId = (tpl, id)=> (tpl || '').replace('__ID__', id);

  function toastOk(msg){
    Swal.fire({ icon:'success', title: msg || 'Listo', timer:1200, showConfirmButton:false });
  }
  function toastErr(xhr, fallback){
    Swal.fire({ icon:'error', title: (xhr?.responseJSON?.message || fallback || 'Error') });
  }

  // ====== DATATABLE PRINCIPAL ======
  const dt = $('#tblPagoProveedor').DataTable({
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
      {data:'proveedor', name:'proveedor'},
      {data:'fecha_documento', name:'fecha_documento'},
      {data:'monto_total', name:'monto_total'},
      {data:'estatus', name:'estatus', orderable:false, searchable:false},
      {data:'acciones', name:'acciones', orderable:false, searchable:false, className:'dt-body-right'}
    ]
  });

  // ====== SELECT PROVEEDORES ======
  async function loadProveedoresSelect(){
    try{
      const res = await $.get(R.proveedoresSelect);
      const $s = $('#pp_add_proveedor_id');
      $s.empty();
      (res.data || []).forEach(it=>{
        $s.append(`<option value="${it.id}">${it.text}</option>`);
      });
    }catch(e){
      // silencio
    }
  }

  // ====== NUEVO PAGO ======
  $('#btnPagoProvAdd').on('click', async function(){
    await loadProveedoresSelect();
    $('#pp_add_fecha_documento').val(new Date().toISOString().slice(0,10));
    $('#pp_add_concepto').val('');
    $('#pp_add_referencia').val('');
    $('#pp_add_monto_total').val('0');
    $('#pp_add_observaciones').val('');
    openModal('#modalPagoProvAdd');
  });

  $('#btnPagoProvSaveAdd').on('click', async function(){
    try{
      const body = {
        proveedor_id: $('#pp_add_proveedor_id').val(),
        fecha_documento: $('#pp_add_fecha_documento').val(),
        concepto: $('#pp_add_concepto').val(),
        referencia: $('#pp_add_referencia').val(),
        monto_total: $('#pp_add_monto_total').val(),
        observaciones: $('#pp_add_observaciones').val()
      };

      const r = await $.ajax({
        url: R.store,
        type: 'POST',
        data: JSON.stringify(body),
        contentType: 'application/json'
      });

      toastOk('Pago creado');
      closeModal('#modalPagoProvAdd');
      dt.ajax.reload(null,false);

      // abre partidas directo
      if(r?.id){
        await openPartidasModal(r.id);
      }

    }catch(xhr){
      toastErr(xhr,'No se pudo guardar');
    }
  });

  // ====== MODAL PARTIDAS ======
  let dtPartidas = null;

  async function openPartidasModal(pagoId){
    $('#pp_partidas_pago_id').val(pagoId);
    $('#pp_part_fecha_pago').val(new Date().toISOString().slice(0,10));
    $('#pp_part_monto').val('0');
    $('#pp_part_referencia_pago').val('');
    $('#pp_part_observacion').val('');

    openModal('#modalPagoProvPartidas');

    // carga partidas en tabla
    await reloadPartidasTable(pagoId);
  }

  async function reloadPartidasTable(pagoId){
    try{
      const res = await $.get(urlWithId(R.partidas, pagoId));
      const rows = res.data || [];

      if(dtPartidas){
        dtPartidas.clear().rows.add(rows).draw();
        return;
      }

      dtPartidas = $('#tblPagoProvPartidas').DataTable({
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
          {data:'forma_pago'},
          {data:'tipo_partida'},
          {data:'monto'},
          {data:'referencia_pago'},
          {data:'recibo', orderable:false, searchable:false, className:'dt-body-right'}
        ]
      });

    }catch(e){
      // noop
    }
  }

  // abrir modal partidas desde acciones
  $(document).on('click', '.btnPPPartidas', async function(){
    const id = $(this).data('id');
    await openPartidasModal(id);
  });

  // agregar partida
  $('#btnPPAddPartida').on('click', async function(){
    const pagoId = $('#pp_partidas_pago_id').val();
    if(!pagoId) return;

    try{
      const body = {
        fecha_pago: $('#pp_part_fecha_pago').val(),
        forma_pago: $('#pp_part_forma_pago').val(),
        tipo_partida: $('#pp_part_tipo_partida').val(),
        monto: $('#pp_part_monto').val(),
        referencia_pago: $('#pp_part_referencia_pago').val(),
        observacion: $('#pp_part_observacion').val()
      };

      const r = await $.ajax({
        url: urlWithId(R.addPartida, pagoId),
        type: 'POST',
        data: JSON.stringify(body),
        contentType: 'application/json'
      });

      toastOk('Partida agregada');
      await reloadPartidasTable(pagoId);

      // abrir recibo si quieres automático:
      if(r?.partida_id){
        window.open(urlWithId(R.reciboPartida, r.partida_id), '_blank');
      }

    }catch(xhr){
      toastErr(xhr,'No se pudo agregar partida');
    }
  });

  // Recibo desde tabla partidas
  $(document).on('click', '.btnPPRecibo', function(){
    const pid = $(this).data('id');
    window.open(urlWithId(R.reciboPartida, pid), '_blank');
  });

});