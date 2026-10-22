import { safeMediaUrl } from '@/lib/api';

// Native lazy images avoid allowing arbitrary admin-provided hosts in the server image optimizer.
export function CatalogImage({ src, alt, portrait = false, priority = false }: { src: string | null; alt: string; portrait?: boolean; priority?: boolean }) {
  const url = safeMediaUrl(src);
  if (!url) return null;
  // eslint-disable-next-line @next/next/no-img-element
  return <img src={url} alt={alt} width={portrait ? 480 : 960} height={portrait ? 600 : 540} loading={priority ? 'eager' : 'lazy'} fetchPriority={priority ? 'high' : 'auto'} decoding="async" referrerPolicy="no-referrer" className={portrait ? 'catalog-image catalog-portrait' : 'catalog-image'} />;
}
