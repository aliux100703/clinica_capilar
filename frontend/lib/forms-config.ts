import 'server-only';

// The production switch must remain false until /privacidad contains the reviewed policy.
export function formsEnabled(): boolean {
  return process.env.NODE_ENV !== 'production' || process.env.PRIVACY_POLICY_APPROVED === 'true';
}
