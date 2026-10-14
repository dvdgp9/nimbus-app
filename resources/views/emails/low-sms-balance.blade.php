<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quedan pocos SMS</title>
  <style>
    body { margin:0; padding:0; background:#0b1020; color:#e2e8f0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
    .wrapper { width:100%; background:#0b1020; padding:40px 20px; }
    .container { max-width:600px; margin:0 auto; background:rgba(255,255,255,0.05); border-radius:16px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); }
    .header { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; padding:32px 28px; text-align:center; }
    .brand { font-size:28px; font-weight:700; margin-bottom:8px; }
    .subtitle { font-size:13px; color:rgba(255,255,255,0.85); text-transform:uppercase; letter-spacing:1px; }
    .content { padding:32px 28px; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.85); }
    .balance { background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:12px; padding:20px 24px; margin:20px 0; text-align:center; }
    .balance-number { font-size:40px; font-weight:700; color:#fbbf24; margin:0; }
    .balance-label { font-size:14px; color:rgba(255,255,255,0.7); margin:4px 0 0 0; }
    .btn { display:inline-block; margin-top:8px; padding:12px 24px; background:linear-gradient(135deg,#6366f1,#4338ca); color:#fff; text-decoration:none; border-radius:10px; font-weight:600; font-size:15px; }
    .note { color:rgba(255,255,255,0.5); font-size:13px; margin-top:24px; }
    .footer { padding:24px 28px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(0,0,0,0.2); text-align:center; border-top:1px solid rgba(255,255,255,0.05); }
    .copyright { margin-top:8px; font-size:11px; color:rgba(255,255,255,0.3); }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="header">
        <div class="brand">☁️ Nimbus</div>
        <div class="subtitle">Saldo de SMS bajo</div>
      </div>

      <div class="content">
        <p>¡Hola!</p>

        <p>
          Quedan pocos SMS en la cuenta de Acumbamail. Cuando se acaben, los recordatorios dejarán de llegar
          por SMS a los pacientes.
        </p>

        <div class="balance">
          <p class="balance-number">≈ {{ $sms }}</p>
          <p class="balance-label">SMS disponibles a España</p>
        </div>

        <p>Recarga el saldo en Acumbamail para que los recordatorios sigan saliendo sin interrupciones:</p>

        <p style="text-align:center;">
          <a href="https://acumbamail.com/" class="btn">Ir a Acumbamail</a>
        </p>

        <p class="note">
          💡 <em>Un recordatorio largo, con tildes o con emojis puede gastar 2 o más SMS, y los envíos fuera de España
          cuestan el doble, así que el saldo real puede durar algo menos. Recibirás este aviso una sola vez; si
          recargas y vuelve a bajar de {{ $threshold }} SMS, te avisaremos de nuevo.</em>
        </p>
      </div>

      <div class="footer">
        <p>Esta es una notificación automática de Nimbus.</p>
        <p class="copyright">© {{ date('Y') }} Nimbus</p>
      </div>
    </div>
  </div>
</body>
</html>
