$(function(){

  const R = window.ROUTES?.lotResumen || {};

  function mesNombre(m){
    const meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    return meses[(parseInt(m,10)-1)] || '';
  }

  function filtros(){
    return {
      month: $('#rl_mes').val(),
      year: $('#rl_anio').val(),
      oficina: $('#rl_oficina').val()
    };
  }

  function setHeader(){
    const f = filtros();
    const label = `${mesNombre(f.month)} ${f.year}`;
    $('#thIngresoMes').text(`INGRESO ${label}`);
    $('#ttlRes').text(`Resumen por lotificación (${label})`);
  }

  async function loadTotales(){
    try{
      const res = await $.get(R.totales, filtros());
      const money = v => '$ ' + (parseFloat(v||0).toFixed(2)).replace(/\d(?=(\d{3})+\.)/g, '$&,');

      $('#ftContratos').text(money(res.contratos));
      $('#ftEnganches').text(money(res.enganches));
      $('#ftCobrado').text(money(res.cobrado));
      $('#ftResto').text(money(res.resto));
      $('#ftIngreso').text(money(res.ingreso));
    }catch(e){
      $('#ftContratos,#ftEnganches,#ftCobrado,#ftResto,#ftIngreso').text('');
    }
  }

  setHeader();

  const dt = $('#tblLotResumen').DataTable({
    processing:true,
    serverSide:true,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    pageLength:25,
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
      {data:'contratos', name:'contratos', className:'dt-body-right'},
      {data:'enganches', name:'enganches', className:'dt-body-right'},
      {data:'cobrado', name:'cobrado', className:'dt-body-right'},
      {data:'resto_por_cobrar', name:'resto_por_cobrar', className:'dt-body-right'},
      {data:'ingreso_mensual', name:'ingreso_mensual', className:'dt-body-right'}
    ],
    drawCallback: function(){
      loadTotales();
    }
  });

  $('#btnRLFiltrar').on('click', function(){
    setHeader();
    dt.ajax.reload();
  });

  $('#rl_mes,#rl_anio').on('change', function(){
    setHeader();
  });

  $('#btnRLExportCsv').on('click', function(){
    const qs = $.param(filtros());
    window.location.href = R.exportCsv + '?' + qs;
  });

  $('#btnRLExportXlsx').on('click', function(){
    const qs = $.param(filtros());
    window.location.href = R.exportXlsx + '?' + qs;
  });

});