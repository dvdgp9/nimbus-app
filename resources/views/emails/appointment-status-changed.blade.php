<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cita {{ $action === 'confirmed' ? 'Confirmada' : 'Cancelada' }}</title>
  <style>
    body { margin:0; padding:20px; background:#f4f7f9; color:#1e293b; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
    .container { max-width:500px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); }
    .header { background:{{ $action === 'confirmed' ? 'linear-gradient(135deg,#10b981,#059669)' : 'linear-gradient(135deg,#ef4444,#dc2626)' }}; padding:20px; text-align:center; }
    .header h1 { margin:0; font-size:18px; color:#ffffff; }
    .content { padding:24px; }
    .patient-code { font-size:32px; font-weight:700; color:#0f172a; text-align:center; margin-bottom:8px; }
    .patient-name { text-align:center; color:#64748b; margin-bottom:24px; font-size:16px; }
    .appointment-box { background:#f8fafc; border-radius:8px; padding:16px; margin-bottom:24px; border:1px solid #f1f5f9; }
    .appointment-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #e2e8f0; }
    .appointment-row:last-child { border-bottom:none; }
    .label { color:#64748b; font-size:13px; font-weight:500; }
    .value { color:#1e293b; font-weight:600; }
    .btn { display:block; text-align:center; padding:16px; background:#f59e0b; color:#ffffff !important; text-decoration:none; border-radius:8px; font-weight:700; font-size:15px; }
    .footer { padding:20px; text-align:center; font-size:11px; color:#94a3b8; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>{{ $action === 'confirmed' ? '✅ CITA CONFIRMADA' : '❌ CITA CANCELADA' }}</h1>
    </div>
    
    <div class="content">
      <div class="patient-code">{{ $patient->code }}</div>
      <div class="patient-name">{{ $patient->name }}</div>
      
      <div class="appointment-box">
        <div class="appointment-row">
          <span class="label">Fecha</span>
          <span class="value">{{ $appointment->formatted_date }}</span>
        </div>
        <div class="appointment-row">
          <span class="label">Hora</span>
          <span class="value">{{ $appointment->formatted_time }}</span>
        </div>
        @if($patient->phone)
        <div class="appointment-row">
          <span class="label">Teléfono</span>
          <span class="value">{{ $patient->phone }}</span>
        </div>
        @endif
      </div>

      @if($action === 'cancelled' && $acknowledgeUrl)
        <a href="{{ $acknowledgeUrl }}" class="btn">
          ✓ He visto esta cancelación
        </a>
        <p style="text-align:center;color:rgba(255,255,255,0.4);font-size:12px;margin-top:12px;">
          La cita se moverá al domingo
        </p>
      @endif
    </div>
    
    <div class="footer">
      Nimbus - Sistema de gestión de citas
    </div>
  </div>
</body>
</html>
