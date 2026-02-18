$(function(){

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const openModal = (sel)=> $(sel).fadeIn(120);
  const closeModal = (sel)=> $(sel).fadeOut(120);
  $(document).on('click','[data-close]', function(){ closeModal($(this).data('close')); });
  $('.modal').on('click', function(e){ if(e.target === this) $(this).fadeOut(120); });

  function buildParams(){
    return {
      json: 1,
      tabla: $('#fTabla').val().trim(),
      accion: $('#fAccion').val().trim(),
      usuario_id: $('#fUsuario').val().trim(),
      desde: $('#fDesde').val(),
      hasta: $('#fHasta').val(),
    };
  }

  const dt = $('#tblAudit').DataTable({
    pageLength: 15,
    lengthChange: false,
    ajax: {
      url: '/auditoria',
      data: function(d){
        return buildParams();
      },
      dataSrc: (r)=> r?.data || []
    },
    columns: [
      { data:'created_at' },
      { data:'usuario_id' },
      { data:'accion' },
      { data:'tabla' },
      { data:'registro_id' },
      { data:'ip' },
      { data:'acciones_html', orderable:false, searchable:false },
    ],
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias"
    }
  });

  $('#btnAuditRefresh').on('click', ()=> dt.ajax.reload(null,false));
  $('#btnAuditFilter').on('click', ()=> dt.ajax.reload());

  $(document).on('click', '.btnAuditDetail', function(){
    const before = $(this).attr('data-before') || '';
    const after  = $(this).attr('data-after') || '';
    $('#audit_before').val(before);
    $('#audit_after').val(after);
    openModal('#modalAuditDetail');
  });

});
