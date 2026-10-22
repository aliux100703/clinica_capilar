import { PageIntro } from '@/components/page-intro';
import { ResultCards } from '@/components/cms-collections';
import { CmsPageContent } from '@/components/cms-page-content';
import { getPageCopy, cmsPageMetadata } from '@/lib/cms';
export async function generateMetadata() { return cmsPageMetadata('resultados', '/resultados'); }
export default async function ResultsPage() {
  const page = await getPageCopy('resultados');
  return <main id="contenido" tabIndex={-1}><PageIntro title={page.title} eyebrow={page.eyebrow || ''} description={page.description || ''} breadcrumbs={[{label:'Resultados'}]} /><section className="container section"><ResultCards /></section><CmsPageContent page="resultados" /></main>;
}
