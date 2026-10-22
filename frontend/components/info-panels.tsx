import { Reveal } from '@/components/reveal';

export function InfoPanels({ items }: { items: { title: string; text: string }[] }) {
  return <div className="process-grid">{items.map((item, index) => <Reveal key={item.title}><article className="process-step"><span className="step-number">0{index + 1}</span><h3>{item.title}</h3><p>{item.text}</p></article></Reveal>)}</div>;
}
