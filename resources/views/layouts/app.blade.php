<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>@yield('title', 'CMS')</title>

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- CSS base -->
  <link rel="stylesheet" href="{{ asset('assets/css/cms.css') }}"/>
  @stack('styles')

  <!-- jQuery (primero) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"/>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body data-theme="light">
  <div class="overlay" id="overlay"></div>

  <div class="app">

    {{-- Sidebar --}}
    @include('partials.sidebar')

    <main class="main">
      {{-- Header --}}
      @include('partials.header')

      {{-- Content --}}
      <section class="content">
        @yield('content')
      </section>
    </main>

  </div>

  <!-- Variables globales JS -->
  <script>
    window.APP = {
      csrf: "{{ csrf_token() }}",
      logoutUrl: "{{ route('logout') }}",
      loginUrl: "{{ route('login') }}",
    };
  </script>

  <!-- JS base -->
  <script src="{{ asset('assets/js/cms.js') }}"></script>

  <!-- Hook global: logout AJAX (por si tu cms.js solo muestra demo) -->
  <script>
    $(function(){
      $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
      });

      // Si existe el botón logout en el header partial
      $(document).on('click', '#logoutBtn', function(){
        Swal.fire({
          icon: 'warning',
          title: 'Cerrar sesión',
          text: '¿Deseas cerrar sesión?',
          showCancelButton: true,
          confirmButtonText: 'Cerrar sesión',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#D9042B'
        }).then(async (r)=>{
          if(!r.isConfirmed) return;

          try{
            const resp = await $.post(window.APP.logoutUrl);
            const redirect = resp?.redirect || window.APP.loginUrl;
            window.location.href = redirect;
          }catch(e){
            Swal.fire({ icon:'error', title:'Error', text:'No se pudo cerrar sesión.' });
          }
        });
      });
    });
  </script>

  @stack('scripts')
</body>
</html>
