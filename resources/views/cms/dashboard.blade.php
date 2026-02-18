@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="breadcrumb">
  <div class="path">
    <span>Inicio</span><span>›</span><b>Dashboard</b>
  </div>
  <div class="actions">
    <button class="btn ghost" type="button" id="btnNotify">
      <i class="fa-regular fa-bell"></i> Notificaciones
    </button>
    <button class="btn primary" type="button" id="btnQuick">
      <i class="fa-solid fa-wand-magic-sparkles"></i> Acción rápida
    </button>
  </div>
</div>

<div class="grid">
  <div class="card metric">
    <div class="top">
      <div>
        <div class="label">Registros</div>
        <div class="value">1,248</div>
      </div>
      <span class="badge"><i class="fa-solid fa-database"></i> Total</span>
    </div>
    <div class="trend"><i class="fa-solid fa-arrow-trend-up" style="color:var(--success)"></i> +12% esta semana</div>
  </div>

  <div class="card metric">
    <div class="top">
      <div>
        <div class="label">Usuarios activos</div>
        <div class="value">86</div>
      </div>
      <span class="badge"><i class="fa-solid fa-users"></i> Online</span>
    </div>
    <div class="trend"><i class="fa-solid fa-circle" style="color:var(--info)"></i> 9 en este momento</div>
  </div>

  <div class="card metric">
    <div class="top">
      <div>
        <div class="label">Pendientes</div>
        <div class="value">23</div>
      </div>
      <span class="badge"><i class="fa-solid fa-triangle-exclamation"></i> Atención</span>
    </div>
    <div class="trend"><i class="fa-solid fa-arrow-trend-down" style="color:var(--warn)"></i> -4 vs ayer</div>
  </div>

  <div class="card metric">
    <div class="top">
      <div>
        <div class="label">Tickets</div>
        <div class="value">14</div>
      </div>
      <span class="badge"><i class="fa-solid fa-headset"></i> Soporte</span>
    </div>
    <div class="trend"><i class="fa-solid fa-clock" style="color:var(--muted)"></i> SLA promedio 2h</div>
  </div>

  <div class="card" style="grid-column: span 8;">
    <div class="panel-title">
      <div>
        <h3>Actividad reciente</h3>
        <div class="sub">Demo</div>
      </div>
      <button class="btn" type="button" id="btnFilter"><i class="fa-solid fa-filter"></i> Filtros</button>
    </div>

    <div class="list">
      <div class="list-item"><b>Se creó un usuario</b><div class="muted">Hace 12 min · por Admin</div></div>
      <div class="list-item"><b>Se actualizó una boleta</b><div class="muted">Hace 1 hora · por Ventas</div></div>
      <div class="list-item"><b>Se registró una partida</b><div class="muted">Ayer · por Cobranza</div></div>
    </div>
  </div>

  <div class="card" style="grid-column: span 4;">
    <div class="panel-title">
      <div>
        <h3>Estado del sistema</h3>
        <div class="sub">Salud general</div>
      </div>
    </div>

    <div class="stack">
      <div class="badge row-between"><span><i class="fa-solid fa-server"></i> API</span><b style="color:var(--success)">OK</b></div>
      <div class="badge row-between"><span><i class="fa-solid fa-database"></i> DB</span><b style="color:var(--success)">OK</b></div>
      <div class="badge row-between"><span><i class="fa-solid fa-envelope"></i> Email</span><b style="color:var(--warn)">Lento</b></div>
      <button class="btn primary" type="button" id="btnHealth" style="margin-top:8px;">
        <i class="fa-solid fa-bolt"></i> Ver detalle
      </button>
    </div>
  </div>
</div>
@endsection
