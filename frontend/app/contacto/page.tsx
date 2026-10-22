import Link from 'next/link';
import { ArrowUpRight, CalendarDays, MapPin, MessageCircle } from 'lucide-react';
import { PageIntro } from '@/components/page-intro';
import { InquiryForm } from '@/components/inquiry-form';
import { formsEnabled } from '@/lib/forms-config';
import { getPageCopy, getSiteSettings, cmsPageMetadata } from '@/lib/cms';
import { RichContent } from '@/components/rich-content';
import { CmsLink } from '@/components/cms-link';

export async function generateMetadata() { return cmsPageMetadata('contacto','/contacto'); }

export default async function ContactPage() {
  const [page,settings] = await Promise.all([getPageCopy('contacto'),getSiteSettings()]);
  return <main id="contenido" tabIndex={-1}>
    <PageIntro title={page.title} eyebrow={page.eyebrow || ''} description={page.description || ''} breadcrumbs={[{ label: 'Contacto' }]} />
    <section className="container section cms-contact-details">
      {settings.address && <p>{settings.address}</p>}
      {settings.phone && <p><CmsLink button={{label:settings.phone,url:`tel:${settings.phone.replace(/[^+0-9]/g,'')}`}} /></p>}
      {settings.whatsapp && <p><CmsLink button={{label:'WhatsApp',url:`https://wa.me/${settings.whatsapp.replace(/\D/g,'')}`}} /></p>}
      {settings.email && <p><CmsLink button={{label:settings.email,url:`mailto:${settings.email}`}} /></p>}
      {settings.opening_hours?.map((item,index)=><p key={index}>{item.days}: {item.hours}</p>)}
      {settings.social_links?.map((button,index)=><CmsLink key={index} button={button} />)}
      {settings.contact_information && <p>{settings.contact_information}</p>}
      <RichContent html={page.content} />
    </section>
    <section className="container section"><div className="contact-grid">{[
      { icon: MapPin, title: 'Ubicación', text: settings.address || 'Información pendiente de publicación.' },
      { icon: MessageCircle, title: 'Un espacio para tus dudas', text: 'Puedes escribir mediante el formulario cuando esté habilitado. No envíes información médica.' },
      { icon: CalendarDays, title: 'Solicitud y confirmación', text: 'Una solicitud de valoración queda pendiente de revisión. No constituye una cita confirmada.' },
    ].map(({ icon: Icon, title, text }) => <article key={title} className="contact-card"><Icon size={26} strokeWidth={1.3} aria-hidden="true" /><h2>{title}</h2><p>{text}</p></article>)}</div></section>
    <section className="container section inquiry-layout" aria-labelledby="contact-form-heading"><div className="inquiry-intro"><p className="eyebrow">CONTACTO</p><h2 id="contact-form-heading">{page.aside_title || page.title}</h2><p className="section-description">{page.aside_description || page.description}</p><Link href="/agendar-valoracion" className="text-link">Solicitar una valoración <ArrowUpRight size={18} aria-hidden="true" /></Link></div><InquiryForm kind="contact" enabled={formsEnabled()} /></section>
  </main>;
}
