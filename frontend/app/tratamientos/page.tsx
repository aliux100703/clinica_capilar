import { PageIntro } from '@/components/page-intro';
import { SectionHeading } from '@/components/section-heading';
import { TreatmentCards } from '@/components/treatment-cards';
import { getPageCopy, cmsPageMetadata } from '@/lib/cms';
import { CmsPageContent } from '@/components/cms-page-content';

export async function generateMetadata() { return cmsPageMetadata('tratamientos','/tratamientos'); }
export default async function TreatmentsPage() {
  const page=await getPageCopy('tratamientos');
  return <main id="contenido" tabIndex={-1}>
    <PageIntro title={page.title} eyebrow={page.eyebrow || ''} description={page.description || ''} breadcrumbs={[{ label: 'Tratamientos' }]} />
    <CmsPageContent page="tratamientos" />
    <section className="section container"><SectionHeading eyebrow="TRATAMIENTOS PUBLICADOS" title="Conocer antes de elegir." /><TreatmentCards /></section>
  </main>;
}
