export type ApiEnvelope<T> = { data: T; message: string; meta?: { current_page: number; last_page: number; total: number } };
export type Treatment = { id: number; name: string; slug: string; description: string; content: string | null; image: string | null; gallery?: string[]; seo_title?: string; seo_description?: string };
export type Credential = { title?: string; issuer?: string; registration?: string; verification_url?: string };
export type Specialist = { id: number; name: string; specialty: string; description: string; photo: string | null; credentials: Credential[] };

export class ApiError extends Error {
  constructor(public status: number, message: string, public fields: Record<string, string[]> = {}, public retryAfter?: string) { super(message); this.name = 'ApiError'; }
}

export function apiBase(value: string | undefined): string {
  if (!value) throw new ApiError(503, 'El servicio todavía no está configurado.');
  let url: URL;
  try { url = new URL(value); } catch { throw new ApiError(503, 'El servicio todavía no está configurado.'); }
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password || url.search || url.hash) throw new ApiError(503, 'Configuración del servicio no válida.');
  return url.href.replace(/\/$/, '');
}

export async function apiRequest<T>(base: string | undefined, path: string, init?: RequestInit): Promise<ApiEnvelope<T>> {
  try {
    const response = await fetch(`${apiBase(base)}/${path}`, {
      // CMS functions own the tagged cache; a separate fetch cache can retain old content.
      cache: 'no-store',
      ...init, headers: { Accept: 'application/json', ...(init?.body ? { 'Content-Type': 'application/json' } : {}), ...init?.headers },
      signal: AbortSignal.timeout(10000),
    });
    const json: unknown = await response.json().catch(() => null);
    const body = json && typeof json === 'object' ? json as Record<string, unknown> : {};
    if (!response.ok) {
      const fields: Record<string, string[]> = {};
      if (body.errors && typeof body.errors === 'object') {
        for (const [key, value] of Object.entries(body.errors)) {
          if (Array.isArray(value)) fields[key] = value.filter((item): item is string => typeof item === 'string');
        }
      }
      throw new ApiError(response.status, response.status === 422 ? 'Revisa los campos indicados.'
        : response.status === 429 ? 'Has enviado varias solicitudes. Espera antes de intentarlo de nuevo.'
        : response.status === 503 ? 'El servicio de formularios no está disponible por el momento.'
        : response.status === 404 ? 'Contenido no encontrado.'
        : 'No se pudo completar la solicitud. Inténtalo más tarde.', fields, response.headers.get('Retry-After') ?? undefined);
    }
    if (!('data' in body) || typeof body.message !== 'string') throw new ApiError(502, 'La respuesta del servicio no es válida.');
    return body as ApiEnvelope<T>;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    throw new ApiError(503, 'No pudimos conectar con la clínica. No hay confirmación del envío; puedes reintentar sin duplicar tu solicitud.');
  }
}

export function safeMediaUrl(value: string | null | undefined): string | undefined {
  if (!value) return undefined;
  if (value.startsWith('/') && !value.startsWith('//') && !value.includes('\\')) return value;
  try {
    const url = new URL(value);
    return ['https:', 'http:'].includes(url.protocol) && !url.username && !url.password ? url.href : undefined;
  } catch { return undefined; }
}
