import { notFound } from 'next/navigation';
import { PageIntro } from '@/components/page-intro';
import { CatalogImage } from '@/components/catalog-image';
import { RichContent } from '@/components/rich-content';
import { getPost } from '@/lib/cms';
import { pageMetadata } from '@/lib/seo';
type Props = {params: Promise<{slug:string}>};
export async function generateMetadata({params}:Props) {
  const post = await getPost((await params).slug);
  if(!post) return {title:'Artículo no encontrado',robots:{index:false,follow:false}};
  return pageMetadata(post.seo_title || post.title,post.seo_description || post.excerpt,`/blog/${encodeURIComponent(post.slug)}`);
}
export default async function PostPage({params}:Props) {
  const post = await getPost((await params).slug);
  if(!post) notFound();
  return <main id="contenido" tabIndex={-1}><PageIntro title={post.title} eyebrow={post.category} description={post.excerpt} breadcrumbs={[{label:'Blog',href:'/blog'},{label:post.title}]} /><article className="container section privacy-content"><CatalogImage src={post.image} alt={post.title} /><p>Por {post.author} · <time dateTime={post.published_at}>{new Intl.DateTimeFormat('es-MX',{dateStyle:'long',timeZone:'UTC'}).format(new Date(post.published_at))}</time></p>{post.medical_reviewer && <p>Revisión médica: {post.medical_reviewer.name}, {post.medical_reviewer.specialty}</p>}<RichContent html={post.content} /></article></main>;
}
