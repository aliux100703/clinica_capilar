import { test, afterEach } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import Module, { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import ts from 'typescript';

const testDirectory = path.dirname(fileURLToPath(import.meta.url));

// Compile the actual TS modules in memory; no duplicate test implementation or new dependency.
const loaded = new Map();
const cacheCalls = [];
const cacheApi = { cacheTag: () => {}, cacheLife: () => {}, revalidateTag: (...args) => cacheCalls.push(['tag', ...args]), revalidatePath: (...args) => cacheCalls.push(['path', ...args]) };
function load(relative) {
  const filename = path.resolve(testDirectory, '..', relative);
  if (filename.endsWith('.json')) return JSON.parse(fs.readFileSync(filename, 'utf8'));
  if (loaded.has(filename)) return loaded.get(filename).exports;
  const mod = new Module(filename);
  mod.filename = filename;
  const originalRequire = createRequire(filename);
  mod.require = name => name === 'server-only' ? {} : name === 'next/cache' ? cacheApi : name.startsWith('@/') ? load(`${name.slice(2)}${path.extname(name) ? '' : '.ts'}`) : originalRequire(name);
  loaded.set(filename, mod);
  mod._compile(ts.transpileModule(fs.readFileSync(filename, 'utf8'), { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, esModuleInterop: true } }).outputText, filename);
  return mod.exports;
}
const api = load('lib/api.ts');
const inquiries = load('lib/inquiries.ts');
const originalFetch = global.fetch;
afterEach(() => { global.fetch = originalFetch; });
const values = { name: 'Persona ficticia', email: 'test@example.test', phone: '', treatment: '', preferredDate: '', preferredTime: '', message: 'Mensaje técnico ficticio', consent: true, website: '' };

test('contact requires a message and consent, but phone is optional', () => {
  assert.deepEqual(inquiries.validateInquiry(values, 'contact', []), {});
  const errors = inquiries.validateInquiry({ ...values, message: '', consent: false }, 'contact', []);
  assert.ok(errors.message);
  assert.ok(errors.consent);
});
test('valuation requires a published selection and validates dates and time', () => {
  const options = [{ value: '5', label: 'Fixture técnica' }];
  const valid = { ...values, treatment: '5', phone: '+52 555 000 0000', preferredDate: '2030-02-01', preferredTime: '10:30' };
  assert.deepEqual(inquiries.validateInquiry(valid, 'assessment', options, '2030-01-01'), {});
  const errors = inquiries.validateInquiry({ ...valid, preferredDate: '2030-02-30', preferredTime: '25:00', treatment: '6' }, 'assessment', options, '2030-01-01');
  assert.ok(errors.preferredDate);
  assert.ok(errors.preferredTime);
  assert.ok(errors.treatment);
});
test('payload uses the Laravel contract without medical or unused fields', () => {
  const payload = inquiries.toLaravelPayload({ ...values, treatment: '5' }, 'assessment', 'uuid');
  assert.equal(payload.treatment_id, 5);
  assert.equal(payload.preferred_time, null);
  assert.equal(payload.idempotency_key, 'uuid');
  assert.equal('message' in payload, false);
  assert.equal('treatment_id' in inquiries.toLaravelPayload(values, 'contact', 'uuid'), false);
});
test('successful submissions use JSON and return success only after the API responds', async () => {
  let received;
  global.fetch = async (url, init) => {
    received = { url, init };
    return Response.json({ data: null, message: 'Recibido' }, { status: 201 });
  };
  const result = await inquiries.submitInquiry(values, 'contact', 'same-key');
  assert.equal(result.status, 'success');
  assert.equal(received.init.method, 'POST');
  assert.equal(JSON.parse(received.init.body).idempotency_key, 'same-key');
});
test('Laravel validation errors are mapped to the correct form fields', async () => {
  global.fetch = async () => Response.json({ data: null, message: 'Invalid', errors: { treatment_id: ['Selecciona un tratamiento.'], preferred_time: ['Hora no válida.'] } }, { status: 422 });
  const result = await inquiries.submitInquiry(values, 'assessment', 'key');
  assert.equal(result.status, 'error');
  assert.equal(result.fieldErrors.treatment, 'Selecciona un tratamiento.');
  assert.equal(result.fieldErrors.preferredTime, 'Hora no válida.');
});
test('network failures and invalid server responses never confirm an inquiry', async () => {
  global.fetch = async () => { throw new Error('Simulated transport failure'); };
  assert.equal((await inquiries.submitInquiry(values, 'contact', 'key')).status, 'error');
  global.fetch = async () => new Response('<html>error</html>', { status: 200 });
  assert.equal((await inquiries.submitInquiry(values, 'contact', 'key')).status, 'error');
});
test('rate limit errors expose retry information without exposing raw server errors', async () => {
  global.fetch = async () => Response.json({ message: 'private diagnostics' }, { status: 429, headers: { 'Retry-After': '60' } });
  await assert.rejects(api.apiRequest('http://localhost/api/v1', 'contact-messages'), error => error.status === 429 && error.retryAfter === '60' && !error.message.includes('private'));
});
test('rejects executable and credential-bearing URLs', () => {
  assert.equal(api.safeMediaUrl('javascript:alert(1)'), undefined);
  assert.equal(api.safeMediaUrl('//tracker.example.test/photo'), undefined);
  assert.equal(api.safeMediaUrl('https://user:password@example.test/image'), undefined);
  assert.throws(() => api.apiBase('https://user:password@example.test/api'));
});

test('CMS links allow anchors and refuse script URLs', () => {
  const { safeLink } = load('lib/cms-links.ts');
  assert.equal(safeLink('#valoracion'), '#valoracion');
  assert.equal(safeLink('/tratamientos'), '/tratamientos');
  assert.equal(safeLink('javascript:alert(1)'), undefined);
  assert.equal(safeLink('//example.test'), undefined);
});

test('CMS returns provisional defaults only when no singleton is published', async () => {
  const cms = load('lib/cms.ts');
  global.fetch = async () => Response.json({ data: null, message: 'Contenido publicado.' });
  assert.ok((await cms.getHome()).title);
  assert.ok((await cms.getSiteSettings()).trade_name);
  global.fetch = async () => Response.json({ data: { title: 'Título CMS', subtitle: 'Texto CMS' }, message: 'Publicado' });
  assert.equal((await cms.getHome()).title, 'Título CMS');
});

test('CMS requests bypass the fetch cache so tag invalidation can expose edited content', async () => {
  const cms = load('lib/cms.ts');
  let title = 'Primera versión';
  global.fetch = async (_url, init) => {
    assert.equal(init.cache, 'no-store');
    return Response.json({ data: { title }, message: 'Publicado' });
  };
  assert.equal((await cms.getHome()).title, 'Primera versión');
  title = 'Versión editada';
  assert.equal((await cms.getHome()).title, 'Versión editada');
});

test('revalidation requires the private server token and expires the shared content tag', async () => {
  const route = load('app/api/revalidate/route.ts');
  const previous = process.env.CMS_REVALIDATE_SECRET;
  process.env.CMS_REVALIDATE_SECRET = 'test-secret-'.repeat(4);
  try {
    assert.equal((await route.POST(new Request('http://localhost/api/revalidate', { method: 'POST' }))).status, 401);
    cacheCalls.length = 0;
    const response = await route.POST(new Request('http://localhost/api/revalidate', { method: 'POST', headers: { Authorization: `Bearer ${process.env.CMS_REVALIDATE_SECRET}` } }));
    assert.equal(response.status, 200);
    assert.deepEqual(cacheCalls, [['tag', 'cms', { expire: 0 }], ['path', '/', 'layout']]);
  } finally {
    if (previous === undefined) delete process.env.CMS_REVALIDATE_SECRET;
    else process.env.CMS_REVALIDATE_SECRET = previous;
  }
});
