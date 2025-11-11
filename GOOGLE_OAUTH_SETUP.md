# Configuración de Google OAuth - Nimbus

## Error: "Request had insufficient authentication scopes"

Este error ocurre cuando la aplicación no tiene los permisos correctos de Google Calendar.

### Causa del problema

- Los tokens de autenticación existentes fueron creados **sin el scope de Google Calendar**
- Solo se autorizaron los scopes básicos: `openid`, `email`, `profile`
- Se necesita el scope: `https://www.googleapis.com/auth/calendar`

### Solución

#### 1. **Volver a conectar la cuenta de Google**

La aplicación ahora detecta automáticamente este error y te redirige a la página de conexión con un mensaje claro.

**Pasos:**
1. Ve a `/auth/google` o haz clic en el botón "Conectar Google"
2. Autoriza **todos los permisos** cuando Google te lo solicite
3. Asegúrate de marcar la casilla de acceso a Calendar

#### 2. **Verificar los scopes en tu proyecto de Google Cloud**

En [Google Cloud Console](https://console.cloud.google.com/):

1. Ve a **APIs & Services** > **OAuth consent screen**
2. En la sección **Scopes**, asegúrate de que esté habilitado:
   ```
   https://www.googleapis.com/auth/calendar
   ```
3. Si no está, agrégalo:
   - Haz clic en "Add or Remove Scopes"
   - Busca "Google Calendar API"
   - Selecciona el scope `.../auth/calendar`
   - Guarda los cambios

#### 3. **Limpiar tokens antiguos (si es necesario)**

Si el problema persiste, elimina los tokens viejos de la base de datos:

```sql
-- En producción (MySQL)
DELETE FROM google_tokens WHERE account_email = 'tu-email@gmail.com';

-- En desarrollo (SQLite)
sqlite3 database/database.sqlite
DELETE FROM google_tokens WHERE account_email = 'tu-email@gmail.com';
.quit
```

Luego vuelve a conectar tu cuenta.

### Configuración correcta

El archivo `GoogleClientFactory.php` ahora solicita automáticamente estos scopes:

```php
$scopes = [
    'openid',
    'email',
    'profile',
    'https://www.googleapis.com/auth/calendar',
];
```

### Variables de entorno

**No es necesario** configurar `GOOGLE_SCOPES` en tu `.env` a menos que quieras personalizar los scopes.

Los scopes predeterminados ya son correctos.

### Verificación

Después de volver a conectar, verifica que el scope esté guardado:

```sql
SELECT account_email, scope FROM google_tokens;
```

Deberías ver algo como:
```
email profile openid https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile
```

### Prevención

- **No elimines** el scope de Calendar del código
- **Siempre** usa `GoogleClientFactory::make()` para crear clientes
- **No hardcodees** tokens de acceso

## FAQ

### ¿Por qué necesita permisos de Calendar?

Nimbus necesita:
- **Ver calendarios**: Para listar los calendarios disponibles
- **Leer eventos**: Para obtener las próximas citas
- **Acceso offline**: Para enviar recordatorios cuando no estés usando la app

### ¿Es seguro?

Sí. Nimbus:
- ✅ Solo solicita permisos de lectura
- ✅ Usa OAuth 2.0 (estándar de la industria)
- ✅ Guarda tokens encriptados
- ✅ No comparte datos con terceros

### ¿Cómo revoco el acceso?

Ve a [myaccount.google.com/permissions](https://myaccount.google.com/permissions) y elimina "Nimbus" de la lista.

---

**Última actualización:** 2024-11-11  
**Versión de Laravel:** 11.x  
**Google API Client:** ^2.0
