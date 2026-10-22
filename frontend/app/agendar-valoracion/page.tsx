import { CalendarDays, ShieldCheck } from 'lucide-react';
import { PageIntro } from '@/components/page-intro';
import { InquiryForm } from '@/components/inquiry-form';
import { getTreatments } from '@/lib/catalog';
import { formsEnabled } from '@/lib/forms-config';
import { getPageCopy, cmsPageMetadata } from '@/lib/cms';
import { RichContent } from '@/components/rich-content';

export async function generateMetadata() { return cmsPageMetadata('valoracion','/agendar-valoracion'); }

export default async function AssessmentPage() {
  const page=await getPageCopy('valoracion');
  let options: { value: string; label: string }[] = [];
  let catalogError = false;
  try { options = (await getTreatments()).map(item => ({ value: String(item.id), label: item.name })); }
  catch { catalogError = true; }
  return <main id="contenido" tabIndex={-1}>
    <PageIntro title={page.title} eyebrow={page.eyebrow || ''} description={page.description || ''} breadcrumbs={[{ label: 'Solicitar valoración' }]} />
    {page.content && <section className="container section"><RichContent html={page.content} /></section>}
    <section className="container section inquiry-layout" aria-labelledby="assessment-heading"><aside className="inquiry-intro"><p className="eyebrow">UNA CONVERSACIÓN POR DELANTE</p><h2 id="assessment-heading">{page.aside_title || page.title}</h2><p className="section-description">{page.aside_description || page.description}</p><div className="inquiry-principle"><CalendarDays size={23} aria-hidden="true" /><p>Tu fecha y horario son orientativos. No se bloquean espacios en una agenda.</p></div><div className="inquiry-principle"><ShieldCheck size={23} aria-hidden="true" /><p>Solo datos de contacto y preferencias. No solicitamos fotografías ni historial médico.</p></div></aside><InquiryForm options={options} enabled={formsEnabled()} catalogError={catalogError} /></section>
  </main>;
}
