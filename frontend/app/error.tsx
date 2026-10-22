'use client';

export default function ErrorPage({ reset }: { reset: () => void }) {
  return <main id="contenido" className="container section"><h1>No pudimos cargar el contenido.</h1><p>El servicio no está disponible por el momento. Inténtalo de nuevo en unos instantes.</p><button className="button" onClick={reset}>Volver a intentar</button></main>;
}
