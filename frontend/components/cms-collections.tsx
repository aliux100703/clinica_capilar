import Link from 'next/link';
import { ScanLine } from 'lucide-react';
import { getResults, getTestimonials, getFaqs } from '@/lib/cms';
import { CatalogImage } from '@/components/catalog-image';
import { Faq } from '@/components/faq';

export async function ResultCards({ limit }: { limit?: number }) {
  const results = await getResults();
  const records = limit ? results.slice(0, limit) : results;
  if (!records.length) return <div className="results-placeholder"><ScanLine size={42} strokeWidth={1} aria-hidden="true" /><div><span className="card-tag">GALERÍA PENDIENTE</span><h3>Sin resultados publicados</h3><p>Los casos se mostrarán únicamente con autorización documentada vigente.</p></div></div>;
  return <div className="cms-results">{records.map(result => <article key={result.id} className="contact-card"><div className="cms-comparison"><figure><CatalogImage src={result.before_image} alt="Antes del tratamiento" /><figcaption>Antes</figcaption></figure><figure><CatalogImage src={result.after_image} alt="Después del tratamiento" /><figcaption>Después</figcaption></figure></div><p>{result.description}</p><p>Tiempo de evolución: {result.evolution_time}</p><small>La evolución es individual; este caso no garantiza un resultado.</small></article>)}</div>;
}
export async function TestimonialCards() {
  const testimonials = await getTestimonials();
  if (!testimonials.length) return null;
  return <section className="container section"><p className="eyebrow">TESTIMONIOS AUTORIZADOS</p><h2>Experiencias compartidas.</h2><div className="treatment-grid">{testimonials.map(item => <article className="treatment-card" key={item.id}><CatalogImage src={item.photo} alt={item.public_name} portrait /><blockquote>{item.quote}</blockquote><p>{item.public_name}</p></article>)}</div></section>;
}
export async function CmsFaqs({ category }: { category?: string }) {
  const records = (await getFaqs()).filter(item => !category || item.category === category);
  if (!records.length) return <p className="section-description">Preguntas frecuentes pendientes de publicación.</p>;
  return <Faq items={records} />;
}
export function BlogLink() { return <Link href="/blog" className="text-link">Leer el blog</Link>; }
