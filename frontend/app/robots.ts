import type { MetadataRoute } from 'next';
import { isIndexable, siteUrl } from '@/lib/seo';

export default function robots(): MetadataRoute.Robots {
  // Permitir el rastreo para que los buscadores puedan leer el noindex del HTML.
  return {
    rules: { userAgent: '*', allow: '/' },
    ...(isIndexable && siteUrl ? { sitemap: new URL('/sitemap.xml', siteUrl).href } : {}),
  };
}
