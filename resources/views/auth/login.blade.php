@extends('layouts.auth')

@section('title', 'Login - CMS')

@section('head')
  <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}"/>
@endsection

@section('content')
<main class="auth">
  <section class="auth-card">

    <!-- LEFT / HERO -->
    <aside class="hero">
      <div class="hero-overlay"></div>

      <div class="mini-logo">
        <img src="{{ asset('assets/logo-login.png') }}" alt="Logo"/>
      </div>

      <div class="hero-content">
        <h2>Bienvenido de vuelta</h2>
        <p>Administra contenido, usuarios, módulos y métricas desde un solo panel.</p>

        <div class="hero-pills">
          <span><i class="fa-solid fa-shield-halved"></i> Roles & permisos</span>
          <span><i class="fa-solid fa-chart-line"></i> Métricas</span>
          <span><i class="fa-solid fa-bolt"></i> Rápido</span>
        </div>

        <div class="hero-stats">
          <div class="hs"><b>99.9%</b><span>Uptime</span></div>
          <div class="hs"><b>2s</b><span>Carga</span></div>
          <div class="hs"><b>24/7</b><span>Soporte</span></div>
        </div>
      </div>
    </aside>

    <!-- RIGHT / FORM -->
    <div class="panel">
      <header class="panel-head">
        <div class="brand">
          <div class="badge-logo">
            <img src="{{ asset('assets/logo-login.png') }}" alt="Logo"/>
          </div>
          <div class="brand-text">
            <b>Laravel CMS</b>
            <span>Acceso al panel</span>
          </div>
        </div>

        <button class="theme" type="button" id="toggleTheme" title="Cambiar tema">
          <i class="fa-solid fa-circle-half-stroke"></i>
        </button>
      </header>

      <h1>Iniciar sesión</h1>
      <p class="sub">Ingresa tus datos para continuar.</p>

      <div class="caps" id="capsAlert" style="display:none;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Bloq Mayús activado</span>
      </div>

      <form id="loginForm" autocomplete="on">
        <div class="field">
          <label>Correo / Usuario</label>
          <div class="input">
            <i class="fa-regular fa-user"></i>
            <input id="login" type="text" placeholder="ej. admin@dominio.com" required />
          </div>
        </div>

        <div class="field">
          <label>Contraseña</label>
          <div class="input">
            <i class="fa-solid fa-lock"></i>
            <input id="password" type="password" placeholder="••••••••" required />
            <button class="eye" type="button" id="togglePass" aria-label="Mostrar contraseña">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>

          <div class="row">
            <label class="check">
              <input type="checkbox" id="remember"/>
              <span>Recordarme</span>
            </label>
            <a href="#" class="link" onclick="return false;">¿Olvidaste tu contraseña?</a>
          </div>
        </div>

        <button class="btn primary" type="submit">
          <span>Entrar</span>
          <i class="fa-solid fa-arrow-right"></i>
        </button>

        <footer class="foot">
          <span>© {{ date('Y') }} · CMS</span>
          <span class="muted">Tu paleta en modo premium</span>
        </footer>
      </form>
    </div>

  </section>
</main>
@endsection

@section('scripts')
  <script>
    window.APP_LOGIN_URL = "{{ route('login.ajax') }}";
    window.APP_DASHBOARD_URL = "{{ route('dashboard') }}";
  </script>
  <script src="{{ asset('assets/js/login.js') }}"></script>
@endsection
