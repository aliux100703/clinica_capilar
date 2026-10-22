import { UserRound } from 'lucide-react';
import { PageIntro } from '@/components/page-intro';
import { CatalogImage } from '@/components/catalog-image';
import { getSpecialists } from '@/lib/catalog';
import { safeMediaUrl } from '@/lib/api';
import { getPageCopy, cmsPageMetadata } from '@/lib/cms';
import { CmsPageContent } from '@/components/cms-page-content';

export async function generateMetadata() { return cmsPageMetadata('especialistas','/especialistas'); }
export default async function SpecialistsPage() {
  const specialists = await getSpecialists();
  const page=await getPageCopy('especialistas');
  return <main id="contenido" tabIndex={-1}>
    <PageIntro title={page.title} eyebrow={page.eyebrow || ''} description={page.description || ''} breadcrumbs={[{ label: 'Especialistas' }]} />
    <CmsPageContent page="especialistas" />
    <section className="container section">
      {!specialists.length ? <div className="team-placeholder"><UserRound size={55} strokeWidth={1} aria-hidden="true" /><h2>Equipo pendiente de publicación</h2><p>La clínica aún no ha publicado perfiles de especialistas.</p><span className="provisional">Contenido pendiente</span></div>
        : <div className="treatment-grid">{specialists.map(person => <article key={person.id} className="treatment-card">
          <CatalogImage src={person.photo} alt={person.name} portrait />
          <p className="card-tag">{person.specialty}</p><h2>{person.name}</h2><p className="catalog-content">{person.description}</p>
          {person.credentials.length > 0 && <section><h3>Credenciales verificadas</h3><ul>{person.credentials.map((credential, index) => {
            const verification = safeMediaUrl(credential.verification_url);
            return <li key={index}><strong>{credential.title}</strong>{credential.issuer && <p>{credential.issuer}</p>}{credential.registration && <p>Registro: {credential.registration}</p>}{verification && <a className="text-link" href={verification} target="_blank" rel="noopener noreferrer">Consultar acreditación<span className="sr-only"> (abre otra pestaña)</span></a>}</li>;
          })}</ul></section>}
        </article>)}</div>}
    </section>
  </main>;
}
