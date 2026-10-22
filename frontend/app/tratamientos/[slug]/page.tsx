import { notFound } from 'next/navigation';
import Link from 'next/link';
import { ArrowUpRight } from 'lucide-react';
import { PageIntro } from '@/components/page-intro';
import { SectionHeading } from '@/components/section-heading';
import { TreatmentCards } from '@/components/treatment-cards';
import { CatalogImage } from '@/components/catalog-image';
import { getTreatment } from '@/lib/catalog';
import { pageMetadata } from '@/lib/seo';
import { RichContent } from '@/components/rich-content';

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props) {
  const { slug } = await params;
  const treatment = await getTreatment(slug);
  if (!treatment) return { title: 'Tratamiento no encontrado', robots: { index: false, follow: false } };
  return pageMetadata(treatment.seo_title || `${treatment.name} | Clínica capilar`, treatment.seo_description || treatment.description, `/tratamientos/${encodeURIComponent(treatment.slug)}`);
}

export default async function TreatmentPage({ params }: Props) {
  const { slug } = await params;
  const treatment = await getTreatment(slug);
  if (!treatment) notFound();
  return <main id="contenido" tabIndex={-1}>
    <PageIntro title={treatment.name} eyebrow="TRATAMIENTOS" description={treatment.description} breadcrumbs={[{ label: 'Tratamientos', href: '/tratamientos' }, { label: treatment.name }]} />
    <div className="container section detail-grid">
      <article className="detail-content" aria-label={`Información sobre ${treatment.name}`}>
        <CatalogImage src={treatment.image} alt={treatment.name} />
        <h2>Conoce el tratamiento</h2>
        {treatment.content ? <RichContent html={treatment.content} /> : <p>Contenido detallado pendiente de publicación por la clínica.</p>}
        {treatment.gallery?.map((image,index)=><CatalogImage key={index} src={image} alt={`${treatment.name}, imagen ${index+1}`} />)}
      </article>
      <aside className="detail-aside"><p className="eyebrow">TU SIGUIENTE PASO</p><h2>Una conversación con claridad.</h2><p>Envía tus preferencias para solicitar una valoración. El envío no confirma una cita.</p><Link href="/agendar-valoracion" className="text-link">Solicitar valoración <ArrowUpRight size={17} aria-hidden="true" /></Link></aside>
    </div>
    <section className="container section"><SectionHeading eyebrow="SIGUE EXPLORANDO" title="Otros tratamientos." /><TreatmentCards exclude={treatment.slug} /></section>
  </main>;
}
