<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Revisar cita</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f4;color:#1f2937;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f4;padding:40px 16px;">
    <tr><td align="center">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;">
        <tr><td style="padding:32px 40px;">
          <p style="margin:0 0 8px;color:#a16207;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Cita pendiente de revisión</p>
          <h1 style="margin:0 0 20px;font-size:24px;line-height:1.3;">¿Está confirmada esta cita?</h1>
          <p style="margin:0 0 20px;line-height:1.6;color:#4b5563;">Google Calendar tiene esta cita marcada en amarillo. Nimbus no enviará nada a la paciente hasta que indiques qué hacer.</p>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;">
            <tr><td style="padding:18px;line-height:1.7;">
              <strong>{{ $appointment->summary }}</strong><br>
              {{ $appointment->formatted_date }} a las {{ $appointment->formatted_time }}<br>
              Paciente: {{ $appointment->patient?->name ?? 'Sin paciente' }}
            </td></tr>
          </table>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td width="50%" style="padding-right:6px;"><a href="{{ $confirmUrl }}" style="display:block;padding:14px;background:#2e7d32;color:#fff;text-decoration:none;text-align:center;border-radius:4px;font-weight:700;">Confirmar y enviar</a></td>
              <td width="50%" style="padding-left:6px;"><a href="{{ $cancelUrl }}" style="display:block;padding:14px;background:#c62828;color:#fff;text-decoration:none;text-align:center;border-radius:4px;font-weight:700;">Cancelar cita</a></td>
            </tr>
          </table>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
