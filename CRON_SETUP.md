# Configuración de Cron Job en cPanel

Para que los recordatorios se envíen automáticamente cada 30 minutos, necesitas configurar un cron job en cPanel.

## Pasos para configurar en cPanel

### 1. Accede a Cron Jobs
- Panel de control de cPanel
- Busca "Cron Jobs" o "Tareas cron"

### 2. Añade un nuevo cron job

**Frecuencia:** Cada minuto
```
* * * * *
```

**Comando:**
```bash
/usr/local/bin/php /home/USUARIO/nimbus-app/artisan schedule:run >> /dev/null 2>&1
```

Sustituye:
- `USUARIO` por tu usuario de cPanel (ej: `umilpdfe`)
- Verifica la ruta a PHP con `which php` si tienes SSH, o usa `/usr/local/bin/php` que suele ser el estándar

### 3. Verifica la ruta correcta

Si tienes dudas sobre la ruta de PHP, prueba estas variantes comunes:
- `/usr/local/bin/php`
- `/usr/bin/php`
- `/opt/alt/php83/usr/bin/php` (si usas versión específica)

### 4. Ejemplo completo para tu caso

Según tu configuración actual:
```bash
/usr/local/bin/php /home/umilpdfe/nimbus-app/artisan schedule:run >> /dev/null 2>&1
```

## ¿Qué hace este cron?

- **Ejecuta cada minuto:** Laravel internamente decide qué tareas ejecutar según el horario configurado.
- **`schedule:run`:** Verifica si hay tareas programadas que deban ejecutarse en ese momento.
- **`nimbus:send-reminders`:** Configurado para ejecutarse cada 30 minutos automáticamente.
- **`withoutOverlapping()`:** Evita que se ejecute dos veces si una tarea aún está corriendo.
- **`>> /dev/null 2>&1`:** Redirige la salida para no llenar el buzón de email.

## Verificar que funciona

Una vez configurado:

1. Espera 30-60 minutos
2. Revisa en la base de datos la tabla `communications` si hay nuevos registros
3. Verifica los logs: `storage/logs/laravel.log`

## Troubleshooting

### El cron no se ejecuta
- Verifica que la ruta a PHP sea correcta
- Verifica que la ruta al proyecto sea correcta
- Comprueba que el usuario tenga permisos sobre el directorio

### Los recordatorios no se envían
- Verifica que haya citas en las próximas 24 horas
- Verifica que los pacientes tengan:
  - `phone` o `email` configurado
  - `preferred_channel` definido
  - Consentimiento dado (`consent_email`, `consent_whatsapp`, etc.)
- Revisa `storage/logs/laravel.log` para errores

### Ejecutar manualmente para probar
Si tienes SSH (o una ruta temporal):
```bash
php artisan nimbus:send-reminders --dry-run
```

## Alternativa sin cron (temporal)

Si no puedes configurar cron, puedes crear una ruta temporal que ejecute el comando:
```php
Route::get('/cron/run', function () {
    Artisan::call('nimbus:send-reminders');
    return 'OK';
});
```

Y llamarla cada 30 min con un servicio externo como:
- https://cron-job.org/
- https://www.easycron.com/

⚠️ **Importante:** Esta ruta debe estar protegida o eliminarse en producción.
