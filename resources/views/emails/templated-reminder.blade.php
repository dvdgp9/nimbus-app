<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $appointment->summary }}</title>
  <style>
    body { margin:0; padding:0; background:#0b1020; color:#e2e8f0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
    .wrapper { width:100%; background:#0b1020; padding:40px 20px; }
    .container { max-width:600px; margin:0 auto; background:rgba(255,255,255,0.05); border-radius:16px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.3); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.1); }
    .header { 
      background:linear-gradient(135deg,#00d4ff,#5b7cfa);
      color:#fff;
      padding:32px 28px;
      text-align:center;
    }
    .brand { 
      font-size:28px;
      font-weight:700;
      letter-spacing:-0.5px;
      margin-bottom:8px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:12px;
    }
    .subtitle { 
      font-size:13px;
      color:rgba(255,255,255,0.8);
      font-weight:500;
      text-transform:uppercase;
      letter-spacing:1px;
    }
    .content { 
      padding:32px 28px;
      font-size:15px;
      line-height:1.8;
      color:rgba(255,255,255,0.85);
      white-space: pre-wrap;
    }
    .footer { 
      padding:24px 28px;
      font-size:12px;
      color:rgba(255,255,255,0.4);
      background:rgba(0,0,0,0.2);
      text-align:center;
      border-top:1px solid rgba(255,255,255,0.05);
    }
    a { color:#00d4ff; text-decoration:none; }
    a:hover { text-decoration:underline; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      {{-- Header --}}
      <div class="header">
        <div class="brand">
          <span>☁️</span>
          <span>Nimbus</span>
        </div>
        <div class="subtitle">Recordatorio de Cita</div>
      </div>
      
      {{-- Content - User's custom template --}}
      <div class="content">{!! nl2br(e($emailBody)) !!}</div>
      
      {{-- Footer --}}
      <div class="footer">
        <p>Este correo fue enviado automáticamente por Nimbus.</p>
        <p>© {{ date('Y') }} Nimbus. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>
</body>
</html>
