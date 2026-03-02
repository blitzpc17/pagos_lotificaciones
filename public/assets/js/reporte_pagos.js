$(function(){

  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  const R = window.ROUTES?.reportePagos || {};

  function mesNombre(m){
    const meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    const idx = parseInt(m,10)-1;
    return meses[idx] || '';
  }

  function filtros(){
    return {
      month: $('#rep_mes').val(),
      year: $('#rep_anio').val(),
      oficina: $('#rep_oficina').val(),
      lotificacion_id: $('#rep_lotificacion').val()
    };
  }

  function setHeaderRealPagado(){
    const f = filtros();
    $('#thRealPagado').text(`REAL PAGADO ${mesNombre(f.month)} ${f.year}`);
  }

  async function loadTotales(){
    try{
      const f = filtros();
      const res = await $.get(R.totales, f);
      const money = v => '$ ' + (parseFloat(v||0).toFixed(2)).replace(/\d(?=(\d{3})+\.)/g, '$&,');

      $('#ftRealPagado').text(money(res.real_pagado));
      $('#ftEnganche').text(money(res.enganche));
      $('#ftRecargo').text(money(res.recargo));
    }catch(e){
      $('#ftRealPagado,#ftEnganche,#ftRecargo').text('');
    }
  }

  setHeaderRealPagado();

  const dt = $('#tblReportePagos').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    pageLength: 25,
    order: [],
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias"
    },
    ajax: {
      url: R.datatable,
      type: 'GET',
      data: function(d){
        Object.assign(d, filtros());
      }
    },
    columns: [
      {data:'oficina', name:'oficina'},
      {data:'lotificacion', name:'lotificacion'},
      {data:'lote', name:'lote'},
      {data:'cliente', name:'cliente'},
      {data:'telefono', name:'telefono'},
      {data:'mensualidad', name:'mensualidad', className:'dt-body-right'},
      {data:'real_pagado', name:'real_pagado', className:'dt-body-right'},
      {data:'enganche', name:'enganche', className:'dt-body-right'},
      {data:'recargo', name:'recargo', className:'dt-body-right'},
      {data:'folio', name:'folio'},
      {data:'observacion', name:'observacion'}
    ],
    drawCallback: function(){
      loadTotales();
    }
  });

  $('#btnRepFiltrar').on('click', function(){
    setHeaderRealPagado();
    dt.ajax.reload();
  });

  $('#rep_mes,#rep_anio').on('change', function(){
    setHeaderRealPagado();
  });

  $('#btnRepExportCsv').on('click', function(){
    const f = filtros();
    const qs = $.param(f);
    window.location.href = R.exportCsv + '?' + qs;
  });

});