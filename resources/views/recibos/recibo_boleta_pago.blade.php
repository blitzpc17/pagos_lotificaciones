<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Recibo pago boleta</title>
  <style>
    @page { size: 5.5in 8.5in; margin: 0.35in; } /* ✅ media carta */
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color:#111; }
    .row { display:flex; justify-content:space-between; gap:10px; }
    .box { border:1px solid #111; padding:10px; border-radius:8px; }
    h1 { font-size: 16px; margin:0; }
    h2 { font-size: 13px; margin:0; }
    .muted { color:#444; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    td, th { border:1px solid #111; padding:6px; }
    th { text-align:left; }
    .right { text-align:right; }
    .mt { margin-top:10px; }
    .sign { margin-top:18px; display:flex; gap:12px; }
    .line { flex:1; border-top:1px solid #111; padding-top:6px; text-align:center; }
    .no-print { margin-top:10px; }
    @media print { .no-print{display:none;} }
  </style>
</head>
<body>
  <div class="row">
    <div>
      <h1>RECIBO DE PAGO</h1>
      <div class="muted">Boleta: <b>{{ $boleta->folio }}</b></div>
    </div>
    <div class="right">
      <div><b>Folio pago:</b> {{ $partida->folio_partida }}</div>
      <div><b>Fecha:</b> {{ \Carbon\Carbon::parse($partida->fecha_pago)->format('d/m/Y') }}</div>
    </div>
  </div>

  <div class="box mt">
    <h2>Datos</h2>
    <div class="mt">
      <div><b>Cliente:</b> {{ $clienteNombre }}</div>
      <div><b>Lotificación:</b> {{ $lotificacion->nombre ?? '—' }}</div>
      <div><b>Lote:</b> {{ $lote->clave_lote ?? '—' }}</div>
      <div><b>Tipo venta:</b> {{ $boleta->tipo_venta }}</div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Concepto</th>
        <th class="right">Monto</th>
        <th class="right">Recargo</th>
        <th class="right">Total</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $partida->tipo_pago }}</td>
        <td class="right">$ {{ number_format($partida->monto,2) }}</td>
        <td class="right">$ {{ number_format($partida->monto_recargo ?? 0,2) }}</td>
        <td class="right"><b>$ {{ number_format(($partida->monto + ($partida->monto_recargo ?? 0)),2) }}</b></td>
      </tr>
    </tbody>
  </table>

  <div class="box mt">
    <div><b>Observación:</b> {{ $partida->observacion ?? '—' }}</div>
    <div class="muted mt">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
  </div>

  <div class="sign">
    <div class="line">Recibí</div>
    <div class="line">Entregué</div>
  </div>

  <div class="no-print">
    <button onclick="window.print()">Imprimir</button>
    <button onclick="window.close()">Cerrar</button>
  </div>
</body>
</html>