<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subjectLine ?? 'Recordatorio de sesión' }}</title>
  <style>
    /* Estilos inlined básicos para compatibilidad */
    body { margin:0; padding:0; background:#f6f7fb; color:#0f172a; }
    .wrapper { width:100%; background:#f6f7fb; padding:24px 0; }
    .container { max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04); }
    .header { background:linear-gradient(135deg,#4f46e5,#06b6d4); color:#fff; padding:20px 24px; }
    .brand { font-size:18px; font-weight:700; letter-spacing:.2px; }
    .content { padding:24px; font-size:15px; line-height:1.6; }
    .footer { padding:16px 24px; font-size:12px; color:#64748b; background:#f8fafc; }
    .hr { height:1px; background:#e2e8f0; border:0; margin:20px 0; }
    .muted { color:#64748b; }
    .btn { display:inline-block; background:#4f46e5; color:#fff !important; text-decoration:none; padding:10px 14px; border-radius:10px; font-weight:600; }
    .badge { display:inline-block; background:#eef2ff; color:#3730a3; padding:4px 8px; border-radius:999px; font-size:12px; font-weight:600; }
    .pre { white-space:pre-line; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="header">
        <div class="brand">Nimbus <span class="badge" style="margin-left:8px;background:#22d3ee;color:#083344;">Recordatorio</span></div>
      </div>
      <div class="content">
        <p class="muted" style="margin-top:0;margin-bottom:12px;">Hola,</p>
        <div class="pre" style="margin:0 0 12px 0;">{!! nl2br(e($body ?? '')) !!}</div>
        <hr class="hr">
        <p class="muted" style="margin:0;">Si este mensaje no era para ti, puedes ignorarlo.
        <br>Este correo fue enviado automáticamente por Nimbus.</p>
      </div>
      <div class="footer">
        <div>© {{ date('Y') }} Nimbus. Todos los derechos reservados.</div>
      </div>
    </div>
  </div>
</body>
</html>
