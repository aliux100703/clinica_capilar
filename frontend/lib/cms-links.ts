export function safeLink(value: string | undefined): string | undefined {
  if (!value || /[\\\\\x00-\x20]/.test(value)) return undefined;
  if (/^#[A-Za-z][A-Za-z0-9_-]*$/.test(value)) return value;
  if (value.startsWith('/') && !value.startsWith('//')) return value;
  try {
    const url = new URL(value);
    return ['http:', 'https:', 'mailto:', 'tel:'].includes(url.protocol) && !url.username && !url.password ? value : undefined;
  } catch { return undefined; }
}
