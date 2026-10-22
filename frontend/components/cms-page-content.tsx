import { getPageCopy } from '@/lib/cms';
import { RichContent } from '@/components/rich-content';
export async function CmsPageContent({ page }: { page: string }) {
  const data = await getPageCopy(page);
  return data.content ? <section className="container section"><RichContent html={data.content} /></section> : null;
}
