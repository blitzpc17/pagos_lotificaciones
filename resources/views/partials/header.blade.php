<header class="header">
  <div class="left">
    <button class="icon-btn" id="btnHamburger" title="Menú">
      <i class="fa-solid fa-bars"></i>
    </button>

    <div class="search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" placeholder="Buscar..." />
    </div>
  </div>

  <div class="userbox" id="userbox">
    <button class="userbtn" id="userbtn" type="button">
      <div class="who">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->username ?? 'U',0,1)) }}</div>
        <div class="meta">
          <b id="userName">{{ auth()->user()->username ?? 'Usuario' }}</b>
          <span id="userRole">{{ auth()->user()->rol->nombre ?? 'Rol' }}</span>
        </div>
      </div>
      <i class="fa-solid fa-chevron-down" style="color:var(--muted)"></i>
    </button>

    <div class="dropdown" id="userDropdown">
      <div class="head">
        <b>Sesión activa</b>
        <div><span id="userName2">{{ auth()->user()->username ?? 'Usuario' }}</span> · <span id="userRole2">{{ auth()->user()->rol->nombre ?? 'Rol' }}</span></div>
      </div>

      <button type="button" id="toggleTheme">
        <i class="fa-solid fa-circle-half-stroke"></i>
        Cambiar tema
      </button>

      <a href="#" onclick="return false;">
        <i class="fa-solid fa-user"></i>
        Mi perfil (demo)
      </a>

      <button type="button" class="danger" id="logoutBtn">
        <i class="fa-solid fa-right-from-bracket"></i>
        Cerrar sesión
      </button>
    </div>
  </div>
</header>
