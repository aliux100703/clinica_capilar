import Link from 'next/link';
import { ChevronRight } from 'lucide-react';

export function Breadcrumbs({ items }: { items: { label: string; href?: string }[] }) {
  return <nav aria-label="Ruta de navegación" className="breadcrumbs"><ol>
    <li><Link href="/">Inicio</Link></li>
    {items.map((item, index) => <li key={item.label}><ChevronRight size={12} aria-hidden="true" />
      {index === items.length - 1 ? <span aria-current="page">{item.label}</span> : <Link href={item.href ?? '/'}>{item.label}</Link>}
    </li>)}
  </ol></nav>;
}
