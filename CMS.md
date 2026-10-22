# CMS de contenido público — fase 6

## Estado de la entrega

La base de datos, la API de contenido, las reglas de publicación, los recursos Filament y la integración de Next.js están implementados. Las dos migraciones nuevas están ejecutadas en `clinica_capilar`, MariaDB 10.4.32 de XAMPP en `127.0.0.1:3307`.

**El panel está operativo en http://127.0.0.1:8000/admin.** Filament 5.10.3 está instalado, el autoload y los assets están generados y los requisitos de plataforma pasan con PHP 8.2.12. La pantalla de acceso responde HTTP 200 y ya existe una cuenta con rol administrador. Utiliza las credenciales introducidas en el iniciador; no se cambiaron ni se generaron credenciales predeterminadas. Se comprobaron los ocho formularios mediante Livewire y el flujo de creación, edición, publicación y despublicación de preguntas frecuentes.

No se eliminaron tablas o datos anteriores. No se ejecutaron `migrate:fresh`, `db:wipe` ni rollbacks. El panel registra exclusivamente recursos de contenido: las clases antiguas de solicitudes, mensajes y citas se conservan en disco, pero no se registran en `/admin`. Sus tablas y los formularios previamente integrados permanecen intactos. No se añadieron CRM, pacientes, expedientes, agendas o administración de solicitudes.

## Módulos disponibles

| Módulo | Campos y comportamiento |
| --- | --- |
| Página de inicio | Título, subtítulo, portada, texto alternativo, botones/enlaces, presentación, secciones destacadas ordenables, elementos por sección, visibilidad, otros textos de portada y SEO. |
| Tratamientos | Nombre, slug único, descripción corta, editor enriquecido, imagen, galería, orden, borrador/publicado y SEO. |
| Especialistas | Nombre, especialidad, fotografía, biografía, credenciales verificables, orden y estado. Solo se exponen credenciales marcadas como verificadas. |
| Resultados | Antes/después, tratamiento publicado asociado, descripción, evolución, orden, estado y consentimiento documentado privado. |
| Blog | Título, slug, extracto, editor, imagen, categoría, autor público, revisor médico, fecha de revisión/publicación, estado y SEO. |
| Testimonios | Nombre público autorizado, texto, foto opcional, orden, estado y consentimiento documentado privado. |
| Preguntas frecuentes | Pregunta, respuesta, categoría, orden y estado. |
| Configuración general | Nombre comercial, leyenda de marca, logo, teléfono, WhatsApp, correo, dirección, redes, horarios informativos, contacto, navegación, pie, textos de páginas, botón del encabezado y SEO general. |

Inicio y configuración son registros únicos. `clinic:initialize-cms` creó únicamente sus borradores con la presentación provisional que ya existía; repetirlo no sobrescribe cambios. No se sembraron tratamientos, profesionales, resultados, testimonios o artículos ficticios como contenido real. Cuando no existe una versión publicada de inicio/configuración, Next.js conserva la presentación provisional original. Los catálogos vacíos muestran estados pendientes, sin inventar información médica.

En inicio, las secciones reconocidas son `principles`, `treatments`, `approach`, `process`, `specialists`, `results`, `faqs` y `consultation`. El administrador puede editar sus títulos, textos, elementos y botones, reordenarlas y desactivar su visibilidad sin alterar la composición visual del sitio.

Los campos clave/texto permiten editar leyendas editoriales de portada (`hero_eyebrow`, `hero_note`, `image_caption`, `portrait_label`, `vertical_label`, `hero_bottom`, `hero_explore`, `hero_step`, `botanical_label`, `consultation_note`). En configuración, `interface_copy` admite `header_cta_label` y `header_cta_url`. Los textos de las páginas de tratamientos, especialistas, resultados, blog, contacto, valoración y privacidad se editan en el repetidor «Textos de páginas». Los rótulos funcionales de controles, estados de error y validación siguen siendo parte de la aplicación.

## Instalar y entrar al CMS

En Windows también puedes abrir **`backend/iniciar-admin.cmd`** con doble clic, o ejecutarlo desde una terminal. Comprueba si Filament está instalado, lo instala cuando falta, aplica únicamente migraciones pendientes, conserva los borradores y administradores existentes, solicita credenciales si no hay administrador, verifica `admin/login` e inicia Laravel en el puerto 8000. Debe ejecutarse desde una sesión con acceso a Internet para completar la instalación inicial. Si falla una fase, se detiene sin anunciar el panel como disponible.

Desde `backend/`, en una terminal con acceso a Packagist:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\install-admin.ps1
php artisan migrate
php artisan clinic:initialize-cms
php artisan clinic:create-admin
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\start-cms.ps1
```

El parámetro de PowerShell permite ejecutar únicamente ese proceso; no modifica la política persistente del equipo. El helper de PHP habilita GD, intl y zip mediante un archivo adicional en `backend/storage/framework/cms-php`, dentro del proyecto y excluido del repositorio. No modifica `C:/xampp/php/php.ini`. Se verificó que las tres extensiones están disponibles en la instalación local. El servidor iniciado con `start-cms.ps1` y sus procesos heredan esa configuración.

El instalador conserva el proveedor ya preparado, publica assets y limpia cachés; **no ejecutar `filament:install --scaffold`** sobre el proyecto. Si Composer informa de otro requisito de plataforma, resolverlo antes de continuar, sin `--ignore-platform-reqs`.

Acceso actual: abrir **http://127.0.0.1:8000/admin** y usar la cuenta creada en el iniciador. Mantén abierta la terminal que ejecuta Laravel. El procedimiento manual anterior sirve para una instalación nueva; no es necesario crear otra cuenta. `clinic:create-admin` solicita nombre, correo y contraseña oculta de al menos 12 caracteres con confirmación. Crea una cuenta nueva, no modifica usuarios existentes ni envía correos. No hay credenciales predeterminadas. La autenticación usa `AdminUser` sobre `users`, rol `admin`; las cuentas normales no pueden entrar. `role` no es asignable masivamente.

El panel aplica sesión, CSRF, autenticación, comprobación persistente del rol en Livewire y políticas por recurso. No existe registro público de administradores. Se permite crear/editar y despublicar contenido; las políticas no permiten borrados administrativos ni eliminaciones de archivos compartidos.

## API pública

Se mantuvo la versión existente **`/api/v1`**; no se crearon alias duplicados `/api/...`.

| Método | Endpoint | Datos públicos |
| --- | --- | --- |
| GET | `/api/v1/site-settings` | Configuración publicada o `data: null` |
| GET | `/api/v1/home` | Inicio publicado o `data: null` |
| GET | `/api/v1/treatments` | Tratamientos publicados, por orden e ID |
| GET | `/api/v1/treatments/{slug}` | Tratamiento publicado, o HTTP 404 |
| GET | `/api/v1/specialists` | Especialistas publicados, por orden e ID |
| GET | `/api/v1/results` | Resultados publicados con tratamiento y consentimiento vigentes |
| GET | `/api/v1/posts` | Artículos publicados cuya fecha ya llegó y revisión requerida está completada |
| GET | `/api/v1/posts/{slug}` | Artículo disponible, o HTTP 404 |
| GET | `/api/v1/testimonials` | Testimonios publicados con consentimiento vigente |
| GET | `/api/v1/faqs` | Preguntas publicadas, por orden e ID |
| GET | `/api/v1/media/{uuid}` | Imagen únicamente si tiene una asociación pública autorizada |

Las listas conservan paginación de 12 y el sobre `{ data, message, meta }`. Next.js recorre sus páginas. Las respuestas usan listas explícitas de campos y excluyen estado interno, documentos, fechas de consentimiento y rutas de almacenamiento. Las fechas públicas de blog corresponden a publicación; los nombres de autor, revisor y testimonio son los autorizados para publicación.

Se mantienen los POST anteriores de contacto y valoración, sin ampliar sus funciones ni incorporarlos al panel. El límite de lectura se ajustó a 120 peticiones/minuto por IP para las consultas SSR de contenido; los límites de envío siguen separados (5/minuto y 20/hora). CORS continúa limitado a `http://localhost:3000`.

## Publicación y revisión

- Los borradores nunca aparecen en consultas públicas. Los catálogos no incluyen columnas privadas ni publican registros futuros por tener únicamente `status=published`.
- Resultados y testimonios requieren consentimiento activado, documento existente en almacenamiento privado, fecha de autorización no futura y vencimiento vigente cuando se indique. Retirar el consentimiento devuelve el registro a borrador. Si falta el documento o se despublica el tratamiento asociado, los resultados dejan de mostrarse.
- Los artículos requieren revisión médica por defecto. Para contenido no médico el administrador puede desactivar ese requisito. Cuando se requiere, deben existir especialista publicado y fecha de revisión completada. Cambiar título, extracto o contenido sin una nueva fecha de revisión devuelve el artículo a borrador e invalida la aprobación anterior.
- Una fecha futura programa la publicación del blog; la consulta pública la respeta. La caché se refresca por tiempo cuando llega esa fecha, sin publicar antes.
- El sistema verifica las condiciones documentales registradas, no la autenticidad jurídica o médica de documentos y acreditaciones. La comprobación real corresponde al responsable autorizado antes de marcar y publicar.

## Imágenes y archivos privados

`StoreCmsImage` valida contenido MIME JPEG/PNG/WebP, máximo 5 MB, 12 megapíxeles y 6000 píxeles por lado. Normaliza orientación JPEG, limita el lado mayor a 1920 píxeles y convierte a WebP de calidad 82, eliminando metadatos del original. Los nombres son UUID generados en servidor y las carpetas corresponden a inicio, configuración, tratamientos, especialistas, resultados, blog y testimonios. SVG y archivos ejecutables no se aceptan.

Las imágenes se guardan en `storage/app/cms-media`, fuera de `public/`. No se necesita `storage:link` para el CMS. La API de imágenes comprueba publicación y consentimiento en cada petición y responde `Cache-Control: private, no-store`. Las imágenes de resultados/testimonios conservan ese requisito aunque se reutilicen en un tratamiento u otro contenido. Se usan imágenes nativas en Next.js para no introducir una caché de optimización que eluda esa revocación; la portada CMS carga con prioridad y el resto con lazy loading.

Los consentimientos se guardan en `storage/app/cms-private/consents`; PDF/JPEG/PNG de hasta 5 MB, nombres aleatorios y sin URL pública. Se descargan únicamente mediante `/admin/consents/{results|testimonials}/{id}` con sesión y rol de administrador. `/admin/media/{uuid}` sirve previsualizaciones privadas bajo el mismo control.

Retirar una imagen de un formulario no elimina físicamente el archivo. Se evita así borrar archivos usados en otros registros. La limpieza de huérfanos y la política de retención quedan pendientes de una decisión operativa; no se añadió una tarea destructiva. Los registros de prueba y sus archivos usan almacenamiento simulado o se eliminan exclusivamente por su identificador técnico recién creado.

## Integración y revalidación de Next.js

Las páginas existentes conservan SSR, estilos, tipografías, navegación, Motion y responsive. Se añadieron `/blog` y `/blog/[slug]` con las mismas tarjetas y composición. Inicio utiliza la configuración y secciones del CMS; tratamientos, especialistas, resultados, testimonios y FAQ consultan sus endpoints. Encabezado, pie, contacto, textos de páginas y privacidad usan configuración publicada. Los tratamientos y artículos tienen metadatos SEO editables y el sitemap obtiene sus slugs publicados.

`SITE_URL` y `SEO_INDEXABLE` mantienen el control de indexación fuera del CMS: no se habilitó indexación mientras la identidad y el contenido siguen pendientes. `/privacidad` conserva `noindex`. Las fichas ausentes llaman a `notFound()`; con streaming iniciado, Next.js puede conservar HTTP 200 mientras muestra esa interfaz e inyecta `noindex`. Laravel devuelve HTTP 404 para el slug ausente.

Se generó un secreto aleatorio compartido exclusivamente en `.env` de Laravel y `.env.local` del servidor Next.js, ambos ignorados. No se mostró ni se incluyó en variables `NEXT_PUBLIC_*`.

```dotenv
# backend/.env
CMS_REVALIDATE_URL=http://127.0.0.1:3000/api/revalidate
CMS_REVALIDATE_SECRET=SECRETO_ALEATORIO_DE_32_O_MAS_CARACTERES

# frontend/.env.local — exclusivamente servidor
CMS_REVALIDATE_SECRET=MISMO_SECRETO
```

El observer se ejecuta después de confirmar cambios. Envía únicamente `{ tag: "cms" }` al webhook de Next.js con un bearer token. El endpoint compara el secreto en tiempo constante, expira la etiqueta `cms` y revalida el layout; la siguiente visita obtiene el contenido actualizado sin recompilar. No se envían datos privados, correos o notificaciones externas.

Si Next.js no está disponible, el cambio de contenido no se pierde: queda una marca de revalidación pendiente. `clinic:revalidate-content` permite reintentar y hay una tarea cada minuto para instalaciones con scheduler activo. En desarrollo se puede ejecutar `php artisan schedule:work`; en despliegue se debe configurar el scheduler y las URL reales. Las consultas conservan refresco por tiempo (30–60 segundos bajo tráfico, expiración de servidor de 5 minutos) como respaldo. La navegación de una pestaña puede conservar contenido prefetched durante su intervalo cliente; una página ya abierta no recibe actualizaciones en tiempo real. Las imágenes vuelven a validar autorización independientemente de esas cachés.

Los formularios conservan el bloqueo de producción hasta publicar una política de privacidad válida y aprobarla expresamente mediante `PRIVACY_POLICY_APPROVED` en ambos servidores. Publicar una página de privacidad en el CMS no activa por sí solo el envío de datos.

## Archivos principales

| Área | Archivos nuevos o modificados |
| --- | --- |
| Esquema | `backend/database/migrations/2026_10_21_000003_create_public_cms.php`, `2026_10_21_000004_add_site_interface_copy.php` |
| Modelos | `backend/app/Models/{CmsModel,HomePage,SiteSetting,Result,Post,Testimonial,Faq,MediaAsset}.php`, `Concerns/HasPublicContent.php`; adaptados `Treatment.php` y `Specialist.php` |
| Panel | `backend/app/Filament/Cms/CmsResource.php`, `CmsFields.php`, ocho `*Resource.php` y sus páginas en `Cms/Pages/`; `AdminPanelProvider.php` registra exclusivamente estos recursos |
| Validación y salida | `backend/app/Support/{CmsDefinition,CmsValidation,CmsPublicData,SafeHtml,PublicMedia}.php` |
| Imágenes y caché | `backend/app/Services/{StoreCmsImage,RevalidateContent}.php`, `Observers/ContentObserver.php`, `config/cms.php`, `config/filesystems.php` |
| API | `backend/app/Http/Controllers/Api/CmsContentController.php`, `CmsMediaController.php`; adaptados controladores de tratamientos/especialistas y `routes/{api,web,console}.php` |
| Preparación | Comandos `InitializeCms.php`, `RevalidateCms.php`; scripts `enable-cms-php.ps1`, `start-cms.ps1`, `install-admin.ps1`, `smoke-cms.php` |
| Tipos y datos SSR | `frontend/lib/{cms,cms-types,cms-links}.ts`, `cms-defaults.json`; adaptados `api.ts` y `catalog.ts` |
| Presentación | `frontend/components/{cms-collections,cms-page-content,cms-link,rich-content}.tsx`; adaptados encabezado, pie, imágenes y páginas públicas |
| Nuevas rutas Next | `frontend/app/blog/page.tsx`, `blog/[slug]/page.tsx`, `api/revalidate/route.ts` |
| SEO y diseño | `frontend/app/layout.tsx`, `page.tsx`, `sitemap.ts`, páginas de contenido y `globals.css`; sin sustituir la identidad visual |
| Pruebas | `backend/tests/Feature/CmsContentTest.php`, `CmsPanelTest.php`, actualización de `ClinicApiTest.php`, `frontend/tests/integration.test.mjs` |
| Variables | `.env.example` de ambos proyectos; los archivos locales con secretos permanecen excluidos |

Los documentos `INTEGRACION.md` anteriores describen la fase previa; este archivo es la referencia para el CMS y sus cambios de API/caché.

## Pruebas realizadas

```powershell
# backend/
php -d extension=gd -d extension=intl -d extension=zip vendor/phpunit/phpunit/phpunit
php artisan migrate:status

# frontend/
npm.cmd test
npm.cmd run lint
npm.cmd run build
```

Resultado: **30 pruebas Laravel, 330 aserciones**, incluidas sanitización, publicación/edición/despublicación, calendario editorial del blog, revisión médica, roles, consentimientos, privacidad de API, carga/optimización de imágenes, archivos compartidos, revalidación y conservación de las credenciales administrativas durante el arranque. Con Filament instalado se verificaron los ocho formularios y la creación, edición, publicación y despublicación de una FAQ mediante Livewire. **12 pruebas TypeScript**, incluidos contratos, errores, enlaces seguros y autenticación del webhook. Lint, TypeScript y build de Next.js completados.

Prueba HTTP contra MySQL y Next.js compilado, usando únicamente FAQ y artículo técnicos temporales:

```powershell
# backend ejecutándose en 8000; terminal frontend/
npm.cmd start -- --port=3100 --hostname=127.0.0.1
# otra terminal backend/
php scripts/smoke-cms.php --frontend-url=http://127.0.0.1:3100
```

Se comprobó rechazo del webhook sin token, publicación de FAQ en Next.js, edición sin rebuild, desaparición tras despublicar, metadatos dinámicos del artículo y noindex/contenido no encontrado al retirarlo. Se corrigió una segunda caché de `fetch` que podía conservar contenido antiguo: las peticiones de `apiRequest` usan `no-store` y la caché editorial queda a cargo de las funciones CMS con etiqueta `cms`. En desarrollo, Next.js también almacenaba estas respuestas en la caché de HMR aunque usaran `no-store`; se desactivó mediante `experimental.serverComponentsHmrCache: false` en `frontend/next.config.ts`. La comprobación HTTP completa pasó después de corregirlo. También pasan las pruebas de carga real de imágenes desde Livewire, conservación de la imagen al editar/publicar y rechazo de invitados y usuarios sin rol administrador en el panel y las imágenes privadas. El script comprueba por defecto la web real del puerto 3000. Para comprobar el servidor compilado del puerto 3100, usa `--frontend-url=http://127.0.0.1:3100`, como en el ejemplo. Solo admite localhost/127.0.0.1 y esos dos puertos; no modifica la URL guardada en `.env` y elimina exclusivamente sus propios registros temporales en `finally`.

## Pendiente

1. Revisar visualmente en navegador el panel, la carga/previsualización de imágenes y documentos y el sitio con contenido real publicado, incluyendo móvil. Las pruebas de Livewire y de almacenamiento no sustituyen esa revisión visual; no hay automatización de navegador disponible en este entorno.
2. Publicar únicamente contenido, acreditaciones y consentimientos reales verificados; completar identidad, dirección, política de privacidad y revisión editorial/médica. Los borradores iniciales permanecen sin publicar.
3. En despliegue: URL y secreto de webhook, scheduler, HTTPS, origen CORS real, `APP_ENV=production`, `APP_DEBUG=false`, copias de seguridad de base y ambos discos privados. No se realizó despliegue ni activación de formularios de producción.
