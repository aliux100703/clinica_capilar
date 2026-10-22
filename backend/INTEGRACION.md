# Backend de la clínica capilar

> Contrato actualizado de fase 5: consultar [../INTEGRACION.md](../INTEGRACION.md). Los POST ahora requieren idempotency_key UUID y aceptan website vacío. El frontend ya está conectado; las secciones siguientes conservan el diagnóstico de preparación anterior.

## Estado verificado

- PHP 8.2.12, Composer 2.2.24, Laravel 12.70.1.
- Migración de cinco tablas y columna `users.role`, modelos, API, validación, CORS y autorización preparados.
- Las migraciones se prueban con SQLite en memoria, sin borrar ni modificar bases locales.
- Conexión local corregida a `127.0.0.1:3307`, base `clinica_capilar`. Las cuatro migraciones aparecen ejecutadas, incluida `2026_10_21_000001_create_clinic_tables`. El puerto 3306 rechazaba las credenciales anteriores.
- `db:show` falla al consultar `performance_schema.session_status` en este servidor; usar `migrate:status` y consultas directas para verificar la conexión, sin cambiar tablas del sistema.
- Filament 5 requiere PHP 8.2+ y Laravel 11.28+, según https://filamentphp.com/docs/5.x/introduction/installation. Composer no pudo acceder a Packagist desde este entorno. El panel está preparado, pero NO instalado ni verificado en ejecución. `/admin` permanece cerrado (404) hasta instalar sus dependencias.
- No se modificó el frontend ni se crearon usuarios o datos de pacientes. No se ejecutó `migrate:fresh`.
- Verificación final: 10 pruebas aprobadas (80 aserciones), sintaxis correcta en 35 archivos PHP y arranque real con `php artisan serve`; `GET /up` respondió 200. El servidor de comprobación se detuvo después.

## Configuración local

Desde `backend/`, editar `.env` sin compartirlo ni versionarlo. Conservar `APP_KEY`.

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=clinica_capilar
DB_USERNAME=root
DB_PASSWORD="CONTRASEÑA_LOCAL_REAL"
MAIL_MAILER=array
```

Usar contraseña vacía solamente si una conexión real lo confirma. Si existe `DB_URL`, debe eliminarse o ajustarse porque prevalece sobre los valores separados. No regenerar una `APP_KEY` existente. `MAIL_MAILER=array` evita tanto envíos reales como registrar mensajes en logs. No hay código que despache correos, notificaciones o trabajos de comunicación.

La zona horaria conserva el valor existente (UTC por defecto). Antes de conectar el selector de fechas, configurar `APP_TIMEZONE` con la zona de la clínica y usar esa misma zona en Next.js. Los campos de preferencia son fecha/hora civiles, no una reserva de disponibilidad. Las marcas de consentimiento usan la zona configurada.

Una vez confirmadas las credenciales:

```powershell
php artisan config:clear
php artisan clinic:create-database
php artisan migrate
php artisan migrate:status
php artisan db:show --database=mysql
php artisan serve --host=127.0.0.1 --port=8000
```

`clinic:create-database` solo permite la conexión MySQL local con nombre `clinica_capilar`, sin `DB_URL`. Ejecuta `CREATE DATABASE IF NOT EXISTS`; nunca elimina ni sustituye datos. Necesita permisos de creación. `migrate` aplica únicamente migraciones pendientes. No ejecutar `migrate:fresh`, `db:wipe` ni rollbacks contra datos existentes.

## API pública

Base local: `http://localhost:8000/api/v1`. Enviar `Accept: application/json`; los POST usan también `Content-Type: application/json`. No requiere autenticación, cookies ni tokens. CORS permite exclusivamente el origen `http://localhost:3000`, con GET/POST/OPTIONS y sin credenciales.

| Método | Ruta | Resultado |
| --- | --- | --- |
| GET | `/treatments?page=1` | Tratamientos publicados, 12 por página |
| GET | `/treatments/{slug}` | Tratamiento publicado; 404 si es borrador o no existe |
| GET | `/specialists?page=1` | Especialistas publicados, 12 por página |
| POST | `/valuation-requests` | Guarda solicitud pendiente; no crea ni confirma una cita |
| POST | `/contact-messages` | Guarda mensaje pendiente |

No existen endpoints de lectura pública de solicitudes, mensajes o citas. Los catálogos exponen solo campos públicos seleccionados explícitamente. Las credenciales profesionales publicadas contienen título, emisor, registro y URL de verificación; deben revisarse antes de publicar. No implican verificación automática por el sistema. El contenido de tratamientos es texto: no renderizar HTML arbitrario en Next.js.

### Solicitud de valoración

```json
{
  "name": "Persona ficticia",
  "email": "test@example.test",
  "phone": "+52 555 000 0000",
  "treatment_id": 1,
  "preferred_date": "2030-11-20",
  "preferred_time": "10:30",
  "consent": true
}
```

Nombre obligatorio (150 caracteres), correo válido (254), teléfono obligatorio (7–30 caracteres de teléfono), ID de tratamiento publicado y consentimiento aceptado. Fecha y hora opcionales; fecha `YYYY-MM-DD` no anterior a hoy, hora `HH:mm`; una hora necesita fecha. `treatment_id` debe obtenerse del catálogo: el ejemplo no crea el tratamiento 1. No hay datos de prueba sembrados en la base local.

### Contacto

```json
{
  "name": "Persona ficticia",
  "email": "test@example.test",
  "phone": null,
  "message": "Mensaje ficticio de prueba.",
  "consent": true
}
```

Teléfono opcional. Mensaje obligatorio, máximo 3000 caracteres. Los límites de nombre, correo y consentimiento son los mismos. Solicitar únicamente datos de contacto: no pedir historia clínica, diagnósticos ni fotografías médicas en estos formularios.

### Respuestas

Éxito de catálogo: `{"data": [...], "message": "...", "meta": {"current_page": 1, "last_page": 1, "total": 0}}`. La consulta por slug devuelve un objeto en `data`.

Ambos POST devuelven 201 con `{"data": null, "message": "..."}`. No devuelven nombres, correos, teléfonos ni el registro guardado. Campos adicionales como `status`, `consented_at` o `internal_notes` no se aceptan por asignación masiva desde el endpoint; el servidor fija estado y fecha del consentimiento.

Errores: `{"data": null, "message": "...", "errors": {}}`; para 422, `errors` contiene un arreglo de mensajes por campo. El frontend debe manejar 422 (validación), 429 (límite; respetar `Retry-After`) y 500 (fallo temporal). Las excepciones de API nunca devuelven consultas SQL, trazas ni bindings, incluso con debug local.

Límites por IP: 60 peticiones/minuto a la API; además, los dos formularios comparten 5 envíos/minuto y 20/hora. Los intentos inválidos también cuentan. El almacenamiento de caché configurado debe estar disponible; con caché en base de datos se requiere migrar primero. CORS limita la lectura desde navegadores; no sustituye el control de abuso.

## Conectar Next.js después

No se modificó `frontend/`. Cuando se autorice la integración, definir en `frontend/.env.local`:

```dotenv
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```

Esta variable es pública; no incluir credenciales MySQL ni claves del backend. Reiniciar Next.js tras cambiar variables. Ejemplo de integración futura:

```typescript
const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/contact-messages`, {
  method: 'POST',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  body: JSON.stringify(form),
});
const result = await response.json();
// Comprobar response.ok; mostrar errors por campo en 422.
// No presentar una solicitud de valoración como cita confirmada.
```

## Panel administrativo pendiente de instalación

Desde `backend/`, con acceso a Packagist:

```powershell
.\scripts\install-admin.ps1
php artisan migrate
php artisan clinic:create-admin
php artisan test
```

El script usa `composer require filament/filament:~5.0`, sin ignorar requisitos, publica assets y comprueba rutas. Composer debe resolver dependencias y extensiones en la máquina real antes de activar el panel; `php -m` actualmente no lista `intl`, `zip` ni `gd`, por lo que pueden aparecer requisitos adicionales. Habilitar únicamente las extensiones que Composer solicite en el PHP CLI de XAMPP. No usar `--ignore-platform-reqs`. No se modificó `composer.json` ni el lock para declarar un paquete que no se pudo instalar.

El proveedor se registra condicionalmente desde `AppServiceProvider` cuando existe Filament. No ejecutar el generador de paneles sobre el proveedor preparado. La autenticación utiliza `AdminUser` sobre la tabla `users` al instalar Filament; sin el paquete sigue funcionando el modelo `User`. No sobrescribir `AUTH_MODEL` con un modelo que omita `FilamentUser` y su control de acceso.

`clinic:create-admin` pide contraseña oculta y confirmación; crea un usuario nuevo con rol `admin`, no modifica cuentas existentes y no envía correos. No se crean contraseñas predeterminadas. Las cuentas nuevas normales conservan el rol `user`, y `role` no es asignable masivamente. El panel exige autenticación y rol `admin` en todas las solicitudes, incluidas las de Livewire persistentes, y las políticas también comprueban el rol para cada recurso.

Recursos preparados:

- Tratamientos: nombre, slug único, descripción, contenido, URL de imagen y estado (`draft`, `published`, `archived`).
- Especialistas: nombre, especialidad, descripción, URL de foto y credenciales verificables, con los mismos estados de publicación.
- Solicitudes: datos recibidos de solo lectura, revisión de estado (`pending`, `in_review`, `contacted`, `closed`).
- Mensajes: datos recibidos y consentimiento de solo lectura, estado (`pending`, `in_review`, `closed`).
- Citas: solicitud asociada, fecha, hora, estado (`pending`, `confirmed`, `completed`, `cancelled`) y notas operativas internas. Solo se crean manualmente en administración.

No hay borrado administrativo, exportación, búsqueda global de datos personales ni gestión pública de usuarios. Las relaciones con solicitudes impiden borrar tratamientos o solicitudes referenciados. El acceso administrativo no se ha probado en navegador porque falta instalar Filament.

## Verificación y seguridad

```powershell
php artisan test
php artisan route:list --path=api
php artisan about --only=environment
```

La suite usa SQLite en memoria y ejecuta `migrate`, sin `migrate:fresh`. Comprueba publicación, solicitudes pendientes sin citas, ausencia de datos personales en respuestas, validación, límites compartidos, CORS y autorización por roles. Incluye comprobación condicional del panel: sin Filament exige 404; con Filament comprueba redirección, rechazo de usuario normal y acceso del administrador al recurso de tratamientos. Es necesario completar después las pruebas de recursos y assets con el paquete instalado, y la verificación de migraciones sobre MySQL.

Los reportes de excepciones de la aplicación guardan únicamente la clase del error, sin mensaje SQL, payload ni trazas que puedan contener datos personales. Los campos sensibles no se vuelcan a la sesión tras errores de validación. No añadir logs de formularios. `.env` ya está ignorado por `backend/.gitignore`; el directorio de trabajo actual no contiene un repositorio Git con el que comprobar el estado de seguimiento. Revisarlo antes del primer commit. `.env.example` contiene únicamente valores de ejemplo, sin secretos.

La definición de retención y eliminación de datos recibidos queda para una decisión operativa posterior; no se automatizó eliminación de información existente.
