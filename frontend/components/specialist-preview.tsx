import { UserRound } from 'lucide-react';
import { CatalogImage } from '@/components/catalog-image';
import { getSpecialists } from '@/lib/catalog';

export async function SpecialistPreview() {
  const person = (await getSpecialists())[0];
  return <div className="team-placeholder">{person ? <><CatalogImage src={person.photo} alt={person.name} portrait /><span className="card-tag">{person.specialty}</span><h3>{person.name}</h3><p>{person.description}</p></> : <><div className="profile-icon"><UserRound size={55} strokeWidth={1} aria-hidden="true" /></div><span className="card-tag">ESPACIO RESERVADO</span><h3>Equipo pendiente de publicación</h3><p>Los perfiles se mostrarán cuando la clínica los publique.</p></>}</div>;
}
