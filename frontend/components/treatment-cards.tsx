import Link from 'next/link';
import { ArrowUpRight, Leaf } from 'lucide-react';
import { Reveal } from '@/components/reveal';
import { CatalogImage } from '@/components/catalog-image';
import { getTreatments } from '@/lib/catalog';

export async function TreatmentCards({ exclude }: { exclude?: string }) {
  const treatments = (await getTreatments()).filter(item => item.slug !== exclude);
  if (!treatments.length) return <div className="results-placeholder"><p>Contenido pendiente de publicación. La clínica aún no ha publicado tratamientos en su catálogo.</p></div>;
  return <div className="treatment-grid">{treatments.map((item, index) =>
    <Reveal key={item.id}><article className="treatment-card">
      <div className="card-top"><Leaf size={30} strokeWidth={1.2} aria-hidden="true" /><span>{String(index + 1).padStart(2, '0')}</span></div>
      <CatalogImage src={item.image} alt={item.name} />
      <p className="card-tag">TRATAMIENTO</p><h3>{item.name}</h3><p>{item.description}</p>
      <Link href={`/tratamientos/${encodeURIComponent(item.slug)}`} className="card-link">Conocer el tratamiento <ArrowUpRight size={19} aria-hidden="true" /><span className="sr-only"> de {item.name}</span></Link>
    </article></Reveal>
  )}</div>;
}
