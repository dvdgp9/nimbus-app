<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva Primera Sesión Detectada</title>
  <style>
    body { margin:0; padding:0; background:#0b1020; color:#e2e8f0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
    .wrapper { width:100%; background:#0b1020; padding:40px 20px; }
    .container { max-width:600px; margin:0 auto; background:rgba(255,255,255,0.05); border-radius:16px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); }
    .header { background:linear-gradient(135deg,#8b5cf6,#6366f1); color:#fff; padding:32px 28px; text-align:center; }
    .brand { font-size:28px; font-weight:700; margin-bottom:8px; }
    .subtitle { font-size:13px; color:rgba(255,255,255,0.8); text-transform:uppercase; letter-spacing:1px; }
    .content { padding:32px 28px; font-size:15px; line-height:1.7; color:rgba(255,255,255,0.85); }
    .info-card { background:rgba(255,255,255,0.03); border-radius:12px; padding:24px; margin:20px 0; border-left:3px solid #8b5cf6; }
    .info-card h3 { margin:0 0 16px 0; font-size:16px; color:#a78bfa; }
    .detail { display:flex; margin:12px 0; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05); }
    .detail:last-child { border-bottom:none; }
    .detail-label { width:120px; color:rgba(255,255,255,0.5); font-size:13px; }
    .detail-value { flex:1; color:rgba(255,255,255,0.9); font-weight:500; }
    .cta-section { text-align:center; margin:32px 0; }
    .cta-btn { display:inline-block; padding:16px 40px; background:linear-gradient(135deg,#10b981,#059669); color:#fff; text-decoration:none; border-radius:10px; font-weight:600; font-size:16px; box-shadow:0 4px 16px rgba(16,185,129,0.3); }
    .footer { padding:24px 28px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(0,0,0,0.2); text-align:center; border-top:1px solid rgba(255,255,255,0.05); }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="header">
        <div class="brand">☁️ Nimbus</div>
        <div class="subtitle">Nueva Primera Sesión Detectada</div>
      </div>
      
      <div class="content">
        <p>¡Hola!</p>
        
        <p>Se ha detectado una <strong>primera sesión</strong> programada en tu calendario:</p>
        
        {{-- Appointment Info --}}
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

        {{-- Patient Data Extracted --}}
        <div class="info-card">
          <h3>👤 Datos del Paciente (extraídos)</h3>
          <div class="detail">
            <span class="detail-label">Nombre</span>
            <span class="detail-value">{{ $patientData['name'] ?? '—' }}</span>
          </div>
          <div class="detail">
            <span class="detail-label">Email</span>
            <span class="detail-value">{{ $patientData['email'] ?? '—' }}</span>
          </div>
          <div class="detail">
            <span class="detail-label">Teléfono</span>
            <span class="detail-value">{{ $patientData['phone'] ?? '—' }}</span>
          </div>
          @if($patientData['notes'])
          <div class="detail">
            <span class="detail-label">Notas</span>
            <span class="detail-value">{{ \Illuminate\Support\Str::limit($patientData['notes'], 100) }}</span>
          </div>
          @endif
        </div>

        {{-- CTA Button --}}
        <div class="cta-section">
          <p style="color:rgba(255,255,255,0.6);font-size:14px;margin-bottom:20px;">
            Haz clic para crear el paciente con los datos prellenados:
          </p>
          <a href="{{ $createPatientUrl }}" class="cta-btn">
            ➕ Crear Paciente
          </a>
        </div>

        <p style="color:rgba(255,255,255,0.5);font-size:13px;margin-top:24px;">
          💡 <em>Recuerda asignar un código único al paciente para que se vincule automáticamente con sus futuras citas.</em>
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
