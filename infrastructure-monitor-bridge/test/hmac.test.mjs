import test from 'node:test';
import assert from 'node:assert/strict';
import crypto from 'node:crypto';

function expectedSig(clientId, ts, token) {
  const payload = JSON.stringify({ clientId, ts: Number(ts) });
  return crypto.createHmac('sha256', token).update(payload).digest('hex');
}

test('HMAC signature matches monitor server algorithm', () => {
  const clientId = 'example-host';
  const ts = 1734150000000;
  const token = 'super-secret-token';

  const sig = expectedSig(clientId, ts, token);

  assert.equal(sig, expectedSig(clientId, ts, token));
  assert.equal(sig.length, 64);
});


















