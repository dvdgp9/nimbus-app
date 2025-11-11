<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subjectLine ?? 'Recordatorio de sesión' }}</title>
  <style>
    /* Reset and base styles */
    body { margin:0; padding:0; background:#0b1020; color:#e2e8f0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
    .wrapper { width:100%; background:#0b1020; padding:40px 20px; }
    .container { max-width:600px; margin:0 auto; background:rgba(255,255,255,0.05); border-radius:16px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.3); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.1); }
    
    /* Header with gradient */
    .header { 
      background:linear-gradient(135deg,#00d4ff,#5b7cfa);
      color:#fff;
      padding:32px 28px;
      text-align:center;
      position:relative;
    }
    .header::after {
      content:'';
      position:absolute;
      bottom:0;
      left:0;
      right:0;
      height:1px;
      background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);
    }
    
    /* Brand */
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
    .cloud-icon {
      display:inline-block;
      width:32px;
      height:32px;
      background:#fff;
      border-radius:8px;
      position:relative;
    }
    .cloud-icon::before {
      content:'☁';
      position:absolute;
      inset:0;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:20px;
      color:#00d4ff;
    }
    .subtitle { 
      font-size:13px;
      color:rgba(255,255,255,0.8);
      font-weight:500;
      text-transform:uppercase;
      letter-spacing:1px;
    }
    
    /* Content */
    .content { 
      padding:32px 28px;
      font-size:15px;
      line-height:1.7;
      color:rgba(255,255,255,0.85);
    }
    .greeting {
      color:rgba(255,255,255,0.6);
      font-size:14px;
      margin:0 0 16px 0;
    }
    .message {
      background:rgba(255,255,255,0.03);
      border-left:3px solid #00d4ff;
      padding:16px 20px;
      border-radius:8px;
      margin:20px 0;
      white-space:pre-line;
      color:rgba(255,255,255,0.9);
    }
    
    /* Divider */
    .hr { 
      height:1px;
      background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);
      border:0;
      margin:24px 0;
    }
    
    /* Footer */
    .footer { 
      padding:24px 28px;
      font-size:12px;
      color:rgba(255,255,255,0.4);
      background:rgba(0,0,0,0.2);
      text-align:center;
      border-top:1px solid rgba(255,255,255,0.05);
    }
    .footer-note {
      margin:0 0 12px 0;
      line-height:1.6;
    }
    .footer-copy {
      margin:0;
      font-size:11px;
      color:rgba(255,255,255,0.3);
    }
    
    /* Button */
    .btn { 
      display:inline-block;
      background:linear-gradient(135deg,#00d4ff,#5b7cfa);
      color:#fff !important;
      text-decoration:none;
      padding:12px 24px;
      border-radius:8px;
      font-weight:600;
      font-size:14px;
      margin:16px 0;
      box-shadow:0 4px 12px rgba(0,212,255,0.3);
    }
    .btn:hover { opacity:0.9; }
    
    /* Info box */
    .info-box {
      background:rgba(91,124,250,0.1);
      border:1px solid rgba(91,124,250,0.2);
      border-radius:8px;
      padding:16px;
      margin:20px 0;
      font-size:14px;
      color:rgba(255,255,255,0.7);
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      {{-- Header --}}
      <div class="header">
        <div class="brand">
          <span class="cloud-icon"></span>
          <span>Nimbus</span>
        </div>
        <div class="subtitle">Recordatorio de sesión</div>
      </div>
      
      {{-- Content --}}
      <div class="content">
        <p class="greeting">Hola,</p>
        
        <div class="message">{!! nl2br(e($body ?? '')) !!}</div>
        
        <hr class="hr">
        
        <div class="info-box">
          <strong style="color:rgba(255,255,255,0.9);">ℹ️ Información importante</strong><br>
          Si este mensaje no era para ti o deseas dejar de recibir recordatorios, puedes ignorarlo o contactar con tu profesional.
        </div>
      </div>
      
      {{-- Footer --}}
      <div class="footer">
        <p class="footer-note">
          Este correo fue enviado automáticamente por Nimbus.<br>
          Sistema de recordatorios inteligentes para consultas online.
        </p>
        <p class="footer-copy">© {{ date('Y') }} Nimbus. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>
</body>
</html>
