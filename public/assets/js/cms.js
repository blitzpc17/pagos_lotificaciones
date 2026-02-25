// public/assets/js/cms.js
$(function(){

  // ===== THEME (persist) =====
  const THEME_KEY = 'cms_theme';
  const savedTheme = localStorage.getItem(THEME_KEY);
  if(savedTheme) $('body').attr('data-theme', savedTheme);

  function toggleTheme(){
    const current = $('body').attr('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    $('body').attr('data-theme', next);
    localStorage.setItem(THEME_KEY, next);
  }

  // ===== Helpers: mobile =====
  const isMobile = () => window.matchMedia("(max-width: 980px)").matches;

  function openMobileSidebar(){
    $('#sidebar').addClass('mobile-open');
    $('#overlay').addClass('show');
  }
  function closeMobileSidebar(){
    $('#sidebar').removeClass('mobile-open');
    $('#overlay').removeClass('show');
  }

  // ===== Hamburger =====
  $('#btnHamburger').on('click', function(){
    if(isMobile()){
      $('#sidebar').hasClass('mobile-open') ? closeMobileSidebar() : openMobileSidebar();
    }else{
      $('#sidebar').toggleClass('collapsed');
      if($('#sidebar').hasClass('collapsed')){
        $('.submenu').hide();
        $('.nav-parent').removeClass('open');
      }
    }
  });

  $('#overlay').on('click', closeMobileSidebar);

  // ===== Multilevel menu =====
  $('.nav-parent').on('click', function(e){
    e.preventDefault();

    if(!isMobile() && $('#sidebar').hasClass('collapsed')){
      $('#sidebar').removeClass('collapsed');
    }

    const parentKey = $(this).data('parent');
    $(this).toggleClass('open');

    const $submenu = $(`.submenu[data-submenu="${parentKey}"]`);
    $submenu.slideToggle(140);
  });

  // ===== Active nav (parent stays active when child active by URL) =====
  $('.nav-link.active').each(function(){
    const parentKey = $(this).data('parent');
    if(parentKey){
      const $parentBtn = $(`.nav-parent[data-parent="${parentKey}"]`);
      const $submenu  = $(`.submenu[data-submenu="${parentKey}"]`);
      $parentBtn.addClass('active open');
      $submenu.show();
    }
  });

  // ===== User dropdown =====
  $('#userbtn').on('click', function(e){
    e.stopPropagation();
    $('#userDropdown').toggle();
  });

  $(document).on('click', function(){
    $('#userDropdown').hide();
  });

  $('#userDropdown').on('click', function(e){
    e.stopPropagation();
  });

  // Theme toggle
  $('#toggleTheme').on('click', function(){
    toggleTheme();
    $('#userDropdown').hide();
    Swal.fire({
      icon: 'success',
      title: 'Tema actualizado',
      text: 'Se guardó tu preferencia en el navegador.',
      timer: 1400,
      showConfirmButton: false
    });
  });

  // ===== Logout AJAX =====
  const csrf = $('meta[name="csrf-token"]').attr('content');

  async function postLogout(){
    const res = await fetch(window.APP_LOGOUT_URL || '/logout', {
      method: 'POST',
      headers: {
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      }
    });
    const data = await res.json().catch(()=> ({}));
    if(!res.ok) throw new Error(data?.message || 'No se pudo cerrar sesión');
    return data;
  }

  $('#logoutBtn').on('click', function(){
    Swal.fire({
      icon: 'warning',
      title: 'Cerrar sesión',
      text: '¿Seguro que deseas salir?',
      showCancelButton: true,
      confirmButtonText: 'Cerrar sesión',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#D9042B'
    }).then(async (r)=>{
      if(!r.isConfirmed) return;

      try{
        Swal.fire({ title:'Saliendo...', allowOutsideClick:false, didOpen:()=> Swal.showLoading() });
        const x = await postLogout();
        window.location.href = x.redirect || '/login';
      }catch(e){
        Swal.fire({ icon:'error', title:'Error', text: e.message || 'Error al cerrar sesión' });
      }
    });
  });

  // ===== Click outside sidebar (FIX: zonas seguras) =====
  $(document).on('click', function(e){
    const $t = $(e.target);

    const safe = $t.closest(
      '#sidebar, #btnHamburger, #userbox, #userbtn, #userDropdown, .dropdown, .swal2-container, .dataTables_wrapper, .modal .box'
    ).length > 0;

    if(safe) return;

    if(isMobile()){
      if($('#sidebar').hasClass('mobile-open')) closeMobileSidebar();
    }else{
      if(!$('#sidebar').hasClass('collapsed')){
        $('#sidebar').addClass('collapsed');
        $('.submenu').hide();
        $('.nav-parent').removeClass('open');
      }
    }
  });

  // Resize
  $(window).on('resize', function(){
    if(!isMobile()){
      closeMobileSidebar();
    }
  });

});


// ==============================
// DataTables defaults GLOBAL
// ==============================
if (window.jQuery && $.fn && $.fn.dataTable) {
  $.extend(true, $.fn.dataTable.defaults, {
    responsive: true,
    autoWidth: false,
    scrollX: false,          // no choca con Responsive
    pageLength: 10,
    lengthChange: false,
    language: {
      search: "Buscar:",
      paginate: { previous: "‹", next: "›" },
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "No se encontraron coincidencias",
      lengthMenu: "Mostrar _MENU_"
    }
  });
}


// =============================================
// DataTables: init responsivo genérico
// (evita Cannot reinitialise)
// =============================================
window.initCmsDataTable = function(selector, opts){
  const $t = $(selector);
  if(!$t.length) return null;

  if($.fn.DataTable.isDataTable($t)){
    $t.DataTable().destroy();
    $t.find('tbody').empty();
  }
  return $t.DataTable(opts || {});
};


// ==============================
// MAYÚSCULAS (valor real)
// - Excepto email/password (no lo fuerzo)
// ==============================
$(document).on('input', 'input[type="text"], input[type="search"], input[type="tel"], textarea', function(){
  this.value = (this.value || '').toUpperCase();
});


// ==============================
// TABS GLOBAL (folder style)
// ==============================
$(document).on('click', '.tabs .tab-btn', function(){
  const $btn = $(this);
  const tabKey = $btn.data('tab');
  const $tabs = $btn.closest('.tabs');

  $tabs.find('.tab-btn').removeClass('active');
  $btn.addClass('active');

  $tabs.find('.tab-panel').removeClass('active');
  $tabs.find(`.tab-panel[data-panel="${tabKey}"]`).addClass('active');
});

// helper opcional (abrir siempre en el 1er tab)
window.resetTabs = function(tabsSelector){
  const $tabs = $(tabsSelector);
  if(!$tabs.length) return;
  const $first = $tabs.find('.tab-btn').first();
  if($first.length) $first.trigger('click');
};