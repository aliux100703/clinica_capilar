"use client";
import { useEffect, useRef, useState } from "react";
import { ArrowUpRight, Menu, X } from "lucide-react";
import Link from "next/link";
import { navigation } from "@/lib/content";
import type { SiteSettings } from '@/lib/cms-types';
import { safeLink } from '@/lib/cms-links';
import { CatalogImage } from '@/components/catalog-image';

export function Header({ settings }: { settings: SiteSettings }) {
  const links = settings.navigation ? settings.navigation.map(item => ({label:item.label,href:safeLink(item.url)})).filter((item): item is {label:string;href:string} => Boolean(item.href)) : navigation;
  const ctaLabel=settings.interface_copy?.header_cta_label ?? 'Tu primera valoración';
  const ctaUrl=safeLink(settings.interface_copy?.header_cta_url ?? '/agendar-valoracion') ?? '/agendar-valoracion';
  const [open, setOpen] = useState(false);
  const toggle = useRef<HTMLButtonElement>(null);
  useEffect(() => {
    if (!open) return;
    const close = (event: KeyboardEvent) => {
      if (event.key === "Escape") { setOpen(false); toggle.current?.focus(); }
    };
    window.addEventListener("keydown", close);
    return () => window.removeEventListener("keydown", close);
  }, [open]);
  return <header className="site-header"><div className="container header-inner">
    <Link href="/" className="brand" aria-label={`${settings.trade_name}, inicio`} onClick={() => setOpen(false)}>{settings.logo ? <span className="cms-brand-image"><CatalogImage src={settings.logo} alt="" /></span> : <span className="brand-mark" aria-hidden="true">c<span>.</span></span>}<span>{settings.trade_name}<small>{settings.tagline}</small></span></Link>
    <nav className="desktop-nav" aria-label="Navegación principal">{links.map(item => <Link key={item.href} href={item.href}>{item.label}</Link>)}</nav>
    {ctaLabel && <Link className="button button-small header-cta" href={ctaUrl} onClick={() => setOpen(false)}>{ctaLabel} <ArrowUpRight size={15} aria-hidden="true" /></Link>}
    <button ref={toggle} className="menu-toggle" type="button" aria-label={open ? "Cerrar menú" : "Abrir menú"} aria-expanded={open} aria-controls="mobile-navigation" onClick={() => setOpen(!open)}>{open ? <X /> : <Menu />}</button>
  </div><nav id="mobile-navigation" className="mobile-nav" aria-label="Navegación móvil" hidden={!open}>{[...links, ...(ctaLabel ? [{label:ctaLabel,href:ctaUrl}] : [])].map((item,index) => <Link key={`${item.href}-${index}`} href={item.href} onClick={() => setOpen(false)}>{item.label}<ArrowUpRight size={17} aria-hidden="true" /></Link>)}</nav></header>;
}
