import 'server-only';
import { cacheTag, cacheLife } from 'next/cache';
import defaults from '@/lib/cms-defaults.json';
import { apiRequest, ApiError } from '@/lib/api';
import { pageMetadata } from '@/lib/seo';
import type { HomeContent, SiteSettings, PageCopy, CmsResult, CmsPost, CmsTestimonial, CmsFaq } from '@/lib/cms-types';

const base = () => process.env.API_URL ?? 'http://127.0.0.1:8000/api/v1';
export async function getHome(): Promise<HomeContent> {
  'use cache';
  cacheTag('cms'); cacheLife({ stale: 30, revalidate: 60, expire: 300 });
  const { data } = await apiRequest<HomeContent | null>(base(), 'home');
  return data ?? defaults.home;
}
export async function getSiteSettings(): Promise<SiteSettings> {
  'use cache';
  cacheTag('cms'); cacheLife({ stale: 30, revalidate: 60, expire: 300 });
  const { data } = await apiRequest<SiteSettings | null>(base(), 'site-settings');
  return data ?? defaults.settings;
}
export async function getLayoutSettings(): Promise<SiteSettings> {
  'use cache';
  cacheTag('cms'); cacheLife({ stale: 30, revalidate: 60, expire: 300 });
  try { return await getSiteSettings(); } catch { return defaults.settings; }
}
export async function getPageCopy(key: string): Promise<PageCopy> {
  const settings = await getSiteSettings();
  return settings.pages?.find(page => page.key === key)
    ?? defaults.settings.pages.find(page => page.key === key)
    ?? { key, title: key };
}
export async function cmsPageMetadata(key: string, path: string) {
  const [settings, page] = await Promise.all([getSiteSettings(), getPageCopy(key)]);
  return pageMetadata(`${page.title} | ${settings.trade_name}`, page.description ?? settings.seo_description ?? '', path);
}
async function listAll<T>(path: string): Promise<T[]> {
  const result: T[] = [];
  let last = 1;
  for (let page = 1; page <= last; page++) {
    const body = await apiRequest<T[]>(base(), `${path}?page=${page}`);
    if (!Array.isArray(body.data) || !body.meta || !Number.isInteger(body.meta.last_page) || body.meta.last_page < 1 || body.meta.last_page > 100) throw new ApiError(502, 'Contenido no disponible.');
    result.push(...body.data); last = body.meta.last_page;
  }
  return result;
}
export async function getResults(): Promise<CmsResult[]> {
  'use cache'; cacheTag('cms'); cacheLife({ stale: 30, revalidate: 30, expire: 300 });
  return listAll('results');
}
export async function getPosts(): Promise<CmsPost[]> {
  'use cache'; cacheTag('cms'); cacheLife({ stale: 30, revalidate: 60, expire: 300 });
  return listAll('posts');
}
export async function getTestimonials(): Promise<CmsTestimonial[]> {
  'use cache'; cacheTag('cms'); cacheLife({ stale: 30, revalidate: 30, expire: 300 });
  return listAll('testimonials');
}
export async function getFaqs(): Promise<CmsFaq[]> {
  'use cache'; cacheTag('cms'); cacheLife({ stale: 30, revalidate: 60, expire: 300 });
  return listAll('faqs');
}
export async function getPost(slug: string): Promise<CmsPost | null> {
  'use cache'; cacheTag('cms'); cacheLife({ stale: 30, revalidate: 60, expire: 300 });
  try { return (await apiRequest<CmsPost>(base(), `posts/${encodeURIComponent(slug)}`)).data; }
  catch(error) { if (error instanceof ApiError && error.status === 404) return null; throw error; }
}
