@php
  $professionalName = optional(optional($patient)->user)->name ?? config('app.name');
  $patientFirstName = explode(' ', trim($patient->name))[0] ?? $patient->name;
  $preheader = 'Tu cita del ' . $appointment->formatted_date . ' a las ' . $appointment->formatted_time . '. Confirma o cancela en un clic.';
  $accent = '#3d5a80';
  $accentDark = '#2f4868';
  $ink = '#1f2937';
  $muted = '#6b7280';
  $line = '#e5e7eb';
  $page = '#f5f5f4';
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="light only">
  <meta name="supported-color-schemes" content="light only">
  <title>{{ $appointment->summary }}</title>
  <style>
    a { color: {{ $accent }}; }
    a.btn-primary:hover { background: {{ $accentDark }} !important; }
    @media only screen and (max-width: 480px) {
      .container { padding: 32px 24px !important; }
      .stack { display: block !important; width: 100% !important; padding: 0 !important; }
      .stack-gap { height: 12px !important; }
      .date-display { font-size: 26px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:{{ $page }};color:{{ $ink }};font-family:Georgia,'Times New Roman',Times,serif;-webkit-font-smoothing:antialiased;">

  {{-- Preheader (hidden, shows in inbox preview) --}}
  <div style="display:none;font-size:1px;color:{{ $page }};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    {{ $preheader }}
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:{{ $page }};">
    <tr>
      <td align="center" style="padding:48px 16px;">

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="max-width:560px;width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:4px;">

          {{-- Header: name of the practice, no graphics --}}
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

          {{-- Greeting + intro --}}
          <tr>
            <td class="container" style="padding:32px 48px 0 48px;font-family:Georgia,'Times New Roman',Times,serif;font-size:16px;line-height:1.7;color:{{ $ink }};">
              <p style="margin:0 0 16px 0;">Hola {{ $patientFirstName }},</p>
              <p style="margin:0;">Te escribo para recordarte tu próxima sesión. Si todo sigue igual, solo necesito que confirmes; si no puedes asistir, avísame con un clic.</p>
            </td>
          </tr>

          {{-- Date / time block --}}
          <tr>
            <td class="container" style="padding:32px 48px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid {{ $line }};border-bottom:1px solid {{ $line }};">
                <tr>
                  <td align="center" style="padding:28px 16px;">
                    <p style="margin:0;font-family:Georgia,'Times New Roman',Times,serif;font-size:13px;letter-spacing:0.1em;text-transform:uppercase;color:{{ $muted }};">
                      {{ $appointment->formatted_date }}
                    </p>
                    <p class="date-display" style="margin:8px 0 0 0;font-family:Georgia,'Times New Roman',Times,serif;font-size:32px;font-weight:normal;letter-spacing:-0.01em;color:{{ $ink }};">
                      {{ $appointment->formatted_time }}
                      <span style="font-size:14px;color:{{ $muted }};letter-spacing:0;">· {{ $appointment->timezone }}</span>
                    </p>
                    @if(!empty($appointment->summary))
                    <p style="margin:14px 0 0 0;font-family:Georgia,'Times New Roman',Times,serif;font-size:15px;color:{{ $muted }};font-style:italic;">
                      {{ $appointment->summary }}
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              @if($appointment->hangout_link)
              <p style="margin:18px 0 0 0;font-family:Georgia,'Times New Roman',Times,serif;font-size:14px;line-height:1.6;color:{{ $muted }};text-align:center;">
                La sesión es online.
                <a href="{{ $appointment->hangout_link }}" style="color:{{ $accent }};text-decoration:underline;">Unirse a la videollamada</a>
              </p>
              @endif
            </td>
          </tr>

          {{-- CTAs --}}
          <tr>
            <td class="container" style="padding:0 48px 16px 48px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td class="stack" align="center" valign="middle" style="padding-right:6px;" width="50%">
                    <a href="{{ $confirmUrl }}" class="btn-primary" style="display:block;background:{{ $accent }};color:#ffffff;text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;letter-spacing:0.01em;padding:14px 20px;border:1px solid {{ $accent }};border-radius:2px;text-align:center;">
                      Confirmar asistencia
                    </a>
                  </td>
                  <td class="stack-gap" style="display:none;font-size:0;line-height:0;">&nbsp;</td>
                  <td class="stack" align="center" valign="middle" style="padding-left:6px;" width="50%">
                    <a href="{{ $cancelUrl }}" style="display:block;background:#ffffff;color:{{ $ink }};text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;letter-spacing:0.01em;padding:14px 20px;border:1px solid {{ $line }};border-radius:2px;text-align:center;">
                      No podré asistir
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Secondary action: reschedule --}}
          @if(!empty($rescheduleUrl))
          <tr>
            <td class="container" align="center" style="padding:0 48px 8px 48px;font-family:Georgia,'Times New Roman',Times,serif;font-size:14px;color:{{ $muted }};">
              ¿Necesitas cambiar el día u hora?
              <a href="{{ $rescheduleUrl }}" style="color:{{ $accent }};text-decoration:underline;">Escríbeme por WhatsApp</a>.
            </td>
          </tr>
          @endif

          {{-- Sign-off --}}
          <tr>
            <td class="container" style="padding:32px 48px 8px 48px;font-family:Georgia,'Times New Roman',Times,serif;font-size:16px;line-height:1.7;color:{{ $ink }};">
              <p style="margin:0;">Un abrazo,<br>{{ $professionalName }}</p>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td class="container" style="padding:32px 48px 40px 48px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr><td style="border-top:1px solid {{ $line }};font-size:0;line-height:0;">&nbsp;</td></tr>
              </table>
              <p style="margin:20px 0 0 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:{{ $muted }};">
                Recibes este correo porque tienes una cita programada con {{ $professionalName }}.
                Si crees que es un error, responde a este mensaje y lo resolvemos.
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
