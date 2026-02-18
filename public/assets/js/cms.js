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
  // Si un child tiene class active (blade), abre su parent
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

  // ===== Click outside sidebar =====
  $(document).on('click', function(e){
    const $t = $(e.target);
    const clickedSidebar = $t.closest('#sidebar').length > 0;
    const clickedHamburger = $t.closest('#btnHamburger').length > 0;

    if(clickedSidebar || clickedHamburger) return;

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

  // ===== Dashboard demo buttons (si existen) =====
  $('#btnNotify').on('click', ()=> Swal.fire({ icon:'info', title:'Notificaciones', text:'Aquí abrirías tu panel de notificaciones.', confirmButtonColor:'#D9042B' }));
  $('#btnQuick').on('click', ()=> Swal.fire({ icon:'success', title:'Acción rápida', text:'Ejecutada.', confirmButtonColor:'#D9042B' }));
  $('#btnFilter').on('click', ()=> Swal.fire({ icon:'question', title:'Filtros', text:'Aquí mostrarías filtros avanzados.', confirmButtonColor:'#D9042B' }));
  $('#btnHealth').on('click', ()=> Swal.fire({ icon:'info', title:'Estado del sistema', text:'Detalle del sistema.', confirmButtonColor:'#D9042B' }));

  // Resize
  $(window).on('resize', function(){
    if(!isMobile()){
      closeMobileSidebar();
    }
  });

});
