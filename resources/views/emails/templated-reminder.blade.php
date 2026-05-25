@php
  $professionalName = optional(optional($patient)->user)->name ?? config('app.name');
  $preheader = 'Tu cita del ' . $appointment->formatted_date . ' a las ' . $appointment->formatted_time . '.';
  $accent = '#3d5a80';
  $accentDark = '#2f4868';
  $ink = '#1f2937';
  $muted = '#6b7280';
  $line = '#e5e7eb';
  $page = '#f5f5f4';

  $confirmBtn = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:18px auto;"><tr><td style="background:' . $accent . ';border:1px solid ' . $accent . ';border-radius:2px;"><a href="' . ($confirmUrl ?? '#') . '" style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;letter-spacing:0.01em;">Confirmar asistencia</a></td></tr></table>';
  $cancelBtn  = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:18px auto;"><tr><td style="background:#ffffff;border:1px solid ' . $line . ';border-radius:2px;"><a href="' . ($cancelUrl ?? '#') . '" style="display:inline-block;padding:14px 28px;color:' . $ink . ';text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;letter-spacing:0.01em;">No podré asistir</a></td></tr></table>';
  $rescheduleBtn = '<p style="margin:14px 0;text-align:center;font-family:Georgia,Times,serif;font-size:14px;color:' . $muted . ';">¿Necesitas cambiar el día? <a href="' . ($rescheduleUrl ?? '#') . '" style="color:' . $accent . ';text-decoration:underline;">Escríbeme por WhatsApp</a>.</p>';

  $processedBody = e($emailBody);

  // Auto-link bare URLs before injecting button HTML, so we don't touch our own anchors.
  $processedBody = preg_replace_callback(
      '#(https?://[^\s<>\'"]+)#i',
      function ($m) use ($accent) {
          $url = $m[1];
          return '<a href="' . $url . '" style="color:' . $accent . ';text-decoration:underline;word-break:break-all;">' . $url . '</a>';
      },
      $processedBody
  );

  $processedBody = str_replace('[BOTON_CONFIRMAR]', $confirmBtn, $processedBody);
  $processedBody = str_replace('[BOTON_CANCELAR]', $cancelBtn, $processedBody);
  $processedBody = str_replace('[BOTON_CAMBIAR]', $rescheduleBtn, $processedBody);
  $processedBody = nl2br($processedBody);
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="light only">
  <title>{{ $appointment->summary }}</title>
  <style>
    a { color: {{ $accent }}; }
    @media only screen and (max-width: 480px) {
      .container { padding-left: 24px !important; padding-right: 24px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:{{ $page }};color:{{ $ink }};font-family:Georgia,'Times New Roman',Times,serif;-webkit-font-smoothing:antialiased;">

  <div style="display:none;font-size:1px;color:{{ $page }};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    {{ $preheader }}
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:{{ $page }};">
    <tr>
      <td align="center" style="padding:48px 16px;">

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="max-width:560px;width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:4px;">

          <tr>
            <td class="container" style="padding:40px 48px 0 48px;">
              <p style="margin:0;font-family:Georgia,'Times New Roman',Times,serif;font-size:14px;letter-spacing:0.12em;text-transform:uppercase;color:{{ $muted }};">
                {{ $professionalName }}
              </p>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px;">
                <tr><td style="border-top:1px solid {{ $line }};font-size:0;line-height:0;">&nbsp;</td></tr>
              </table>
            </td>
          </tr>

          <tr>
            <td class="container" style="padding:32px 48px;font-family:Georgia,'Times New Roman',Times,serif;font-size:16px;line-height:1.7;color:{{ $ink }};">
              {!! $processedBody !!}
            </td>
          </tr>

          <tr>
            <td class="container" style="padding:0 48px 40px 48px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr><td style="border-top:1px solid {{ $line }};font-size:0;line-height:0;">&nbsp;</td></tr>
              </table>
              <p style="margin:20px 0 0 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:{{ $muted }};">
                Recibes este correo porque tienes una cita programada con {{ $professionalName }}.
                Si crees que es un error, responde a este mensaje.
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
