import { PageIntro } from '@/components/page-intro';
import { RichContent } from '@/components/rich-content';
import { getPageCopy, cmsPageMetadata } from '@/lib/cms';
export async function generateMetadata() { return {...await cmsPageMetadata('privacidad','/privacidad'),robots:{index:false,follow:true}}; }
export default async function PrivacyPage() {
  const page=await getPageCopy('privacidad');
  return <main id="contenido" tabIndex={-1}><PageIntro title={page.title} eyebrow={page.eyebrow || ''} description={page.description || ''} breadcrumbs={[{label:'Privacidad'}]} /><article className="container section privacy-content"><RichContent html={page.content} /></article></main>;
}
