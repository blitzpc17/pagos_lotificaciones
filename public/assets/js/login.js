(() => {
  const KEY = 'cms_theme';
  const saved = localStorage.getItem(KEY);
  if (saved) document.body.setAttribute('data-theme', saved);

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  const toggleTheme = () => {
    const cur = document.body.getAttribute('data-theme') || 'light';
    const next = cur === 'light' ? 'dark' : 'light';
    document.body.setAttribute('data-theme', next);
    localStorage.setItem(KEY, next);
    Swal.fire({ icon: 'success', title: 'Tema actualizado', text: 'Preferencia guardada.', timer: 1100, showConfirmButton: false });
  };

  document.getElementById('toggleTheme')?.addEventListener('click', toggleTheme);

  const pass = document.getElementById('password');
  const btn = document.getElementById('togglePass');

  btn?.addEventListener('click', () => {
    const isPwd = pass.type === 'password';
    pass.type = isPwd ? 'text' : 'password';
    btn.innerHTML = isPwd ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
  });

  const caps = document.getElementById('capsAlert');
  pass?.addEventListener('keyup', (e) => {
    const on = e.getModifierState && e.getModifierState('CapsLock');
    if (caps) caps.style.display = on ? 'flex' : 'none';
  });

  async function postJSON(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(body)
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const msg = data?.message || data?.errors?.login?.[0] || 'No se pudo iniciar sesión.';
      throw new Error(msg);
    }
    return data;
  }

  document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const login = document.getElementById('login')?.value?.trim();
    const pwd = document.getElementById('password')?.value?.trim();
    const remember = document.getElementById('remember')?.checked ? 1 : 0;

    if (!login || !pwd) {
      Swal.fire({ icon: 'error', title: 'Faltan datos', text: 'Completa usuario y contraseña.' });
      return;
    }

    try {
      Swal.fire({ title: 'Entrando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      const r = await postJSON(window.APP_LOGIN_URL || '/login', {
        login,
        password: pwd,
        remember
      });

      Swal.fire({ icon: 'success', title: 'Bienvenido', timer: 700, showConfirmButton: false })
        .then(() => window.location.href = r.redirect || (window.APP_DASHBOARD_URL || '/dashboard'));

    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Credenciales inválidas.' });
    }
  });
})();
