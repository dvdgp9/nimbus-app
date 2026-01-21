<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $appointment->summary }}</title>
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
    
    /* Appointment card */
    .appointment-card {
      background:rgba(255,255,255,0.03);
      border-left:3px solid #00d4ff;
      padding:20px;
      border-radius:8px;
      margin:20px 0;
    }
    .appointment-card h2 {
      margin:0 0 16px 0;
      font-size:20px;
      color:#fff;
    }
    .detail {
      display:flex;
      align-items:start;
      gap:12px;
      margin:12px 0;
      color:rgba(255,255,255,0.8);
    }
    .detail-icon {
      width:20px;
      height:20px;
      flex-shrink:0;
      margin-top:2px;
    }
    
    /* Buttons */
    .actions {
      margin:32px 0;
      text-align:center;
    }
    .btn { 
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      padding:14px 28px;
      border-radius:8px;
      text-decoration:none;
      font-weight:600;
      font-size:14px;
      margin:8px 4px;
      transition:all 0.2s;
      color:#fff;
    }
    .btn:hover { opacity:0.9; transform:translateY(-1px); }
    .btn-icon { width:18px; height:18px; flex-shrink:0; }
    .btn-confirm {
      background:linear-gradient(135deg,#10b981,#059669);
      color:#fff;
      box-shadow:0 4px 12px rgba(16,185,129,0.3);
    }
    .btn-cancel {
      background:rgba(255,255,255,0.1);
      color:#fff;
      border:1px solid rgba(255,255,255,0.2);
    }
    .btn-reschedule {
      background:linear-gradient(135deg,#f59e0b,#d97706);
      color:#fff;
      box-shadow:0 4px 12px rgba(245,158,11,0.3);
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
        <div class="subtitle">Recordatorio de Cita</div>
      </div>
      
      {{-- Content --}}
      <div class="content">
        <p class="greeting">Hola {{ $patient->name }},</p>
        
        <p>Te recordamos tu próxima cita de psicología:</p>
        
        <div class="appointment-card">
          <h2>{{ $appointment->summary }}</h2>
          
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect x="3" y="6" width="18" height="15" rx="2" />
              <path d="M3 10h18M8 3v6M16 3v6" stroke-linecap="round" />
            </svg>
            <div>
              <strong>Fecha:</strong><br>
              {{ $appointment->formatted_date }}
            </div>
          </div>
          
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 6v6l4 2" stroke-linecap="round" />
            </svg>
            <div>
              <strong>Hora:</strong><br>
              {{ $appointment->formatted_time }} ({{ $appointment->timezone }})
            </div>
          </div>
          
          @if($appointment->hangout_link)
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M8 11h8M8 15h5M16 19H8a2 2 0 01-2-2V7a2 2 0 012-2h8l4 4v8a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div>
              <strong>Enlace de la sesión:</strong><br>
              <a href="{{ $appointment->hangout_link }}" style="color:#00d4ff;text-decoration:none;">Unirse a la videollamada</a>
            </div>
          </div>
          @endif
          
          @if($appointment->description)
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round"/>
            </svg>
            <div>
              <strong>Notas:</strong><br>
              {{ $appointment->description }}
            </div>
          </div>
          @endif
        </div>
        
        <p style="margin:24px 0 8px 0;color:rgba(255,255,255,0.9);font-weight:600;">¿Qué deseas hacer?</p>
        
        <div class="actions">
          <a href="{{ $confirmUrl }}" class="btn btn-confirm" style="color:#ffffff !important; text-decoration:none;">
            ✅
            <span>Confirmar Asistencia</span>
          </a>
          <a href="{{ $cancelUrl }}" class="btn btn-cancel" style="color:#ffffff !important; text-decoration:none;">
            ❌
            <span>Cancelar Cita</span>
          </a>
        </div>
        
        <hr class="hr">
        
        <div class="info-box" style="display:flex;gap:12px;">
          <svg style="width:20px;height:20px;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v.01M12 11v5" stroke-linecap="round" />
          </svg>
          <div>
            <strong style="color:rgba(255,255,255,0.9);">Recuerda</strong><br>
            Por favor, confirma tu asistencia para mantener tu espacio reservado. Si necesitas cancelar, hazlo con al menos 24 horas de antelación.
          </div>
        </div>
      </div>
      
      {{-- Footer --}}
      <div class="footer">
        <p class="footer-note">
          Este correo fue enviado automáticamente por Nimbus.<br>
          Sistema de recordatorios para consultas de psicología online.
        </p>
        <p class="footer-copy">© {{ date('Y') }} Nimbus. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>
</body>
</html>
