import Link from 'next/link';
import { ArrowUpRight } from 'lucide-react';
import { safeLink } from '@/lib/cms-links';
import type { CmsButton } from '@/lib/cms-types';

export function CmsLink({ button, className = 'text-link' }: { button: CmsButton; className?: string }) {
  const url = safeLink(button.url);
  if (!url || !button.label) return null;
  return <Link href={url} className={className}>{button.label}<ArrowUpRight size={17} aria-hidden="true" /></Link>;
}
