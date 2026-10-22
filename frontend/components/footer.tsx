import Link from 'next/link';
import { ArrowUpRight } from 'lucide-react';
import { navigation } from '@/lib/content';
import type { SiteSettings } from '@/lib/cms-types';
import { safeLink } from '@/lib/cms-links';

export function Footer({ settings }: { settings: SiteSettings }) {
  const links = settings.navigation?.map(item=>({label:item.label,href:safeLink(item.url)})).filter((item):item is {label:string;href:string}=>Boolean(item.href)) ?? navigation;
  return <footer className="site-footer"><div className="container">
    <div className="footer-top"><Link href="/" className="brand"><span className="brand-mark" aria-hidden="true">c<span>.</span></span><span>{settings.trade_name}<small>{settings.tagline}</small></span></Link>
      <p>{settings.contact_information}</p>
      <nav aria-label="Navegación del pie">{links.map(item => <Link key={item.href} href={item.href}>{item.label}</Link>)}</nav>
    </div>
    <div className="footer-bottom"><span>© {settings.trade_name}</span><span>{settings.footer_text} <Link href="/privacidad">Privacidad</Link></span><a href="#contenido">Volver arriba <ArrowUpRight size={14} aria-hidden="true" /></a></div>
  </div></footer>;
}
