import { apiRequest, ApiError } from '@/lib/api';

export type InquiryKind = 'assessment' | 'contact';
export type InquiryValues = {
  name: string; email: string; phone: string; treatment: string;
  preferredDate: string; preferredTime: string; message: string; consent: boolean; website: string;
};
export type InquiryField = keyof InquiryValues;
export type InquiryErrors = Partial<Record<InquiryField, string>>;
export type InterestOption = { value: string; label: string };
export type InquiryResult = { status: 'success'; message: string } | { status: 'error'; message: string; fieldErrors?: InquiryErrors };

export function localDateValue(date = new Date()): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
function validDate(value: string) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
  const [year, month, day] = value.split('-').map(Number);
  const date = new Date(year, month - 1, day);
  return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
}
export function validateInquiry(values: InquiryValues, kind: InquiryKind, options: InterestOption[], today = localDateValue()): InquiryErrors {
  const errors: InquiryErrors = {};
  if (!values.name.trim() || values.name.trim().length > 150) errors.name = 'Escribe tu nombre completo, máximo 150 caracteres.';
  if (values.email.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email.trim())) errors.email = 'Escribe un correo electrónico válido.';
  if ((kind === 'assessment' || values.phone.trim()) && !/^[+0-9() .-]{7,30}$/.test(values.phone.trim())) errors.phone = 'Escribe un teléfono válido de entre 7 y 30 caracteres.';
  if (kind === 'assessment') {
    if (!options.some(option => option.value === values.treatment)) errors.treatment = 'Selecciona un tratamiento publicado.';
    if (values.preferredDate && (!validDate(values.preferredDate) || values.preferredDate < today)) errors.preferredDate = 'Elige una fecha válida de hoy en adelante.';
    if (values.preferredTime && !values.preferredDate) errors.preferredDate = 'Indica la fecha de tu horario preferido.';
    if (values.preferredTime && !/^([01]\d|2[0-3]):[0-5]\d$/.test(values.preferredTime)) errors.preferredTime = 'Indica una hora válida.';
  } else if (!values.message.trim() || values.message.length > 3000) errors.message = 'Escribe tu mensaje, máximo 3000 caracteres.';
  if (!values.consent) errors.consent = 'Debes aceptar el tratamiento de tus datos para enviar la solicitud.';
  return errors;
}

export function toLaravelPayload(values: InquiryValues, kind: InquiryKind, key: string) {
  const common = { name: values.name.trim(), email: values.email.trim(), phone: values.phone.trim() || null,
    consent: values.consent, website: values.website, idempotency_key: key };
  return kind === 'assessment'
    ? { ...common, treatment_id: Number(values.treatment), preferred_date: values.preferredDate || null, preferred_time: values.preferredTime || null }
    : { ...common, message: values.message.trim() };
}

export async function submitInquiry(values: InquiryValues, kind: InquiryKind, key: string): Promise<InquiryResult> {
  try {
    await apiRequest<null>(process.env.NEXT_PUBLIC_API_URL ?? 'http://127.0.0.1:8000/api/v1',
      kind === 'assessment' ? 'valuation-requests' : 'contact-messages',
      { method: 'POST', body: JSON.stringify(toLaravelPayload(values, kind, key)), cache: 'no-store' });
    return { status: 'success', message: kind === 'assessment'
      ? 'Solicitud recibida. La clínica se pondrá en contacto contigo; esto no confirma una cita.'
      : 'Mensaje recibido. Gracias por ponerte en contacto.' };
  } catch (error) {
    if (!(error instanceof ApiError)) return { status: 'error', message: 'No se pudo completar el envío.' };
    const names: Record<string, InquiryField> = { name: 'name', email: 'email', phone: 'phone', treatment_id: 'treatment', preferred_date: 'preferredDate', preferred_time: 'preferredTime', message: 'message', consent: 'consent' };
    const fieldErrors: InquiryErrors = {};
    for (const [key, messages] of Object.entries(error.fields)) {
      if (names[key]) fieldErrors[names[key]] = messages[0];
    }
    return { status: 'error', message: error.message, fieldErrors };
  }
}
