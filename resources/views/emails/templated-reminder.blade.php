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
    .btn { display:inline-block; padding:14px 28px; border-radius:10px; font-weight:600; font-size:15px; text-decoration:none; margin:8px 4px; }
    .btn-confirm { background:linear-gradient(135deg,#10b981,#059669); color:#ffffff; }
    .btn-cancel { background:linear-gradient(135deg,#ef4444,#dc2626); color:#ffffff; }
    .btn-reschedule { background:linear-gradient(135deg,#f59e0b,#d97706); color:#ffffff; }
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
      
      {{-- Content - User's custom template with button support --}}
      @php
        // Process the email body to convert button markers to actual buttons
        $processedBody = e($emailBody);
        
        // Replace button markers with actual HTML buttons
        $processedBody = str_replace(
          '[BOTON_CONFIRMAR]',
          '</p><div style="text-align:center;margin:16px 0;"><a href="' . ($confirmUrl ?? '#') . '" class="btn btn-confirm" style="display:inline-block;padding:14px 28px;background:linear-gradient(135deg,#10b981,#059669);color:#ffffff;border-radius:10px;font-weight:600;font-size:15px;text-decoration:none;">✅ Confirmar cita</a></div><p style="margin:0;">',
          $processedBody
        );
        $processedBody = str_replace(
          '[BOTON_CANCELAR]',
          '</p><div style="text-align:center;margin:16px 0;"><a href="' . ($cancelUrl ?? '#') . '" class="btn btn-cancel" style="display:inline-block;padding:14px 28px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#ffffff;border-radius:10px;font-weight:600;font-size:15px;text-decoration:none;">❌ Cancelar cita</a></div><p style="margin:0;">',
          $processedBody
        );
        $processedBody = str_replace(
          '[BOTON_CAMBIAR]',
          '</p><div style="text-align:center;margin:16px 0;"><a href="' . ($rescheduleUrl ?? '#') . '" class="btn btn-reschedule" style="display:inline-block;padding:14px 28px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#ffffff;border-radius:10px;font-weight:600;font-size:15px;text-decoration:none;">📅 Cambiar cita</a></div><p style="margin:0;">',
          $processedBody
        );
        
        // Convert newlines to <br> for regular text
        $processedBody = nl2br($processedBody);
      @endphp
      
      <div class="content">{!! $processedBody !!}</div>
      
      {{-- Footer --}}
      <div class="footer">
        <p>Este correo fue enviado automáticamente por Nimbus.</p>
        <p>© {{ date('Y') }} Nimbus. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>
</body>
</html>
