<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sesiones sin confirmar</title>
  <style>
    body { margin:0; padding:0; background:#0b1020; color:#e2e8f0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
    .wrapper { width:100%; background:#0b1020; padding:40px 20px; }
    .container { max-width:600px; margin:0 auto; background:rgba(255,255,255,0.05); border-radius:16px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); }
    .header { background:linear-gradient(135deg,#6366f1,#4338ca); color:#fff; padding:32px 28px; text-align:center; }
    .brand { font-size:28px; font-weight:700; margin-bottom:8px; }
    .subtitle { font-size:13px; color:rgba(255,255,255,0.8); text-transform:uppercase; letter-spacing:1px; }
    .content { padding:32px 28px; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.85); }
    .session { background:rgba(255,255,255,0.03); border-radius:12px; padding:20px 24px; margin:20px 0; border-left:3px solid #6366f1; }
    .session h3 { margin:0 0 12px 0; font-size:17px; color:#c7d2fe; }
    .session-when { color:rgba(255,255,255,0.75); font-size:14px; margin:0 0 6px 0; }
    .session-phone { color:rgba(255,255,255,0.5); font-size:13px; margin:0; }
    .wa-btn { display:inline-block; margin-top:16px; padding:12px 24px; background:linear-gradient(135deg,#25d366,#128c7e); color:#fff; text-decoration:none; border-radius:10px; font-weight:600; font-size:15px; }
    .no-phone { margin-top:14px; padding:12px 16px; background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:8px; font-size:13px; color:rgba(255,255,255,0.75); }
    .footer { padding:24px 28px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(0,0,0,0.2); text-align:center; border-top:1px solid rgba(255,255,255,0.05); }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="header">
        <div class="brand">☁️ Nimbus</div>
        <div class="subtitle">Sesiones sin confirmar</div>
      </div>

      <div class="content">
        <p>¡Hola!</p>

        <p>
          @if($sessions->count() === 1)
            Esta sesión es dentro de menos de {{ $hoursAhead }} h y el paciente todavía no ha confirmado ni cancelado:
          @else
            Estas {{ $sessions->count() }} sesiones son dentro de menos de {{ $hoursAhead }} h y los pacientes todavía no han confirmado ni cancelado:
          @endif
        </p>

        @foreach($sessions as $session)
          <div class="session">
            <h3>{{ $session['patient_name'] }}</h3>
            <p class="session-when">📅 {{ $session['date'] }} · 🕐 {{ $session['time'] }}</p>

            @if($session['phone'])
              <p class="session-phone">{{ $session['phone'] }}</p>
            @endif

            @if($session['whatsapp_url'])
              <a href="{{ $session['whatsapp_url'] }}" class="wa-btn">💬 Escribir por WhatsApp</a>
            @else
              <div class="no-phone">
                Este paciente no tiene teléfono guardado, así que no podemos abrirte el WhatsApp. Tendrás que
                contactarle por otra vía.
              </div>
            @endif
          </div>
        @endforeach

        <p style="color:rgba(255,255,255,0.5);font-size:13px;margin-top:24px;">
          💡 <em>El mensaje de WhatsApp va escrito, solo tienes que enviarlo. Si el paciente responde por los enlaces
          del recordatorio, el estado se actualiza solo y no volverás a recibir este aviso.</em>
        </p>
      </div>

      <div class="footer">
        <p>Esta es una notificación automática de Nimbus.</p>
        <p style="margin-top:8px;font-size:11px;color:rgba(255,255,255,0.3);">© {{ date('Y') }} Nimbus</p>
      </div>
    </div>
  </div>
</body>
</html>
