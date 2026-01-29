<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estado de Cita Actualizado</title>
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
    
    /* Status banner */
    .status-banner {
      background:rgba(255,255,255,0.03);
      border-left:3px solid;
      padding:20px;
      border-radius:8px;
      margin:20px 0;
      display:flex;
      align-items:center;
      gap:16px;
    }
    .status-banner.confirmed { border-color:#10b981; }
    .status-banner.cancelled { border-color:#ef4444; }
    .status-icon-large {
      width:48px;
      height:48px;
      flex-shrink:0;
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .status-icon-large.confirmed { background:rgba(16,185,129,0.1); }
    .status-icon-large.cancelled { background:rgba(239,68,68,0.1); }
    .status-text h2 {
      margin:0 0 4px 0;
      font-size:20px;
      color:#fff;
    }
    .status-text p {
      margin:0;
      color:rgba(255,255,255,0.7);
      font-size:14px;
    }
    
    /* Info sections */
    .info-section {
      background:rgba(255,255,255,0.03);
      border-radius:8px;
      padding:20px;
      margin:20px 0;
    }
    .info-section h3 {
      margin:0 0 16px 0;
      font-size:14px;
      color:rgba(255,255,255,0.5);
      text-transform:uppercase;
      letter-spacing:1px;
      font-weight:600;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .section-icon {
      width:18px;
      height:18px;
    }
    .detail {
      display:flex;
      align-items:start;
      gap:12px;
      margin:12px 0;
      padding:12px 0;
      border-bottom:1px solid rgba(255,255,255,0.05);
    }
    .detail:last-child {
      border-bottom:none;
    }
    .detail-icon {
      width:20px;
      height:20px;
      flex-shrink:0;
      margin-top:2px;
      color:rgba(255,255,255,0.5);
    }
    .detail-content {
      flex:1;
    }
    .detail-label {
      font-size:12px;
      color:rgba(255,255,255,0.5);
      text-transform:uppercase;
      letter-spacing:0.5px;
      margin-bottom:4px;
    }
    .detail-value {
      color:rgba(255,255,255,0.9);
      font-weight:500;
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
        <div class="subtitle">Notificación de Actualización</div>
      </div>
      
      {{-- Content --}}
      <div class="content">
        <p class="greeting">Hola,</p>
        
        <p>Tu paciente ha actualizado el estado de su cita:</p>
        
        {{-- Status Banner --}}
        <div class="status-banner {{ $action === 'confirmed' ? 'confirmed' : 'cancelled' }}">
          <div class="status-icon-large {{ $action === 'confirmed' ? 'confirmed' : 'cancelled' }}">
            @if($action === 'confirmed')
              <svg style="width:28px;height:28px;color:#10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" />
                <path d="M9 12.5l2 2.5 4-5" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            @else
              <svg style="width:28px;height:28px;color:#ef4444;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" />
                <path d="M9 9l6 6M15 9l-6 6" stroke-linecap="round" />
              </svg>
            @endif
          </div>
          <div class="status-text">
            <h2>{{ $action === 'confirmed' ? 'Cita Confirmada' : 'Cita Cancelada' }}</h2>
            <p>
              @if($action === 'confirmed')
                {{ $patient->name }} ha confirmado su asistencia a la sesión
              @else
                {{ $patient->name }} ha cancelado la sesión programada
              @endif
            </p>
          </div>
        </div>
        
        {{-- Patient Information --}}
        <div class="info-section">
          <h3>
            <svg class="section-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Información del Paciente
          </h3>
          
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <div class="detail-content">
              <div class="detail-label">Nombre</div>
              <div class="detail-value">{{ $patient->name }}</div>
            </div>
          </div>
          
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-linecap="round" />
            </svg>
            <div class="detail-content">
              <div class="detail-label">Código</div>
              <div class="detail-value">{{ $patient->code }}</div>
            </div>
          </div>
          
          @if($patient->email)
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <div class="detail-content">
              <div class="detail-label">Email</div>
              <div class="detail-value">{{ $patient->email }}</div>
            </div>
          </div>
          @endif
          
          @if($patient->phone)
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            <div class="detail-content">
              <div class="detail-label">Teléfono</div>
              <div class="detail-value">{{ $patient->phone }}</div>
            </div>
          </div>
          @endif
        </div>
        
        {{-- Appointment Details --}}
        <div class="info-section">
          <h3>
            <svg class="section-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect x="3" y="6" width="18" height="15" rx="2" />
              <path d="M3 10h18M8 3v6M16 3v6" stroke-linecap="round" />
            </svg>
            Detalles de la Cita
          </h3>
          
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect x="3" y="6" width="18" height="15" rx="2" />
              <path d="M3 10h18M8 3v6M16 3v6" stroke-linecap="round" />
            </svg>
            <div class="detail-content">
              <div class="detail-label">Fecha</div>
              <div class="detail-value">{{ $appointment->formatted_date }}</div>
            </div>
          </div>
          
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 6v6l4 2" stroke-linecap="round" />
            </svg>
            <div class="detail-content">
              <div class="detail-label">Hora</div>
              <div class="detail-value">{{ $appointment->formatted_time }}</div>
            </div>
          </div>
          
          @if($appointment->summary)
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round"/>
            </svg>
            <div class="detail-content">
              <div class="detail-label">Descripción</div>
              <div class="detail-value">{{ $appointment->summary }}</div>
            </div>
          </div>
          @endif
          
          <div class="detail">
            <svg class="detail-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 8v.01M12 11v5" stroke-linecap="round" />
            </svg>
            <div class="detail-content">
              <div class="detail-label">Estado Actual</div>
              <div class="detail-value" style="color:{{ $action === 'confirmed' ? '#10b981' : '#ef4444' }};">
                {{ $action === 'confirmed' ? 'Confirmada' : 'Cancelada' }}
              </div>
            </div>
          </div>
        </div>
        
        <p style="color:rgba(255,255,255,0.6);font-size:14px;line-height:1.6;margin-top:24px;">
          @if($action === 'confirmed')
            El paciente confirmó su asistencia a través del enlace que enviaste. Todo listo para la sesión. 🎉
          @else
            El paciente canceló la cita. Puedes contactarle para reprogramar si es necesario. 📞
          @endif
        </p>

        @if($action === 'cancelled' && $acknowledgeUrl)
        {{-- Acknowledge Cancellation Button --}}
        <div style="margin-top:32px;text-align:center;">
          <p style="color:rgba(255,255,255,0.6);font-size:13px;margin-bottom:16px;">
            Haz clic en el botón para confirmar que has visto esta cancelación.<br>
            <span style="color:rgba(255,255,255,0.4);font-size:12px;">La cita se moverá automáticamente al domingo para tu referencia.</span>
          </p>
          <a href="{{ $acknowledgeUrl }}" 
             style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;box-shadow:0 4px 12px rgba(245,158,11,0.3);">
            ✓ He visto esta cancelación
          </a>
        </div>
        @endif
      </div>
      
      {{-- Footer --}}
      <div class="footer">
        <p class="footer-note">
          Esta es una notificación automática de Nimbus.<br>
          Tu sistema de gestión de citas.
        </p>
        <p class="footer-copy">© {{ date('Y') }} Nimbus. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>
</body>
</html>
