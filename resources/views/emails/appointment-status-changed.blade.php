<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cita Actualizado</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .email-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .email-header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .status-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .status-message {
            background: {{ $action === 'confirmed' ? '#ecfdf5' : '#fef2f2' }};
            border-left: 4px solid {{ $action === 'confirmed' ? '#10b981' : '#ef4444' }};
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .status-message h2 {
            margin: 0 0 10px 0;
            font-size: 20px;
            color: {{ $action === 'confirmed' ? '#065f46' : '#991b1b' }};
        }
        .status-message p {
            margin: 0;
            color: {{ $action === 'confirmed' ? '#047857' : '#dc2626' }};
            font-size: 16px;
        }
        .info-section {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        .info-section h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 120px;
            font-size: 14px;
        }
        .info-value {
            color: #2d3748;
            font-size: 14px;
            flex: 1;
        }
        .appointment-details {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        .appointment-details h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .datetime-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .datetime-box .icon {
            font-size: 24px;
            margin-right: 12px;
        }
        .datetime-box .label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .datetime-box .value {
            font-size: 18px;
            color: #2d3748;
            font-weight: 600;
        }
        .footer {
            padding: 30px;
            text-align: center;
            background: #f7fafc;
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer p {
            margin: 5px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 10px;
            }
            .email-body {
                padding: 30px 20px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <div class="status-icon">
                    @if($action === 'confirmed')
                        ✅
                    @else
                        ❌
                    @endif
                </div>
                <h1>Actualización de Cita</h1>
            </div>

            <!-- Body -->
            <div class="email-body">
                <div class="greeting">
                    ¡Hola! 👋
                </div>

                <div class="status-message">
                    @if($action === 'confirmed')
                        <h2>✅ Cita Confirmada</h2>
                        <p>{{ $patient->name }} ha confirmado su asistencia a la cita.</p>
                    @else
                        <h2>❌ Cita Cancelada</h2>
                        <p>{{ $patient->name }} ha cancelado su cita.</p>
                    @endif
                </div>

                <!-- Patient Information -->
                <div class="info-section">
                    <h3>📋 Información del Paciente</h3>
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value">{{ $patient->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Código:</span>
                        <span class="info-value">{{ $patient->code }}</span>
                    </div>
                    @if($patient->email)
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $patient->email }}</span>
                    </div>
                    @endif
                    @if($patient->phone)
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">{{ $patient->phone }}</span>
                    </div>
                    @endif
                </div>

                <!-- Appointment Details -->
                <div class="appointment-details">
                    <h3>📅 Detalles de la Cita</h3>
                    
                    <div class="datetime-box">
                        <div class="label">Fecha</div>
                        <div class="value">📅 {{ $appointment->formatted_date }}</div>
                    </div>

                    <div class="datetime-box">
                        <div class="label">Hora</div>
                        <div class="value">🕐 {{ $appointment->formatted_time }}</div>
                    </div>

                    @if($appointment->summary)
                    <div class="datetime-box">
                        <div class="label">Descripción</div>
                        <div class="value">📋 {{ $appointment->summary }}</div>
                    </div>
                    @endif

                    @if($action === 'confirmed')
                    <div class="datetime-box" style="background: #ecfdf5; border-left: 4px solid #10b981;">
                        <div class="label" style="color: #065f46;">Estado Actual</div>
                        <div class="value" style="color: #065f46;">✅ Confirmada</div>
                    </div>
                    @else
                    <div class="datetime-box" style="background: #fef2f2; border-left: 4px solid #ef4444;">
                        <div class="label" style="color: #991b1b;">Estado Actual</div>
                        <div class="value" style="color: #991b1b;">❌ Cancelada</div>
                    </div>
                    @endif
                </div>

                <p style="color: #718096; font-size: 14px; line-height: 1.6; margin-top: 30px;">
                    @if($action === 'confirmed')
                        El paciente ha confirmado su asistencia a través del enlace que enviaste. Todo listo para la sesión. 🎉
                    @else
                        El paciente ha cancelado la cita a través del enlace que enviaste. Recuerda contactarle si necesitas reprogramar. 📞
                    @endif
                </p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>Esta es una notificación automática de <strong>Nimbus</strong></p>
                <p>Tu sistema de gestión de citas</p>
            </div>
        </div>
    </div>
</body>
</html>
