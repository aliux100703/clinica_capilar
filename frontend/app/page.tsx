import Image from 'next/image';
import { ArrowDown, Check, Fingerprint, Flower2, HeartHandshake, ShieldCheck, Sparkles } from 'lucide-react';
import { Suspense } from 'react';
import { SpecialistPreview } from '@/components/specialist-preview';
import { TreatmentCards } from '@/components/treatment-cards';
import { Reveal } from '@/components/reveal';
import { SectionHeading } from '@/components/section-heading';
import { CmsLink } from '@/components/cms-link';
import { CatalogImage } from '@/components/catalog-image';
import { ResultCards, TestimonialCards, CmsFaqs } from '@/components/cms-collections';
import { getHome } from '@/lib/cms';
import { safeMediaUrl } from '@/lib/api';
import { pageMetadata } from '@/lib/seo';
import type { CmsSection } from '@/lib/cms-types';

export async function generateMetadata() {
  const home = await getHome();
  return pageMetadata(home.seo_title || home.title.replace(/\n/g, ' '), home.seo_description || home.subtitle || '', '/');
}
function Title({ text }: { text?: string }) {
  const lines = (text || '').split('\n');
  return <>{lines.map((line, index) => <span key={index}>{index > 0 && <br />}{index === lines.length - 1 && index > 0 ? <em>{line}</em> : line}</span>)}</>;
}
const loading = <p role="status">Cargando contenido…</p>;
function Buttons({ section }: { section: CmsSection }) { return <>{section.buttons?.map((button, index) => <CmsLink key={index} button={button} />)}</>; }

export default async function Home() {
  const home = await getHome();
  const copy = home.copy || {};
  const cover = safeMediaUrl(home.cover_image);
  const principles = [Fingerprint, HeartHandshake, ShieldCheck];
  return <main id="contenido" tabIndex={-1}>
    <section id="inicio" className="hero" aria-labelledby="hero-title">
      <div className="hero-orbit" aria-hidden="true" /><div className="container hero-grid">
        <Reveal className="hero-copy"><p className="eyebrow"><span className="eyebrow-line" />{copy.hero_eyebrow}</p><h1 id="hero-title"><Title text={home.title} /></h1><p className="hero-description">{home.subtitle}</p>
          <div className="hero-actions">{home.buttons?.map((button,index) => <CmsLink key={index} button={button} className={index === 0 ? 'button' : 'text-link light'} />)}</div>
          <div className="hero-note"><ShieldCheck size={19} aria-hidden="true" /><span>{home.presentation}<br /><small>{copy.hero_note}</small></span></div>
        </Reveal>
        <div className="hero-visual"><div className="portrait-frame">
          {cover && (cover.startsWith('https://images.unsplash.com/photo-1506794778202-cad84cf45f1d') ? <Image src={cover} alt={home.image_alt || ''} fill sizes="(max-width: 760px) 100vw, 50vw" preload className="hero-image" /> : <div className="cms-hero-image"><CatalogImage src={cover} alt={home.image_alt || ''} portrait priority /></div>)}
          <div className="portrait-shade" /><span className="image-caption">{copy.image_caption}</span></div>
          <div className="portrait-label"><span className="small-star" aria-hidden="true">✳</span><div className="catalog-content">{copy.portrait_label}</div></div><span className="vertical-label" aria-hidden="true">{copy.vertical_label}</span>
        </div>
      </div><div className="container hero-bottom"><span>{copy.hero_bottom}</span><a href="#tratamientos">{copy.hero_explore}<ArrowDown size={15} aria-hidden="true" /></a><span>{copy.hero_step}</span></div>
    </section>
    {home.sections?.filter(section => section.enabled !== false).map(section => {
      switch(section.key) {
        case 'principles': return <div className="principles-strip" key={section.key}><div className="container principles-inner">{section.items?.map((item,index) => { const Icon = principles[index % principles.length]; return <span key={index}><Icon size={20} strokeWidth={1.4} aria-hidden="true" />{item.title}</span>; })}</div></div>;
        case 'treatments': return <section id="tratamientos" key={section.key} className="section container"><Reveal><div className="section-top"><div><p className="eyebrow">{section.eyebrow}</p><h2><Title text={section.title} /></h2></div><p className="section-aside catalog-content">{section.description}</p></div></Reveal><Suspense fallback={loading}><TreatmentCards /></Suspense><Buttons section={section} /></section>;
        case 'approach': return <section id="enfoque" key={section.key} className="approach section"><div className="container approach-grid"><div className="botanical" aria-hidden="true"><div className="botanical-circle" /><Flower2 strokeWidth={0.45} /><span>{copy.botanical_label}</span></div><Reveal><p className="eyebrow">{section.eyebrow}</p><h2><Title text={section.title} /></h2><p className="section-description">{section.description}</p><div className="benefit-list">{section.items?.map((item,index) => <div key={index}><Check size={17} aria-hidden="true" /><div><h3>{item.title}</h3><p>{item.text}</p></div></div>)}</div><Buttons section={section} /></Reveal></div></section>;
        case 'process': return <section id="proceso" key={section.key} className="section container"><Reveal><SectionHeading eyebrow={section.eyebrow || ''} title={section.title || ''} text={section.description} /></Reveal><div className="process-grid">{section.items?.map((item,index) => <Reveal key={index}><article className="process-step"><span className="step-number">{String(index+1).padStart(2,'0')}</span><h3>{item.title}</h3><p>{item.text}</p></article></Reveal>)}</div><Buttons section={section} /></section>;
        case 'specialists': return <section id="especialistas" key={section.key} className="team-section"><div className="container team-grid"><Reveal><p className="eyebrow">{section.eyebrow}</p><h2><Title text={section.title} /></h2><p className="section-description">{section.description}</p><Buttons section={section} /></Reveal><Suspense fallback={loading}><SpecialistPreview /></Suspense></div></section>;
        case 'results': return <section id="resultados" key={section.key} className="section container"><Reveal><div className="section-top"><SectionHeading eyebrow={section.eyebrow || ''} title={section.title || ''} /><p className="section-aside">{section.description}</p></div><Suspense fallback={loading}><ResultCards limit={1} /></Suspense><Buttons section={section} /></Reveal></section>;
        case 'faqs': return <section id="preguntas" key={section.key} className="faq-section section"><div className="container faq-grid"><Reveal><p className="eyebrow">{section.eyebrow}</p><h2><Title text={section.title} /></h2><p className="section-description">{section.description}</p></Reveal><Suspense fallback={loading}><CmsFaqs /></Suspense></div></section>;
        case 'consultation': return <section id="valoracion" key={section.key} className="consultation"><div className="container"><Reveal><Sparkles size={27} strokeWidth={1.2} aria-hidden="true" /><p className="eyebrow">{section.eyebrow}</p><h2><Title text={section.title} /></h2><p>{section.description}</p><Buttons section={section} /><p className="consultation-note catalog-content">{copy.consultation_note}</p></Reveal></div></section>;
        default: return null;
      }
    })}
    <Suspense fallback={loading}><TestimonialCards /></Suspense>
  </main>;
}
