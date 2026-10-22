import { Breadcrumbs } from '@/components/breadcrumbs';
import { Reveal } from '@/components/reveal';

export function PageIntro({ title, eyebrow, description, breadcrumbs }: {
  title: string; eyebrow: string; description: string;
  breadcrumbs: { label: string; href?: string }[];
}) {
  return <section className="inner-hero"><div className="container">
    <Breadcrumbs items={breadcrumbs} />
    <Reveal><p className="eyebrow">{eyebrow}</p><h1>{title}</h1><p className="inner-description">{description}</p>
      <span className="inner-status">Presentación provisional · Información pendiente de verificar</span>
    </Reveal>
  </div></section>;
}
