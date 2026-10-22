import { timingSafeEqual } from 'node:crypto';
import { revalidateTag, revalidatePath } from 'next/cache';

export async function POST(request: Request) {
  const secret = process.env.CMS_REVALIDATE_SECRET;
  const provided = request.headers.get('authorization') ?? '';
  if (!secret || secret.length < 32) return Response.json({ message: 'Revalidación no configurada.' }, { status: 503 });
  const actual = Buffer.from(provided);
  const expected = Buffer.from(`Bearer ${secret}`);
  if (actual.length !== expected.length || !timingSafeEqual(actual, expected)) return Response.json({ message: 'No autorizado.' }, { status: 401 });
  revalidateTag('cms', { expire: 0 });
  revalidatePath('/', 'layout');
  return Response.json({ revalidated: true });
}
