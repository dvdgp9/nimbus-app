<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Código de Paciente No Encontrado</title>
  <style>
    body { margin:0; padding:0; background:#0b1020; color:#e2e8f0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
    .wrapper { width:100%; background:#0b1020; padding:40px 20px; }
    .container { max-width:600px; margin:0 auto; background:rgba(255,255,255,0.05); border-radius:16px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); }
    .header { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; padding:32px 28px; text-align:center; }
    .brand { font-size:28px; font-weight:700; margin-bottom:8px; }
    .subtitle { font-size:13px; color:rgba(255,255,255,0.8); text-transform:uppercase; letter-spacing:1px; }
    .content { padding:32px 28px; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.85); }
    .info-card { background:rgba(255,255,255,0.03); border-radius:12px; padding:24px; margin:20px 0; border-left:3px solid #f59e0b; }
    .info-card h3 { margin:0 0 16px 0; font-size:16px; color:#fbbf24; }
    .detail { display:flex; margin:12px 0; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05); }
    .detail:last-child { border-bottom:none; }
    .detail-label { width:120px; color:rgba(255,255,255,0.5); font-size:13px; }
    .detail-value { flex:1; color:rgba(255,255,255,0.9); font-weight:500; }
    .code-highlight { display:inline-block; background:rgba(245,158,11,0.2); color:#fbbf24; padding:4px 12px; border-radius:6px; font-family:monospace; font-size:18px; font-weight:700; }
    .cta-section { text-align:center; margin:32px 0; }
    .cta-btn { display:inline-block; padding:16px 40px; background:linear-gradient(135deg,#10b981,#059669); color:#fff; text-decoration:none; border-radius:10px; font-weight:600; font-size:16px; box-shadow:0 4px 16px rgba(16,185,129,0.3); }
    .alert-box { background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:10px; padding:16px 20px; margin:20px 0; }
    .alert-box p { margin:0; color:rgba(255,255,255,0.8); }
    .footer { padding:24px 28px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(0,0,0,0.2); text-align:center; border-top:1px solid rgba(255,255,255,0.05); }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="header">
        <div class="brand">☁️ Nimbus</div>
        <div class="subtitle">Código de Paciente No Encontrado</div>
      </div>
      
      <div class="content">
        <p>¡Hola!</p>
        
        <div class="alert-box">
          <p>⚠️ Se ha detectado una cita con un <strong>código de paciente que no existe</strong> en tu base de datos.</p>
        </div>

        <p>El código detectado es: <span class="code-highlight">{{ $patientCode }}</span></p>
        
        <div class="info-card">
          <h3>📅 Información de la Cita</h3>
          <div class="detail">
            <span class="detail-label">Fecha</span>
            <span class="detail-value">{{ $appointment->formatted_date }}</span>
          </div>
          <div class="detail">
            <span class="detail-label">Hora</span>
            <span class="detail-value">{{ $appointment->formatted_time }}</span>
          </div>
          <div class="detail">
            <span class="detail-label">Título</span>
            <span class="detail-value">{{ $appointment->summary }}</span>
          </div>
        </div>

        <p style="color:rgba(255,255,255,0.7);">
          Como el paciente no está registrado, <strong>no se enviará ningún recordatorio</strong> para esta cita.
        </p>

        <div class="cta-section">
          <p style="color:rgba(255,255,255,0.6);font-size:14px;margin-bottom:20px;">
            Haz clic para crear el paciente con el código prellenado:
          </p>
          <a href="{{ $createPatientUrl }}" class="cta-btn">
            ➕ Crear Paciente "{{ $patientCode }}"
          </a>
        </div>

        <p style="color:rgba(255,255,255,0.5);font-size:13px;margin-top:24px;">
          💡 <em>Una vez creado el paciente, la próxima sincronización vinculará automáticamente esta cita.</em>
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
