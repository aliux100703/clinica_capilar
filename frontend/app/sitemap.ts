import type { MetadataRoute } from 'next';
import { isIndexable, siteUrl } from '@/lib/seo';
import { getTreatments } from '@/lib/catalog';
import { getPosts } from '@/lib/cms';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  if (!siteUrl || !isIndexable) return [];
  const [treatments,posts] = await Promise.all([getTreatments(),getPosts()]);
  const paths = ['/', '/tratamientos', ...treatments.map(item => `/tratamientos/${encodeURIComponent(item.slug)}`), '/resultados', '/especialistas', '/blog', ...posts.map(item=>`/blog/${encodeURIComponent(item.slug)}`), '/contacto', '/agendar-valoracion'];
  return paths.map(path => ({ url: new URL(path, siteUrl).href }));
}
