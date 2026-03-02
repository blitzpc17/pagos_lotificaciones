$(function(){

  const R = window.ROUTES?.corteCaja || {};

  function mesNombre(m){
    const meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    return meses[(parseInt(m,10)-1)] || '';
  }
  function filtros(){
    return {
      month: $('#cc_mes').val(),
      year: $('#cc_anio').val(),
      oficina: $('#cc_oficina').val(),
      lotificacion_id: $('#cc_lotificacion').val()
    };
  }
  function setHeaders(){
    const f = filtros();
    const label = `${mesNombre(f.month)} ${f.year}`;
    $('#thIngresoMes').text(`INGRESO ${label}`);
    $('#thCarteraMes').text(`CARTERA P/${label}`);
    $('#ttlCorte').text(label);
  }

  async function loadTotales(){
    try{
      const res = await $.get(R.totales, filtros());
      const money = v => '$ ' + (parseFloat(v||0).toFixed(2)).replace(/\d(?=(\d{3})+\.)/g, '$&,');
      $('#ftIngresoMes').text(money(res.ingreso_mes));
      $('#ftCarteraMes').text(money(res.cartera_mes));
    }catch(e){
      $('#ftIngresoMes,#ftCarteraMes').text('');
    }
  }

  setHeaders();

  const dt = $('#tblCorteCaja').DataTable({
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
    rowGroup: {
      dataSrc: ['cliente_group','boleta_group'],
      startRender: function(rows, group, level){
        if(level === 0){
          return $('<tr/>')
            .append('<td colspan="18" style="background:#eef2ff;font-weight:900;">CLIENTE: '+ group +'</td>');
        }
        if(level === 1){
          return $('<tr/>')
            .append('<td colspan="18" style="background:#f8fafc;font-weight:800;">BOLETA ID: '+ group +'</td>');
        }
      }
    },
    columns: [
      {data:'lugar', name:'lugar'},
      {data:'no_lotes', name:'no_lotes'},
      {data:'fecha', name:'fecha'},
      {data:'socio', name:'socio'},
      {data:'vendedor', name:'vendedor'},
      {data:'lotificacion', name:'lotificacion'},
      {data:'lote', name:'lote'},
      {data:'mz', name:'mz'},
      {data:'nombre_cliente', name:'nombre_cliente'},
      {data:'estatus', name:'estatus'},
      {data:'costo_contado', name:'costo_contado', className:'dt-body-right'},
      {data:'costo_credito', name:'costo_credito', className:'dt-body-right'},
      {data:'enganche', name:'enganche', className:'dt-body-right'},
      {data:'comision', name:'comision', className:'dt-body-right'},
      {data:'ingreso_cartera_global', name:'ingreso_cartera_global', className:'dt-body-right col-icg'},
      {data:'ingreso_mes', name:'ingreso_mes', className:'dt-body-right'},
      {data:'meses', name:'meses', className:'dt-body-right'},
      {data:'cartera_mes', name:'cartera_mes', className:'dt-body-right'}
    ],
    createdRow: function(row, data){
      // pinta celda socio con su color (si viene "A - B" tomamos el 1er color)
      const color = (data.socio_color || '').split(' - ')[0].trim();
      if(color){
        // socio es columna index 3
        $('td', row).eq(3).css({
          background: color,
          color: '#fff',
          fontWeight: '900'
        });
      }
    },
    drawCallback: function(){
      loadTotales();
    }
  });

  $('#btnCCFiltrar').on('click', function(){
    setHeaders();
    dt.ajax.reload();
  });

  $('#cc_mes,#cc_anio').on('change', function(){
    setHeaders();
  });

  $('#btnCCExportCsv').on('click', function(){
    const qs = $.param(filtros());
    window.location.href = R.exportCsv + '?' + qs;
  });

});