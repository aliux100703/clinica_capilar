# Integración Next.js → Laravel → MySQL

> Fase 6: consultar [CMS.md](CMS.md) para los módulos de contenido, nuevos endpoints, revalidación y estado de instalación del panel.

## Estado

La integración está implementada sin añadir dependencias. Laravel conecta con MariaDB 10.4.32 de XAMPP en `127.0.0.1:3307`, base `clinica_capilar`. Las migraciones están ejecutadas, incluida la nueva migración de claves de idempotencia. No se modificaron datos existentes ni se utilizó `migrate:fresh`.

Actualmente no hay tratamientos ni especialistas publicados. El sitio muestra estados vacíos explícitos. No se han sembrado servicios, médicos o información médica ficticia. Filament no está instalado; su proveedor y recursos siguen preparados pero pendientes de instalar y verificar.

## Arranque en Windows

Terminal 1, desde `backend/`:

```powershell
php artisan config:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2, desde `frontend/`:

```powershell
npm.cmd run dev
```

Abrir **http://localhost:3000**. El origen `http://127.0.0.1:3000` es distinto y no está autorizado por el CORS local.

`frontend/.env.local`, ignorado por Git:

```dotenv
API_URL=http://127.0.0.1:8000/api/v1
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1
PRIVACY_POLICY_APPROVED=false
```

`API_URL` se usa únicamente en el servidor Next.js. `NEXT_PUBLIC_API_URL` es la dirección pública de Laravel, nunca una credencial; el navegador la necesita para enviar los formularios. Reiniciar Next.js tras cambiar estas variables; las variables públicas se incorporan durante el build. No copiar `.env` de Laravel al frontend. Se preservó `APP_KEY` y la configuración MySQL que ya funcionaba.

## Arquitectura y archivos

- `frontend/lib/api.ts`: contrato TypeScript, peticiones con timeout de 10 segundos, validación del sobre JSON y errores centralizados. No muestra mensajes internos del servidor.
- `frontend/lib/catalog.ts`: módulo exclusivo de servidor; consulta todas las páginas del catálogo, valida su estructura y comparte caché pública con revalidación de 60 segundos y expiración de 5 minutos. Una respuesta vacía no se confunde con un fallo de conexión.
- `frontend/components/treatment-cards.tsx`, `specialist-preview.tsx` y páginas de tratamientos/especialistas: datos reales de Laravel, imágenes y textos publicados; los contenidos son texto escapado, sin HTML arbitrario.
- `frontend/app/tratamientos/[slug]/page.tsx`: consulta por slug y metadatos dinámicos con el mismo origen de datos; interfaz de contenido no encontrado y `noindex` para fichas ausentes o no publicadas. No se generan slugs provisionales. Se conserva el streaming de Next.js: si la respuesta ya comenzó, Next.js mantiene HTTP 200 al mostrar esa interfaz; la API de Laravel sí devuelve HTTP 404 para el slug ausente.
- `frontend/app/sitemap.ts`: obtiene slugs publicados desde Laravel. Se conserva la protección SEO existente: dominio válido y `SEO_INDEXABLE=true` antes de permitir indexación. No se inventan fechas de actualización.
- `frontend/app/loading.tsx`, `app/error.tsx`: estados de carga y error con reintento. La portada conserva su hero, Motion, navegación y estilos; los catálogos se cargan con Suspense.
- `frontend/components/inquiry-form.tsx`, `lib/inquiries.ts`: formulario compartido, validación accesible, resumen de errores enfocable, estado de envío, traducción de campos snake_case y confirmación real del backend.
- `backend/app/Http/Middleware/EnsureInquiriesEnabled.php`, `config/clinic.php`: bloqueo efectivo de POST fuera de local/testing hasta aprobar la política de privacidad.
- `backend/database/migrations/2026_10_21_000002_add_inquiry_idempotency_keys.php`: UUID único opcional en los registros existentes; obligatorio para nuevos envíos de API.

Los POST van directamente del navegador a Laravel. Esto conserva la IP de origen para el rate limiting, sin confiar en cabeceras reenviadas por el cliente. CORS sigue limitado a `http://localhost:3000`; ahora expone `Retry-After`. Antes de desplegar en otro dominio habrá que sustituir ese origen por el dominio real, conservar una lista explícita y servir frontend y API mediante HTTPS. No se añadió una configuración permisiva de proxies.

## Endpoints

Base: `/api/v1`.

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/treatments?page=1` | Tratamientos publicados, paginación de 12 |
| GET | `/treatments/{slug}` | Ficha publicada, o 404 |
| GET | `/specialists?page=1` | Especialistas publicados, paginación de 12 |
| POST | `/valuation-requests` | Solicitud pendiente; no crea una cita |
| POST | `/contact-messages` | Mensaje pendiente |

No existen endpoints públicos de lectura de solicitudes, mensajes o citas. Los catálogos utilizan listas explícitas de campos públicos. No se añadieron páginas individuales de especialistas porque el contrato actual no dispone de slug ni endpoint individual; sus perfiles completos se muestran en `/especialistas`.

### Valoración

```json
{
  "name": "Persona ficticia",
  "email": "test@example.test",
  "phone": "+52 555 000 0000",
  "treatment_id": 1,
  "preferred_date": "2030-11-20",
  "preferred_time": "10:30",
  "consent": true,
  "website": "",
  "idempotency_key": "3ab9d38c-6d82-4c23-b961-dc516e876fe4"
}
```

El ID debe corresponder a un tratamiento publicado real. Nombre, correo, teléfono, tratamiento y consentimiento son obligatorios. Fecha y hora son preferencias opcionales; si se indica una hora se necesita fecha. El backend fija `status=pending` (pendiente) y la marca de consentimiento. Ningún envío crea ni confirma una cita.

### Contacto

```json
{
  "name": "Persona ficticia",
  "email": "test@example.test",
  "phone": null,
  "message": "Mensaje ficticio de prueba, sin información médica.",
  "consent": true,
  "website": "",
  "idempotency_key": "21f77a80-771e-4f15-8c6d-0286650fd2e9"
}
```

Teléfono opcional, mensaje obligatorio de hasta 3000 caracteres. No se piden tratamiento, fecha u horario en contacto porque el backend no los almacena en ese recurso.

### Respuestas y reintentos

Los POST devuelven 201 con `data: null` y un mensaje, sin reflejar datos personales. El frontend solo confirma después de una respuesta válida y exitosa. Los errores 422 se muestran por campo; 429 explica el límite; los fallos de transporte o 5xx permiten reintentar sin anunciar una solicitud recibida.

Durante el envío se bloquean los controles y se usa un bloqueo inmediato en memoria para evitar doble clic. Tras un éxito el formulario queda cerrado. Los reintentos del mismo contenido mientras el componente sigue montado conservan el UUID; el índice único en MySQL impide duplicados por ese UUID, incluso en solicitudes concurrentes. No hay reintentos automáticos de POST. Al recargar la página se pierde ese identificador, ya que no se almacena información personal ni claves de envío en localStorage/sessionStorage.

El campo invisible `website` debe estar vacío (honeypot). Además se mantienen 60 peticiones/minuto de API y, compartidos entre ambos formularios, 5 envíos/minuto y 20/hora por IP. El honeypot es una defensa adicional, no sustituye esos límites. Los intentos inválidos y los reintentos también cuentan.

## Credenciales profesionales

Cada entrada del JSON `specialists.credentials` puede contener `title`, `issuer`, `registration`, `verification_url` y `verified`. La API solo devuelve entradas con **`verified: true`** y excluye campos internos. Las entradas antiguas sin ese indicador no se muestran. El recurso Filament preparado incorpora un interruptor de verificación documental, inicialmente desactivado. No se realiza verificación automática ante organismos externos.

## Privacidad y producción

`/privacidad` sigue siendo un aviso provisional, actualizado para describir el almacenamiento real durante pruebas locales. No se redactó ni aprobó una política legal definitiva. Se deben usar únicamente datos ficticios en desarrollo.

Los formularios se bloquean por defecto en builds de producción de Next.js. Laravel rechaza los POST en entornos distintos de `local`/`testing` mientras `PRIVACY_POLICY_APPROVED` permanezca en `false`. Para una futura activación: publicar primero la política revisada en `/privacidad`, confirmar responsable/finalidades/retención y configurar `PRIVACY_POLICY_APPROVED=true` en ambos proyectos, con `APP_ENV=production` y `APP_DEBUG=false` en Laravel. Reconstruir Next.js y actualizar la caché de configuración Laravel. No desplegar Laravel con `APP_ENV=local`.

No se envían correos ni notificaciones. No se incluyen datos personales en logs de aplicación, consola, URLs o respuestas de éxito. Los datos del formulario y su UUID solo se mantienen en memoria durante la interacción. Las rutas administrativas conservan autenticación y autorización por rol; no se abrió ningún acceso público de administración.

## Verificación

```powershell
# backend/
php artisan migrate:status
php artisan test
# Con Laravel escuchando en 127.0.0.1:8000, solo en local:
php scripts/smoke-api.php

# frontend/
npm.cmd test
npm.cmd run lint
npm.cmd run build
```

Las pruebas Laravel usan SQLite en memoria y cubren publicación, campos, privacidad de respuestas, rate limiting, CORS, roles, consentimiento, idempotencia, honeypot, bloqueo de producción y credenciales verificadas. Las pruebas TypeScript usan el código real, compilado en memoria sin dependencias adicionales, y cubren validación, contratos, errores, confirmación y URLs seguras.

El smoke test crea únicamente registros técnicos ficticios temporales, comprueba HTTP contra Laravel y persistencia MySQL de ambos formularios, comprueba que un reintento no duplique contacto y que valoración no cree citas, y elimina exclusivamente sus propios registros en `finally`. No elimina ni altera datos anteriores. No ejecutar contra producción. Si un proceso se interrumpe abruptamente, revisar sus registros técnicos antes de repetir.

No hay automatización de navegador instalada; las verificaciones de páginas se realizan mediante respuestas HTTP y compilación. La revisión visual interactiva y la administración de Filament siguen pendientes.

Resultado de la ejecución: 14 pruebas Laravel aprobadas (108 aserciones), 8 pruebas TypeScript aprobadas, lint y build de producción correctos. El smoke test HTTP/MySQL pasó y se confirmó que no quedaron sus registros temporales. Las páginas principales respondieron HTTP 200 con los estados vacíos correspondientes; los formularios del build de producción renderizaron controles deshabilitados y el aviso de bloqueo.
