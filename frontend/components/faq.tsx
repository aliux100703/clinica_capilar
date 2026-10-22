import { Plus } from 'lucide-react';
import type { Question } from '@/lib/treatments';

export function Faq({ items }: { items: Question[] }) {
  return <div className="faq-list">{items.map(item => <details key={item.question}>
    <summary>{item.question}<Plus size={19} aria-hidden="true" /></summary><p>{item.answer}</p>
  </details>)}</div>;
}
