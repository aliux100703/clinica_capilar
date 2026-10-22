import type { Metadata } from "next";

function getSiteUrl(): URL | undefined {
  const value = process.env.SITE_URL?.trim();
  if (!value) return undefined;
  const url = new URL(value);
  if (!['https:', 'http:'].includes(url.protocol) || url.username || url.password || url.pathname !== '/' || url.search || url.hash) {
    throw new Error('SITE_URL debe ser el origen público del sitio, sin rutas, credenciales ni parámetros.');
  }
  return url;
}

export const siteUrl = getSiteUrl();
// Confirmar identidad, servicios y revisión médica antes de activar esta opción.
export const isIndexable = Boolean(siteUrl) && process.env.SEO_INDEXABLE === 'true';

export function pageMetadata(title: string, description: string, path: string): Metadata {
  const canonical = siteUrl ? new URL(path, siteUrl).href : undefined;
  return {
    title,
    description,
    ...(canonical ? { alternates: { canonical } } : {}),
    robots: { index: isIndexable, follow: true },
    openGraph: { title, description, type: 'website', locale: 'es_MX', ...(canonical ? { url: canonical } : {}) },
    twitter: { card: 'summary', title, description },
  };
}
