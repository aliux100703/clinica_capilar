# Clínica capilar · landing, tratamientos y SEO

El CMS de contenido público, sus módulos, archivos, endpoints, acceso y limitaciones actuales están documentados en [CMS.md](../CMS.md). Ese documento actualiza la integración de la fase anterior.

Landing provisional en español con Next.js 16.4.2, React 19.3, TypeScript estricto, Tailwind CSS 4, Motion y Lucide React. Conserva App Router en `app/`, sin `src/` ni dependencias añadidas.

## Desarrollo

Desde `frontend/`: `npm run dev`, `npm run lint`, `npm run build` y `npm start`. En PowerShell con scripts restringidos, usar `npm.cmd`.

## Estructura

- `app/page.tsx`: secciones renderizadas en servidor.
- `app/layout.tsx`: idioma y metadatos SEO, Open Graph y Twitter.
- `app/globals.css`: Tailwind, identidad, responsive y movimiento reducido.
- `components/header.tsx`: navegación móvil accesible con cierre mediante Escape.
- `components/reveal.tsx`: Motion sin ocultar contenido inicialmente; respeta movimiento reducido.
- `components/section-heading.tsx`: encabezado reutilizable.
- `lib/content.ts`: navegación y FAQ compartidas.
- `app/icon.svg`: icono provisional.

Las FAQ usan elementos nativos `details` y `summary` y funcionan sin JavaScript. Los catálogos y formularios están conectados con Laravel. Las solicitudes no confirman citas. Consulta ../INTEGRACION.md para configuración, pruebas y bloqueo de producción.

## Antes de publicar

Confirmar nombre, ciudad, dominio, contactos, datos legales y tratamientos. Sustituir los perfiles y resultados pendientes solo con contenido verificado, consentimiento cuando corresponda y revisión médica. No se incluyen médicos, certificaciones, testimonios ni resultados ficticios.

La indexación está desactivada mientras el contenido sea provisional. Los metadatos, canonical, sitemap y robots se controlan desde `lib/seo.ts` y las variables documentadas más abajo. No se generan datos estructurados de una clínica cuya identidad aún no está confirmada.

La fotografía editorial de referencia se sirve desde Unsplash mediante `next/image`, con tamaños responsive y precarga del hero. No representa un paciente ni un resultado. La optimización requiere acceso de red del servidor a `images.unsplash.com`; para producción conviene sustituirla por un recurso propio autorizado en `public/`. No se pudo descargar la imagen en este entorno con red restringida. La ilustración botánica y el icono son gráficos vectoriales de interfaz. Se usan fuentes del sistema para evitar descargas durante build.

## Páginas y contenido estructurado

- `/tratamientos`: tarjetas generadas desde `lib/treatments.ts`.
- `/tratamientos/injerto-capilar-fue`, `/tratamientos/injerto-capilar-dhi` y `/tratamientos/valoracion-capilar`: rutas dinámicas con `generateStaticParams` y `generateMetadata`. Los slugs desconocidos muestran la página 404.
- `/resultados`, `/especialistas` y `/contacto`: páginas informativas sin casos, profesionales ni contactos inventados.
- La cabecera y el footer se comparten desde el layout raíz. Breadcrumbs, FAQ, tarjetas, introducciones y paneles también son reutilizables. Las FAQ son HTML nativo; no se añade marcado `FAQPage`.

La fuente TypeScript incluye resumen, introducción, secciones, preguntas para consulta y FAQ por ficha. La disponibilidad de los tres servicios debe verificarse. No contiene recomendaciones clínicas, promesas de resultados ni comparativas de eficacia.

## Configuración SEO

Copiar `.env.example` a `.env.local` y configurar `SITE_URL` cuando se conozca el origen público definitivo (protocolo y dominio, sin rutas ni parámetros). No hay un dominio predeterminado inventado. Al configurar el dominio, los metadatos incorporan `metadataBase`, canonical y URL de Open Graph específica para cada página. Títulos, descripciones y tarjetas sociales son únicos.

`SEO_INDEXABLE=false` es el estado provisional. Solo después de verificar el contenido y la identidad se debe establecer `SEO_INDEXABLE=true`, junto con `SITE_URL`, y volver a compilar. Sin ambas condiciones se conserva `noindex, follow`, el sitemap no enumera páginas y robots no lo anuncia. `robots.ts` permite rastrear para que los buscadores puedan leer el `noindex`; no equivale a habilitar indexación.

`app/sitemap.ts` obtiene las rutas desde el mismo catálogo de tratamientos y las páginas públicas. Cuando se activa la indexación genera las URL absolutas del catálogo publicado sin fechas de modificación inventadas. `app/robots.ts` anuncia entonces la URL real del sitemap. El dominio por sí solo no habilita la indexación.

## Integración con Laravel

La configuración actual, los endpoints, los formularios reales y las restricciones de privacidad están documentados en [INTEGRACION.md](../INTEGRACION.md). Ejecutar también npm.cmd test para comprobar validaciones y comunicación.
