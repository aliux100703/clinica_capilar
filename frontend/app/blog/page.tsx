import Link from 'next/link';
import { PageIntro } from '@/components/page-intro';
import { CatalogImage } from '@/components/catalog-image';
import { CmsPageContent } from '@/components/cms-page-content';
import { getPosts, getPageCopy, cmsPageMetadata } from '@/lib/cms';
export async function generateMetadata() { return cmsPageMetadata('blog','/blog'); }
export default async function BlogPage() {
  const [posts,page] = await Promise.all([getPosts(),getPageCopy('blog')]);
  return <main id="contenido" tabIndex={-1}><PageIntro title={page.title} eyebrow={page.eyebrow || ''} description={page.description || ''} breadcrumbs={[{label:'Blog'}]} /><section className="container section">{posts.length ? <div className="treatment-grid">{posts.map(post => <article className="treatment-card" key={post.id}><CatalogImage src={post.image} alt={post.title} /><p className="card-tag">{post.category}</p><h2>{post.title}</h2><p>{post.excerpt}</p><Link className="text-link" href={`/blog/${encodeURIComponent(post.slug)}`}>Leer artículo</Link></article>)}</div> : <p className="results-placeholder">Artículos pendientes de publicación.</p>}</section><CmsPageContent page="blog" /></main>;
}
