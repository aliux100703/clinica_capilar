import Link from 'next/link';
import { ArrowLeft } from 'lucide-react';

export default function NotFound() {
  return <main id="contenido" tabIndex={-1} className="container section"><p className="eyebrow">404 · PÁGINA NO ENCONTRADA</p><h1>Este camino aún no existe.</h1><p className="section-description">La página que buscas no está disponible. Puedes volver al inicio o explorar el catálogo de tratamientos.</p><div className="flex flex-wrap gap-6"><Link href="/tratamientos" className="button"><ArrowLeft size={17} aria-hidden="true" />Ver tratamientos</Link><Link href="/" className="text-link">Volver al inicio</Link></div></main>;
}
