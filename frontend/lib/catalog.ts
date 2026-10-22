import 'server-only';
import { cacheLife, cacheTag } from 'next/cache';
import { apiRequest, ApiError, type Treatment, type Specialist } from '@/lib/api';

const base = () => process.env.API_URL ?? 'http://127.0.0.1:8000/api/v1';

function validTreatment(value: unknown): value is Treatment {
  if (!value || typeof value !== 'object') return false;
  const item = value as Treatment;
  return Number.isInteger(item.id) && ['name', 'slug', 'description'].every(key => typeof (item as unknown as Record<string, unknown>)[key] === 'string')
    && (item.content === null || typeof item.content === 'string') && (item.image === null || typeof item.image === 'string');
}
function validSpecialist(value: unknown): value is Specialist {
  if (!value || typeof value !== 'object') return false;
  const item = value as Specialist;
  return Number.isInteger(item.id) && typeof item.name === 'string' && typeof item.specialty === 'string' && typeof item.description === 'string'
    && (item.photo === null || typeof item.photo === 'string') && Array.isArray(item.credentials)
    && item.credentials.every(credential => credential && typeof credential === 'object'
      && ['title', 'issuer', 'registration', 'verification_url'].every(key => (credential as Record<string, unknown>)[key] === undefined || typeof (credential as Record<string, unknown>)[key] === 'string'));
}

async function listAll<T>(path: string, valid: (value: unknown) => value is T): Promise<T[]> {
  const all: T[] = [];
  let last = 1;
  for (let page = 1; page <= last; page++) {
    const response = await apiRequest<unknown[]>(base(), `${path}?page=${page}`);
    if (!Array.isArray(response.data) || !response.data.every(valid) || !response.meta || !Number.isInteger(response.meta.last_page) || response.meta.last_page < 1 || response.meta.last_page > 100) throw new ApiError(502, 'El catálogo no está disponible por el momento.');
    all.push(...response.data);
    last = response.meta.last_page;
  }
  return all;
}

export async function getTreatments(): Promise<Treatment[]> {
  'use cache';
  cacheTag('cms');
  cacheLife({ stale: 60, revalidate: 60, expire: 300 });
  return listAll('treatments', validTreatment);
}

export async function getSpecialists(): Promise<Specialist[]> {
  'use cache';
  cacheTag('cms');
  cacheLife({ stale: 60, revalidate: 60, expire: 300 });
  return listAll('specialists', validSpecialist);
}

export async function getTreatment(slug: string): Promise<Treatment | null> {
  'use cache';
  cacheTag('cms');
  cacheLife({ stale: 60, revalidate: 60, expire: 300 });
  try {
    const response = await apiRequest<unknown>(base(), `treatments/${encodeURIComponent(slug)}`);
    if (!validTreatment(response.data)) throw new ApiError(502, 'La ficha no está disponible.');
    return response.data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) return null;
    throw error;
  }
}
