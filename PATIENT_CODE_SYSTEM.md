# Sistema de Códigos de Pacientes

## 📋 Cambio de lógica

**Antes:**
- El sistema buscaba el email del primer asistente en el evento de Google Calendar
- Creaba automáticamente un paciente con ese email

**Ahora:**
- El sistema extrae un **CÓDIGO** del título del evento
- Busca un paciente con ese código en la base de datos
- Si NO existe, la cita queda **sin asignar** y se resalta en amarillo
- Desde el panel podrás crear el paciente o asignar a uno existente

---

## 🗄️ SQL para ejecutar en phpMyAdmin

```sql
-- Añadir campo 'code' a la tabla patients
ALTER TABLE `patients` 
ADD COLUMN `code` VARCHAR(50) UNIQUE AFTER `id`,
ADD INDEX `idx_code` (`code`);
```

Este SQL:
- Añade la columna `code` (VARCHAR 50, único)
- La coloca después del `id`
- Crea un índice para búsquedas rápidas

---

## 🔍 Cómo funciona

### Formato de títulos de eventos soportados:

```
✅ "P123 - Consulta de seguimiento"   → Código: P123
✅ "P123: Primera visita"              → Código: P123
✅ "P123 Revisión"                     → Código: P123
✅ "P123"                              → Código: P123
✅ "ABC - Terapia"                     → Código: ABC
✅ "001 Sesión inicial"                → Código: 001

❌ "Consulta P123"                     → No detecta (no está al inicio)
❌ "Consulta general"                  → No detecta (sin código)
```

### Proceso de sincronización:

1. **Sincronizas eventos** desde `/events` → "Sincronizar"
2. Por cada evento:
   - Extrae el código del inicio del título
   - Busca en `patients` un paciente con ese código
   - Si existe → Vincula la cita al paciente
   - Si NO existe → La cita queda con `patient_id = NULL`

3. **En la vista `/events`**:
   - Las citas SIN paciente se resaltan con borde amarillo
   - Se muestra: ⚠️ "Sin paciente asignado"
   - Las citas CON paciente muestran: ✅ **CODE** - Nombre del paciente

---

## 🎯 Próximos pasos

### 1. Ejecuta el SQL en phpMyAdmin
Copia y pega el SQL de arriba en la pestaña SQL de tu base de datos.

### 2. Crea pacientes con sus códigos
Cuando tengamos el panel de gestión de pacientes, podrás:
- Ver todos los pacientes
- Crear nuevos con su código
- Editar código de pacientes existentes
- Ver qué citas tiene cada paciente

### 3. Sincroniza tus eventos
- Ve a `/events`
- Click en "Sincronizar"
- Verás las citas con su estado:
  - Verde si tienen paciente
  - Amarillo si no tienen paciente

### 4. Panel de pacientes (siguiente paso)
Te crearé un CRUD completo para:
- ✅ Crear pacientes con código
- ✅ Editar datos (código, nombre, email, teléfono)
- ✅ Dar consentimientos
- ✅ Ver citas de cada paciente
- ✅ Asignar citas sin paciente
- ✅ Crear paciente desde cita sin asignar

---

## 🔧 Configuración futura

En el panel también añadiremos:
- **Timeframe de recordatorios**: Configurar si se envían a 24h, 48h, 72h, etc.
- **Frecuencia de ejecución**: Cada 30 min, 1h, etc.
- **Plantillas de email/SMS**: Personalizar mensajes por canal
- **Reglas de validación**: Formato de códigos (ej: solo números, P+número, etc.)

---

## 📝 Notas técnicas

### Extracción del código
```php
// El patrón regex usado:
^([A-Za-z0-9]+)(?:\s*[-:]\s*|\s+|$)

// Esto captura:
// - Inicio de línea (^)
// - Uno o más caracteres alfanuméricos ([A-Za-z0-9]+)
// - Seguido de: guion, dos puntos, espacio o fin de línea
// - El código se normaliza a MAYÚSCULAS
```

### Normalización
Todos los códigos se convierten a **MAYÚSCULAS** automáticamente:
- "p123" → "P123"
- "abc" → "ABC"

Esto evita problemas de coincidencia por diferencias de mayúsculas/minúsculas.

---

## ⚠️ Importante

**Antes de activar el cron de recordatorios:**
1. ✅ Ejecuta el SQL
2. ✅ Crea al menos un paciente de prueba con código
3. ✅ Sincroniza eventos y verifica que se vinculan correctamente
4. ✅ Añade teléfono y da consentimiento al paciente de prueba
5. ✅ Ejecuta `php artisan nimbus:send-reminders --dry-run` manualmente
6. ✅ Si todo está OK, activa el cron

Sin estos pasos, los recordatorios no se enviarán porque:
- No habrá pacientes con códigos
- Los pacientes no tendrán consentimiento
- Las citas no estarán vinculadas
