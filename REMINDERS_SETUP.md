# Sistema de Recordatorios - Nimbus

## 🎯 Descripción

Sistema completo de recordatorios automáticos para citas de psicología con soporte para:
- ✉️ **Email** (SMTP)
- 📱 **SMS** (Twilio)
- 💚 **WhatsApp** (Twilio)

## 📋 Prerequisitos

### 1. **Instalar dependencias**

```bash
# Instalar SDK de Twilio
composer require twilio/sdk

# Ejecutar migraciones
php artisan migrate
```

### 2. **Configurar Twilio**

1. Crea una cuenta en [Twilio](https://www.twilio.com/)
2. Obtén tus credenciales:
   - Account SID
   - Auth Token
   - Número de teléfono (para SMS)
   - Número de WhatsApp (para WhatsApp Business)

3. Configura en `.env`:
```env
TWILIO_SID=AC...
TWILIO_TOKEN=...
TWILIO_FROM=+34XXXXXXXXX
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
WHATSAPP_PROFESSIONAL_PHONE=+34XXXXXXXXX
```

### 3. **Configurar SMTP** (si usas email. Estas claves no son reales)

Ejemplo de configuración en `.env` (sustituye por tus valores reales):
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.tu-dominio.com
MAIL_PORT=587
MAIL_USERNAME="noreply@tu-dominio.com"
MAIL_PASSWORD="<SMTP_PASSWORD>"
MAIL_FROM_ADDRESS="noreply@tu-dominio.com"
```

## 📊 Estructura de Base de Datos

### Tablas creadas:

1. **`patients`** - Información de contacto y preferencias
   - `name`, `email`, `phone`
   - `preferred_channel`: email|sms|whatsapp
   - `consent_email`, `consent_sms`, `consent_whatsapp`

2. **`appointments`** - Citas sincronizadas desde Google Calendar
   - `google_event_id`, `calendar_id`
   - `patient_id` (foreign key)
   - `nimbus_status`: pending|reminder_sent|confirmed|cancelled|completed
   - `reminder_sent_at`, `confirmed_at`, `cancelled_at`

3. **`communications`** - Audit log de mensajes enviados
   - `appointment_id`, `patient_id`
   - `channel`, `type`, `status`
   - `sent_at`, `delivered_at`

4. **`shortlinks`** - Enlaces seguros de un solo uso
   - `appointment_id`, `token`, `action`
   - `expires_at`, `used`, `used_at`

## 🔄 Flujo de Trabajo

### 1. **Sincronización de Eventos**

```php
// En EventsController o mediante cron
$events = $googleCalendarService->listUpcomingEvents($email, 48);
$googleCalendarService->syncAppointments($events);
```

Esto crea o actualiza registros en `appointments`.

### 2. **Asignación de Pacientes**

**Opción A: Manual** (recomendado para MVP)
```php
// Crear paciente
$patient = Patient::create([
    'name' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'phone' => '+34600000000',
    'preferred_channel' => 'whatsapp',
]);

// Dar consentimiento
$patient->giveConsent(['email', 'whatsapp']);

// Asignar a cita
$appointment->update(['patient_id' => $patient->id]);
```

**Opción B: Automática**
```php
// Al sincronizar, extraer email del asistente de Google Calendar
// y crear/buscar paciente automáticamente
```

### 3. **Envío de Recordatorios**

#### Manual (para testing):
```bash
# Dry run (simula sin enviar)
php artisan nimbus:send-reminders --dry-run

# Enviar recordatorios para las próximas 24h
php artisan nimbus:send-reminders

# Enviar recordatorios para las próximas 48h
php artisan nimbus:send-reminders --hours=48
```

#### Automático (scheduler):
Edita `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Ejecutar cada hora
    $schedule->command('nimbus:send-reminders')
             ->hourly()
             ->withoutOverlapping();
             
    // O cada 30 minutos
    $schedule->command('nimbus:send-reminders')
             ->everyThirtyMinutes()
             ->withoutOverlapping();
}
```

En producción (cPanel):
```bash
# Cron job cada minuto
* * * * * cd /home/usuario/nimbus-app && php artisan schedule:run >> /dev/null 2>&1
```

### 4. **Enlaces de Acción**

Cada recordatorio incluye 3 enlaces:

1. **Confirmar** → `/link/{token}` → `Shortlink::markAsUsed()` → `Appointment::confirm()`
2. **Cancelar** → `/link/{token}` → `Shortlink::markAsUsed()` → `Appointment::cancel()`
3. **Reprogramar** → `https://wa.me/+34XXX?text=...` → WhatsApp directo

## 🎨 Personalización de Mensajes

### Email

Edita: `resources/views/emails/appointment-reminder.blade.php`

Variables disponibles:
- `$appointment` - Datos de la cita
- `$patient` - Datos del paciente
- `$confirmUrl`, `$cancelUrl`, `$rescheduleUrl` - Enlaces de acción

### SMS

Edita: `NotificationService::buildSMSMessage()`

Límite: **160 caracteres** (o 70 con emojis)

### WhatsApp

Edita: `NotificationService::buildWhatsAppMessage()`

Formato: **Markdown** de WhatsApp
- `*negrita*`
- `_cursiva_`
- `~tachado~`

## 📱 Canales de Notificación

### Email ✉️
- **Pro:** Gratuito, sin límites
- **Contra:** Menor tasa de apertura
- **Uso:** Recordatorios formales, confirmaciones

### SMS 📱
- **Pro:** Alta tasa de apertura (98%)
- **Contra:** Costo por mensaje (~0.08€)
- **Uso:** Recordatorios urgentes, confirmaciones rápidas

### WhatsApp 💚 ⭐ **RECOMENDADO**
- **Pro:** Alta tasa de apertura, conversacional, multimedia
- **Contra:** Requiere número de negocio verificado
- **Uso:** Canal principal de recordatorios

## 🧪 Testing

### 1. Crear datos de prueba

```php
// Crear paciente de prueba
$patient = Patient::create([
    'name' => 'Test Patient',
    'email' => 'test@example.com',
    'phone' => '+34600000000',
    'preferred_channel' => 'email', // Cambiar a 'whatsapp' cuando tengas Twilio
    'consent_email' => true,
    'consent_whatsapp' => true,
    'consent_date' => now(),
]);

// Crear cita de prueba
$appointment = Appointment::create([
    'google_event_id' => 'test-' . uniqid(),
    'calendar_id' => 'primary',
    'summary' => 'Sesión de prueba',
    'description' => 'Esta es una cita de prueba',
    'start_at' => now()->addHours(20), // En 20 horas
    'end_at' => now()->addHours(21),
    'patient_id' => $patient->id,
    'nimbus_status' => 'pending',
]);
```

### 2. Probar comando

```bash
# Dry run
php artisan nimbus:send-reminders --hours=24 --dry-run

# Enviar de verdad
php artisan nimbus:send-reminders --hours=24
```

### 3. Verificar logs

```bash
tail -f storage/logs/laravel.log
```

## 🔒 RGPD & Compliance

### Consentimientos requeridos:

```php
// Verificar antes de enviar
if ($patient->hasConsentFor('whatsapp')) {
    // OK para enviar
}
```

### Audit trail:

Todos los mensajes se registran en `communications`:
- Cuándo se envió
- A quién
- Por qué canal
- Estado (sent/delivered/failed)
- Consentimiento verificado

## 📈 Mejoras Futuras

- [ ] Webhooks de Twilio para actualizar estado de entrega
- [ ] Plantillas personalizables por tipo de cita
- [ ] Recordatorios múltiples (48h + 24h + 2h antes)
- [ ] Respuestas automáticas en WhatsApp
- [ ] Panel de estadísticas de entrega
- [ ] A/B testing de mensajes
- [ ] Segmentación por tipo de paciente

## 🆘 Troubleshooting

### Email no se envía
```bash
# Verificar config
php artisan config:cache

# Probar envío
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

### Twilio falla
```bash
# Verificar credenciales
php artisan tinker
>>> config('services.twilio.sid')
>>> config('services.twilio.token')
```

### Appointment no tiene patient_id
```sql
-- Verificar relaciones
SELECT id, summary, patient_id FROM appointments WHERE patient_id IS NULL;
```

## 📚 Recursos

- [Twilio PHP SDK](https://www.twilio.com/docs/libraries/php)
- [WhatsApp Business API](https://www.twilio.com/whatsapp)
- [Laravel Mail](https://laravel.com/docs/11.x/mail)
- [Laravel Scheduler](https://laravel.com/docs/11.x/scheduling)

---

**¿Necesitas ayuda?** Revisa los logs en `storage/logs/laravel.log`
