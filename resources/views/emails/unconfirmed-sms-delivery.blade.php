Hola {{ $communication->patient->user->name }},

No hemos podido confirmar en Acumbamail el SMS para {{ $communication->patient->name }}, cita del {{ $communication->appointment?->start_at?->format('d/m/Y H:i') ?? '—' }}.

Puede haberse enviado aunque su respuesta no llegara a Nimbus. Hemos detenido los reintentos para evitar duplicados. Revisa los envíos individuales en Acumbamail antes de volver a enviar un SMS.

El correo de recordatorio se gestiona por separado.

Referencia de comunicación: {{ $communication->id }}.

Nimbus
