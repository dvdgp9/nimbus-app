<h1 align="center">Nimbus</h1>

Nimbus automatiza recordatorios y confirmaciones de sesiones para consultas de psicología online. Se integra con Google Calendar, envía notificaciones por SMS (Twilio) y email, y ofrece enlaces seguros de 1 clic para confirmar, reprogramar (WhatsApp) o cancelar citas.

## Stack

- **Backend:** Laravel 11 (PHP ≥ 8.2)
- **Base de datos:** MySQL
- **Mensajería:** Twilio (SMS) + SMTP
- **Calendar:** Google Calendar API (OAuth offline)
- **Hosting objetivo:** cPanel con cron `php artisan schedule:run`

## Requisitos

- PHP 8.2+
- Composer
- Node.js 20+ (para assets opcionales)
- MySQL 8+
- Cuenta de Twilio (o credenciales por tenant)
- Cliente OAuth de Google (puede ser global o por tenant)

## Configuración rápida

1. Instala dependencias

   ```bash
   composer install
   npm install
   ```

2. Copia `.env.example` a `.env` y ajusta valores clave:

   - `APP_URL=https://nimbus.wthefox.com` (o entorno local)
   - `DB_*` con credenciales MySQL
   - `MAIL_*` para `confirmacion@nimbus.wthefox.com`
   - `TWILIO_*` por tenant (o credenciales globales)
   - `GOOGLE_*` (client ID/secret + redirect)
   
   > **Nota:** Para configurar Google OAuth correctamente, consulta [GOOGLE_OAUTH_SETUP.md](./GOOGLE_OAUTH_SETUP.md)

3. Genera clave y migra base de datos

   ```bash
   php artisan key:generate
   php artisan migrate
   ```

4. Lanza el servidor de desarrollo

   ```bash
   php artisan serve
   ```

5. Ejecuta tests

   ```bash
   php artisan test
   ```

## Cron en cPanel

Programa un cron cada minuto:

```bash
php /home/<usuario>/nimbus-app/artisan schedule:run
```

## Estructura modular (en progreso)

- `tenants`: configuración, marca blanca y credenciales por cliente.
- `appointments`: reflejo local de eventos de Google Calendar con estados en `extendedProperties.private` (`nimbus_status`).
- `shortlinks`: tokens firmados para enlaces de Confirmar / Reprogramar / Cancelar.
- `communications`: registro de notificaciones SMS/email y su auditoría.

## Roadmap MVP

- Sincronización por polling (48h de antelación) + WhatsApp para reprogramar.
- Panel para próximas citas, plantillas y configuraciones por tenant.
- Auditoría y cumplimiento RGPD (consentimientos, STOP en SMS, minimización de datos).

Revisa `.cursor/scratchpad.md` para el plan detallado, hitos y decisiones.
