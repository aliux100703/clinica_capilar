"use client";

import Link from 'next/link';
import { useId, useRef, useState, useSyncExternalStore, type FormEvent } from 'react';
import { ArrowUpRight, Flower2, Info, LoaderCircle } from 'lucide-react';
import { localDateValue, submitInquiry, validateInquiry, type InterestOption, type InquiryErrors, type InquiryField, type InquiryKind, type InquiryValues } from '@/lib/inquiries';

const subscribe = () => () => {};
const labels: Record<InquiryField, string> = {
  name: 'Nombre', email: 'Correo electrónico', phone: 'Teléfono', treatment: 'Tratamiento de interés',
  preferredDate: 'Fecha preferida', preferredTime: 'Horario preferido', message: 'Mensaje', consent: 'Consentimiento', website: 'Sitio web',
};

export function InquiryForm({ kind = 'assessment', options = [], enabled = false, catalogError = false }: {
  kind?: InquiryKind; options?: InterestOption[]; enabled?: boolean; catalogError?: boolean;
}) {
  const prefix = useId();
  const ready = useSyncExternalStore(subscribe, () => true, () => false);
  const [errors, setErrors] = useState<InquiryErrors>({});
  const [loading, setLoading] = useState(false);
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const summary = useRef<HTMLDivElement>(null);
  const pending = useRef(false);
  const submission = useRef<{ key: string; payload: string } | null>(null);
  const assessment = kind === 'assessment';
  const blocked = !enabled || (assessment && (catalogError || options.length === 0));
  const successful = feedback?.type === 'success';
  const id = (field: string) => `${prefix}-${field}`;
  const fieldProps = (field: InquiryField, hint?: string) => ({
    id: id(field), name: field,
    'aria-invalid': Boolean(errors[field]),
    'aria-describedby': [hint ? id(hint) : '', errors[field] ? id(`${field}-error`) : ''].filter(Boolean).join(' ') || undefined,
  });
  const error = (field: InquiryField) => errors[field] && <p className="field-error" id={id(`${field}-error`)}>{errors[field]}</p>;
  function showErrors(next: InquiryErrors) {
    setErrors(next);
    requestAnimationFrame(() => summary.current?.focus());
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (pending.current || !ready || blocked || successful) return;
    const data = new FormData(event.currentTarget);
    const text = (field: string) => String(data.get(field) ?? '');
    const values: InquiryValues = {
      name: text('name'), email: text('email'), phone: text('phone'), treatment: text('treatment'),
      preferredDate: text('preferredDate'), preferredTime: text('preferredTime'), message: text('message'),
      consent: data.get('consent') === 'on', website: text('website'),
    };
    setFeedback(null);
    const next = validateInquiry(values, kind, options);
    if (Object.keys(next).length) { showErrors(next); return; }
    setErrors({});
    pending.current = true;
    setLoading(true);
    try {
      const payload = JSON.stringify(values);
      if (!submission.current || submission.current.payload !== payload) submission.current = { key: crypto.randomUUID(), payload };
      const result = await submitInquiry(values, kind, submission.current.key);
      if (result.status === 'error') {
        if (result.fieldErrors && Object.keys(result.fieldErrors).length) showErrors(result.fieldErrors);
        setFeedback({ type: 'error', message: result.message });
      } else {
        submission.current = null;
        setFeedback({ type: 'success', message: result.message });
      }
    } catch {
      setFeedback({ type: 'error', message: 'No se pudo completar el envío. No hay una cita confirmada. Puedes reintentar.' });
    } finally {
      pending.current = false;
      setLoading(false);
    }
  }

  return <form className="inquiry-form" method="post" noValidate onSubmit={submit} aria-busy={loading} aria-describedby={id('notice')}>
    <div className="form-masthead" aria-hidden="true"><span><Flower2 size={23} strokeWidth={1.2} />{assessment ? 'VALORACIÓN CAPILAR' : 'CONTACTO PERSONAL'}</span><span className="form-mode">{enabled ? 'SOLICITUD' : 'NO DISPONIBLE'}</span></div>
    <div className="demo-notice" id={id('notice')}><Info size={21} aria-hidden="true" /><div><strong>{!enabled ? 'Envíos deshabilitados' : 'Tu información merece cuidado'}</strong><p>{!enabled ? 'Los formularios permanecerán cerrados en producción hasta disponer de una política de privacidad válida.' : 'El envío guarda tus datos de contacto en la clínica. Durante las pruebas locales, utiliza únicamente datos ficticios. No incluyas información médica.'}</p></div></div>
    {assessment && (catalogError || options.length === 0) && <p className="form-feedback">{catalogError ? 'No pudimos cargar los tratamientos. Recarga la página para volver a intentarlo.' : 'Aún no hay tratamientos publicados para solicitar una valoración.'}</p>}
    <noscript><p className="form-feedback">Necesitas JavaScript para enviar este formulario.</p></noscript>
    <div ref={summary} className="error-summary" tabIndex={-1} hidden={!Object.keys(errors).length}>
      <p><strong>Revisa los siguientes campos:</strong></p><ul>{Object.entries(errors).map(([field, message]) => <li key={field}><a href={`#${id(field)}`}>{labels[field as InquiryField]}: {message}</a></li>)}</ul>
    </div>
    <fieldset disabled={!ready || loading || blocked || successful}>
      <legend>{assessment ? 'Tus datos y preferencias' : '¿En qué podemos ayudarte?'}</legend>
      <p className="form-help">{assessment ? 'La fecha y el horario son opcionales.' : 'El teléfono es opcional.'} No incluyas fotografías ni historial médico.</p>
      <div className="form-honeypot" aria-hidden="true"><label htmlFor={id('website')}>Deja este campo vacío</label><input id={id('website')} name="website" tabIndex={-1} autoComplete="off" /></div>
      <div className="form-grid">
        <div className="form-field"><label htmlFor={id('name')}>Nombre completo</label><input {...fieldProps('name')} autoComplete="name" required maxLength={150} />{error('name')}</div>
        <div className="form-field"><label htmlFor={id('email')}>Correo electrónico</label><input {...fieldProps('email')} type="email" autoComplete="email" required maxLength={254} />{error('email')}</div>
        <div className="form-field"><label htmlFor={id('phone')}>Teléfono{!assessment && ' (opcional)'}</label><input {...fieldProps('phone', 'phone-hint')} type="tel" autoComplete="tel" inputMode="tel" required={assessment} maxLength={30} /><p className="field-hint" id={id('phone-hint')}>Incluye el prefijo del país si corresponde.</p>{error('phone')}</div>
        {assessment && <>
          <div className="form-field"><label htmlFor={id('treatment')}>Tratamiento de interés</label><select {...fieldProps('treatment')} defaultValue="" required><option value="" disabled>Selecciona una opción</option>{options.map(option => <option key={option.value} value={option.value}>{option.label}</option>)}</select>{error('treatment')}</div>
          <div className="form-field"><label htmlFor={id('preferredDate')}>Fecha preferida (opcional)</label><input {...fieldProps('preferredDate', 'preferences-hint')} type="date" min={ready ? localDateValue() : undefined} />{error('preferredDate')}</div>
          <div className="form-field"><label htmlFor={id('preferredTime')}>Horario preferido (opcional)</label><input {...fieldProps('preferredTime', 'preferences-hint')} type="time" step={60} />{error('preferredTime')}</div>
        </>}
        {!assessment && <div className="form-field form-full"><label htmlFor={id('message')}>Mensaje</label><textarea {...fieldProps('message')} required maxLength={3000} rows={5} />{error('message')}</div>}
      </div>
      {assessment && <p className="preference-note" id={id('preferences-hint')}>La fecha y el horario son preferencias. No representan disponibilidad ni una cita confirmada.</p>}
      <div className="consent-field"><div className="consent-row"><input {...fieldProps('consent', 'privacy-hint')} type="checkbox" required /><label htmlFor={id('consent')}>Consiento el tratamiento de mis datos de contacto para que la clínica gestione este mensaje o solicitud. Entiendo que enviar una solicitud de valoración no confirma una cita.</label></div><p className="field-hint" id={id('privacy-hint')}>Consulta el <Link href="/privacidad" target="_blank" rel="noopener noreferrer">aviso de privacidad (se abre en otra pestaña)</Link>.</p>{error('consent')}</div>
      <button className="button form-submit" type="submit" disabled={!ready || loading || blocked || successful}>{loading ? <><LoaderCircle size={18} className="loading-icon" aria-hidden="true" />Enviando…</> : successful ? 'Envío recibido' : <>{assessment ? 'Enviar solicitud de valoración' : 'Enviar mensaje'} <ArrowUpRight size={18} aria-hidden="true" /></>}</button>
    </fieldset>
    <div role="status" aria-live="polite" aria-atomic="true" className={feedback ? `form-feedback ${feedback.type === 'error' ? 'feedback-error' : ''}` : 'sr-only'}>{loading ? 'Enviando tu solicitud.' : feedback?.message ?? ''}</div>
  </form>;
}
