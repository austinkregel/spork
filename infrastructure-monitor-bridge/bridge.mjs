import crypto from 'node:crypto';
import process from 'node:process';
import http from 'node:http';
import dotenv from 'dotenv';
import { io } from 'socket.io-client';
import { Server as IOServer } from 'socket.io';
import { createClient as createRedisClient } from 'redis';
import mysql from 'mysql2/promise';
import { randomUUID, createHmac } from 'node:crypto';
process.on('unhandledRejection', (reason) => {
  console.warn('[bridge] unhandledRejection', { reason: String(reason || '') });
});
process.on('uncaughtException', (err) => {
  console.error('[bridge] uncaughtException', { message: err?.message });
});

dotenv.config();

const MONITOR_URL = process.env.MONITOR_SERVER_URL || 'wss://monitor.kratos.kregel.host';
const AGENT_AUTH_TOKEN = process.env.MONITOR_SERVER_AUTH_TOKEN || '';
const SPORK_INGEST_URL = process.env.SPORK_INGEST_URL || 'http://echo.kregel.dev/api/infrastructure/monitor/ingest';

const CLIENT_ID = process.env.MONITOR_BRIDGE_CLIENT_ID || `spork-bridge-${crypto.randomUUID()}`;

const AUT_HAIR_MACHINE_INFO_URL = process.env.AUT_HAIR_MACHINE_INFO_URL || 'https://aut.hair/api/machine-info';
// Dashboard token is typically an access token, so default to Bearer; overrideable if aut.hair expects a different scheme.
const AUT_HAIR_MACHINE_TOKEN_SCHEME = process.env.AUT_HAIR_MACHINE_TOKEN_SCHEME || 'Bearer';
const AUT_HAIR_MACHINE_INFO_PREFLIGHT_REQUIRED = String(process.env.AUT_HAIR_MACHINE_INFO_PREFLIGHT_REQUIRED || '').trim() === '1';

const BRIDGE_LOG_SOCKET_EVENTS = String(process.env.BRIDGE_LOG_SOCKET_EVENTS || '').trim() === '1';
const BRIDGE_LOG_MAX_CHARS = Number(process.env.BRIDGE_LOG_MAX_CHARS || 3_000);
const BRIDGE_LOG_MAX_DEPTH = Number(process.env.BRIDGE_LOG_MAX_DEPTH || 5);
const BRIDGE_LOG_MAX_KEYS = Number(process.env.BRIDGE_LOG_MAX_KEYS || 80);
const BRIDGE_LOG_MAX_ARRAY = Number(process.env.BRIDGE_LOG_MAX_ARRAY || 80);

const REDIS_URL = process.env.REDIS_URL || '';
const REDIS_HOST = process.env.REDIS_HOST || '127.0.0.1';
const REDIS_PORT = Number(process.env.REDIS_PORT || 6379);
const REDIS_PASSWORD = process.env.REDIS_PASSWORD || '';
const REDIS_CHANNEL = process.env.SPORK_MONITOR_COMMANDS_CHANNEL || 'spork:monitor:commands';

const BRIDGE_HOST = process.env.BRIDGE_HOST || '0.0.0.0';
const BRIDGE_PORT = Number(process.env.BRIDGE_PORT || 6002);
const BRIDGE_ALLOWED_ORIGINS = String(process.env.BRIDGE_ALLOWED_ORIGINS || '*')
  .split(',')
  .map((s) => s.trim())
  .filter(Boolean);

/** @type {import('socket.io-client').Socket | null} */
let monitorDashboardSocket = null;

/** @type {Map<string, Array<string>>} */
const pendingShellStartSocketIds = new Map();

/** @type {Map<string, string>} */
const shellSessionOwnerSocketId = new Map(); // monitor_session -> ui socket.id

/** @type {Map<string, ReturnType<typeof setTimeout>>} */
const shellFirstOutputWatchdog = new Map(); // monitor_session -> timer

const bridgeState = {
  monitor_connected: false,
  monitor_socket_id: null,
  last_monitor_event_at: null,
};

/** @type {mysql.Pool | null} */
let mysqlPool = null;

const BRIDGE_CREDENTIAL_TTL_MS = Number(process.env.BRIDGE_CREDENTIAL_TTL_MS || 30_000);
const DASHBOARD_TOKEN_TTL_MS = Number(process.env.DASHBOARD_TOKEN_TTL_MS || 30_000);

// Dashboard token env fallback (legacy).
const DASHBOARD_AUTH_TOKEN_ENV = String(process.env.MONITOR_SERVER_DASHBOARD_TOKEN || '').trim();

// This is the "credentials entry" (row) the bridge will use for the machine-to-machine dashboard JWT.
//
// Expected MySQL row in `credentials`:
// - service: (default) 'monitor-dashboard'
// - access_token: current JWT access token (optional; bridge will populate/refresh)
// - api_key: OAuth client_id (required to mint new tokens)
// - secret_key: OAuth client_secret (required to mint new tokens)
// - settings (json): must contain `oauth_token_url` or `token_url`
//   Optional: `scope`, `audience`, `extra_token_params` (object of additional form fields)
const MONITOR_DASHBOARD_CREDENTIAL_SERVICE = String(process.env.MONITOR_DASHBOARD_CREDENTIAL_SERVICE || 'monitor-dashboard').trim() || 'monitor-dashboard';

// Prefer DB-backed token over env fallback by default.
const DASHBOARD_TOKEN_PREFER_DB = String(process.env.DASHBOARD_TOKEN_PREFER_DB || '1').trim() === '1';

// Refresh a little early to avoid edge cases around clock skew.
const DASHBOARD_TOKEN_REFRESH_LEEWAY_SEC = Number(process.env.DASHBOARD_TOKEN_REFRESH_LEEWAY_SEC || 120);

// Avoid tight reconnect/refresh loops when auth is misconfigured or the server rejects the token.
const DASHBOARD_RECONNECT_MIN_INTERVAL_MS = Number(process.env.DASHBOARD_RECONNECT_MIN_INTERVAL_MS || 2000);
const DASHBOARD_RECONNECT_MAX_BACKOFF_MS = Number(process.env.DASHBOARD_RECONNECT_MAX_BACKOFF_MS || 30_000);
const DASHBOARD_TOKEN_MINT_COOLDOWN_MS = Number(process.env.DASHBOARD_TOKEN_MINT_COOLDOWN_MS || 10_000);

/** @type {{ value: { id: number, user_id: number, access_token: string, api_key: string, secret_key: string, settings: any } | null, fetchedAt: number, inFlight: Promise<any> | null }} */
const cachedDashboardCredential = {
  value: null,
  fetchedAt: 0,
  inFlight: null,
};

/** @type {{ value: string | null, expMs: number | null, fetchedAt: number, inFlight: Promise<any> | null }} */
const cachedDashboardAccessToken = {
  value: null,
  expMs: null,
  fetchedAt: 0,
  inFlight: null,
};

/** @type {Promise<any> | null} */
let dashboardReconnectInFlight = null;
/** @type {ReturnType<typeof setTimeout> | null} */
let dashboardReconnectTimer = null;
let dashboardReconnectAttempts = 0;
let lastDashboardReconnectAtMs = 0;
let lastDashboardTokenMintAttemptAtMs = 0;

/** @type {{ value: { id: number, user_id: number, api_key: string } | null, fetchedAt: number, inFlight: Promise<any> | null }} */
const cachedBridgeCredential = {
  value: null,
  fetchedAt: 0,
  inFlight: null,
};

function shouldRedactKey(key) {
  const k = String(key || '').toLowerCase();
  return (
    k.includes('authorization')
    || k.includes('cookie')
    || k === 'token'
    || k.includes('access_token')
    || k.includes('accesstoken')
    || k === 'sig'
    || k.includes('password')
    || k.includes('secret')
    || k.includes('api_key')
    || k.includes('apikey')
  );
}

function truncateString(value, maxChars = BRIDGE_LOG_MAX_CHARS) {
  const s = String(value ?? '');
  if (s.length <= maxChars) return s;
  return `${s.slice(0, Math.max(0, maxChars))}…(truncated ${s.length - maxChars} chars)`;
}

function base64UrlDecodeToString(input) {
  const str = String(input || '');
  if (!str) return '';
  // Base64url -> base64
  let b64 = str.replace(/-/g, '+').replace(/_/g, '/');
  // Pad
  const pad = b64.length % 4;
  if (pad) b64 += '='.repeat(4 - pad);
  try {
    return Buffer.from(b64, 'base64').toString('utf8');
  } catch {
    return '';
  }
}

function decodeJwtPayload(token) {
  const t = String(token || '').trim();
  const parts = t.split('.');
  if (parts.length < 2) return null;
  const json = base64UrlDecodeToString(parts[1]);
  if (!json) return null;
  try {
    const parsed = JSON.parse(json);
    return parsed && typeof parsed === 'object' ? parsed : null;
  } catch {
    return null;
  }
}

function getJwtExpMs(token) {
  const payload = decodeJwtPayload(token);
  const exp = payload?.exp;
  if (typeof exp === 'number' && Number.isFinite(exp) && exp > 0) {
    return exp * 1000;
  }
  if (typeof exp === 'string' && exp.trim() && Number.isFinite(Number(exp))) {
    return Number(exp) * 1000;
  }
  return null;
}

function isJwtExpiredOrExpiring(token, leewaySec = DASHBOARD_TOKEN_REFRESH_LEEWAY_SEC) {
  const expMs = getJwtExpMs(token);
  if (!expMs) return false; // Non-JWT tokens: treat as "unknown", don't auto-refresh by exp.
  return expMs <= (Date.now() + (Number(leewaySec) || 0) * 1000);
}

function sanitizeForLog(value, depth = 0) {
  if (value == null) return value;

  if (typeof value === 'string') {
    // Redact obvious bearer tokens even if not under a sensitive key
    if (/^Bearer\s+.+/i.test(value)) return 'Bearer [REDACTED]';
    return truncateString(value);
  }
  if (typeof value === 'number' || typeof value === 'boolean') return value;
  if (typeof value === 'bigint') return `${value}n`;
  if (typeof value === 'function') return '[Function]';

  if (value instanceof Error) {
    return { name: value.name, message: truncateString(value.message), stack: truncateString(value.stack || '') };
  }

  if (depth >= BRIDGE_LOG_MAX_DEPTH) {
    if (Array.isArray(value)) return `[Array(maxDepth, length=${value.length})]`;
    return '[Object(maxDepth)]';
  }

  if (Array.isArray(value)) {
    const sliced = value.slice(0, BRIDGE_LOG_MAX_ARRAY).map((v) => sanitizeForLog(v, depth + 1));
    if (value.length > BRIDGE_LOG_MAX_ARRAY) {
      sliced.push(`[... +${value.length - BRIDGE_LOG_MAX_ARRAY} more]`);
    }
    return sliced;
  }

  if (typeof value === 'object') {
    // Avoid circular refs
    const out = {};
    const keys = Object.keys(value);
    const limitedKeys = keys.slice(0, BRIDGE_LOG_MAX_KEYS);
    for (const key of limitedKeys) {
      if (shouldRedactKey(key)) {
        out[key] = '[REDACTED]';
        continue;
      }
      try {
        out[key] = sanitizeForLog(value[key], depth + 1);
      } catch {
        out[key] = '[Unserializable]';
      }
    }
    if (keys.length > BRIDGE_LOG_MAX_KEYS) {
      out._truncated_keys = keys.length - BRIDGE_LOG_MAX_KEYS;
    }
    return out;
  }

  // fallback for symbols/unknown types
  try {
    return truncateString(String(value));
  } catch {
    return '[Unknown]';
  }
}

function extractClientIdFromArgs(args) {
  const first = args?.[0];
  if (first && typeof first === 'object') {
    const cid = first.clientId ?? first.client_id ?? first.client ?? first.hostname ?? first.host ?? null;
    if (typeof cid === 'string' && cid.trim()) return cid.trim();
  }
  return null;
}

function logSocketEvent({ source, event, socket_id, args }) {
  if (!BRIDGE_LOG_SOCKET_EVENTS) return;

  // Avoid noisy internal socket.io events
  if (event === 'ping' || event === 'pong') return;

  const payloadPreview = sanitizeForLog(args, 0);
  const clientId = extractClientIdFromArgs(args);

  const line = {
    ts: new Date().toISOString(),
    source,
    event,
    socket_id: socket_id || null,
    clientId,
    args: payloadPreview,
  };

  try {
    console.log('[bridge][socklog]', JSON.stringify(line));
  } catch {
    console.log('[bridge][socklog]', { ts: line.ts, source, event, socket_id: socket_id || null, clientId });
  }
}

async function requireAutHairMachineInfoOk() {
  const token = await getMonitorDashboardAccessToken({ force: false });
  if (!token) {
    const msg = '[bridge] missing machine token; set up monitor-dashboard credential (DB) or MONITOR_SERVER_DASHBOARD_TOKEN.';
    if (AUT_HAIR_MACHINE_INFO_PREFLIGHT_REQUIRED) {
      console.error(msg);
      process.exit(1);
    }
    console.warn(msg);
    return false;
  }

  if (typeof fetch !== 'function') {
    const msg = '[bridge] global fetch is not available; cannot validate aut.hair machine-info preflight.';
    if (AUT_HAIR_MACHINE_INFO_PREFLIGHT_REQUIRED) {
      console.error(msg);
      process.exit(1);
    }
    console.warn(msg);
    return false;
  }

  const scheme = String(AUT_HAIR_MACHINE_TOKEN_SCHEME || 'Bearer').trim() || 'Bearer';
  const authValue = `${scheme} ${token}`;

  let res;
  try {
    res = await fetch(AUT_HAIR_MACHINE_INFO_URL, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': authValue,
      },
    });
  } catch (err) {
    const details = { message: err?.message };
    if (AUT_HAIR_MACHINE_INFO_PREFLIGHT_REQUIRED) {
      console.error('[bridge] aut.hair machine-info preflight failed (network)', details);
      process.exit(1);
    }
    console.warn('[bridge] aut.hair machine-info preflight failed (network)', details);
    return false;
  }

  if (res.status !== 200) {
    let body = '';
    try { body = await res.json(); } catch { /* ignore */ }
    const details = { status: res.status, body, url: AUT_HAIR_MACHINE_INFO_URL };
    if (AUT_HAIR_MACHINE_INFO_PREFLIGHT_REQUIRED) {
      console.error('[bridge] aut.hair machine-info preflight failed', details);
      process.exit(1);
    }
    console.warn('[bridge] aut.hair machine-info preflight failed', details);
    return false;
  }

  console.log('[bridge] aut.hair machine-info preflight ok');
  return true;
}

function getMysqlConfig() {
  // Prefer Laravel-style envs when running under Sail; fall back to MYSQL_*.
  const host = process.env.DB_HOST || process.env.MYSQL_HOST || '127.0.0.1';
  const port = Number(process.env.DB_PORT || process.env.MYSQL_PORT || 3306);
  const user = process.env.DB_USERNAME || process.env.MYSQL_USER || 'root';
  const password = process.env.DB_PASSWORD || process.env.MYSQL_PASSWORD || '';
  const database = process.env.DB_DATABASE || process.env.MYSQL_DATABASE || 'laravel';

  return {
    host,
    port,
    user,
    password,
    database,
  };
}

function getMysqlPool() {
  if (mysqlPool) return mysqlPool;

  const cfg = getMysqlConfig();
  mysqlPool = mysql.createPool({
    host: cfg.host,
    port: cfg.port,
    user: cfg.user,
    password: cfg.password,
    database: cfg.database,
    waitForConnections: true,
    connectionLimit: 5,
    // Keep low: bridge is light and we want to be gentle.
    maxIdle: 2,
    idleTimeout: 60_000,
    enableKeepAlive: true,
    keepAliveInitialDelay: 0,
  });

  return mysqlPool;
}

function normalizeMysqlSettings(settings) {
  if (settings == null) return null;
  if (typeof settings === 'object') return settings;
  const raw = String(settings || '').trim();
  if (!raw) return null;
  try {
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === 'object' ? parsed : null;
  } catch {
    return null;
  }
}

/**
 * Fetch the monitor dashboard credential (client_credentials config + cached access_token) from MySQL.
 *
 * @returns {Promise<{ id: number, user_id: number, access_token: string, api_key: string, secret_key: string, settings: any } | null>}
 */
async function fetchMonitorDashboardCredential() {
  try {
    const pool = getMysqlPool();
    const [rows] = await pool.query(
      `SELECT id, user_id, access_token, api_key, secret_key, settings
       FROM credentials
       WHERE service = ?
       ORDER BY updated_at DESC, id DESC
       LIMIT 1`,
      [MONITOR_DASHBOARD_CREDENTIAL_SERVICE]
    );

    const row = Array.isArray(rows) ? rows[0] : null;
    if (!row) return null;

    return {
      id: Number(row.id),
      user_id: Number(row.user_id),
      access_token: String(row.access_token || ''),
      api_key: String(row.api_key || ''),
      secret_key: String(row.secret_key || ''),
      settings: normalizeMysqlSettings(row.settings),
    };
  } catch (err) {
    console.warn('[bridge] mysql fetchMonitorDashboardCredential error', { message: err?.message });
    return null;
  }
}

/**
 * Get cached monitor dashboard credential, refreshing from MySQL as needed.
 *
 * @param {{ force?: boolean }} opts
 * @returns {Promise<{ id: number, user_id: number, access_token: string, api_key: string, secret_key: string, settings: any } | null>}
 */
async function getDashboardCredential(opts = {}) {
  const force = Boolean(opts.force);
  const now = Date.now();
  const isFresh = cachedDashboardCredential.value && (now - cachedDashboardCredential.fetchedAt) < DASHBOARD_TOKEN_TTL_MS;

  if (!force && isFresh) {
    return cachedDashboardCredential.value;
  }

  if (cachedDashboardCredential.inFlight) {
    try {
      await cachedDashboardCredential.inFlight;
    } catch {
      // ignore
    }
    return cachedDashboardCredential.value;
  }

  cachedDashboardCredential.inFlight = (async () => {
    const next = await fetchMonitorDashboardCredential();
    if (next) {
      cachedDashboardCredential.value = next;
      cachedDashboardCredential.fetchedAt = Date.now();
    }
  })();

  try {
    await cachedDashboardCredential.inFlight;
  } finally {
    cachedDashboardCredential.inFlight = null;
  }

  return cachedDashboardCredential.value;
}

function buildOAuthTokenRequestParams(cred) {
  const settings = cred?.settings && typeof cred.settings === 'object' ? cred.settings : {};

  const tokenUrl = String(settings.oauth_token_url || settings.token_url || '').trim();
  const clientId = String(settings.client_id || cred?.api_key || '').trim();
  const clientSecret = String(settings.client_secret || cred?.secret_key || '').trim();
  const scope = String(settings.scope || '').trim();
  const audience = String(settings.audience || settings.aud || '').trim();

  const extra = settings.extra_token_params && typeof settings.extra_token_params === 'object'
    ? settings.extra_token_params
    : null;

  return { tokenUrl, clientId, clientSecret, scope, audience, extra };
}

async function mintClientCredentialsAccessToken(cred) {
  if (typeof fetch !== 'function') {
    throw new Error('global fetch is not available');
  }

  const { tokenUrl, clientId, clientSecret, scope, audience, extra } = buildOAuthTokenRequestParams(cred);

  if (!tokenUrl) throw new Error('missing token_url in settings (oauth_token_url or token_url)');
  if (!clientId) throw new Error('missing client_id (settings.client_id or credentials.api_key)');
  if (!clientSecret) throw new Error('missing client_secret (settings.client_secret or credentials.secret_key)');

  const body = new URLSearchParams();
  body.set('grant_type', 'client_credentials');
  body.set('client_id', clientId);
  body.set('client_secret', clientSecret);
  if (scope) body.set('scope', scope);
  if (audience) body.set('audience', audience);
  if (extra) {
    for (const [k, v] of Object.entries(extra)) {
      if (v == null) continue;
      body.set(String(k), String(v));
    }
  }

  const res = await fetch(tokenUrl, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body,
  });

  const text = await res.text().catch(() => '');
  let json = null;
  try { json = text ? JSON.parse(text) : null; } catch { /* ignore */ }

  if (!res.ok) {
    const msg = (json && typeof json === 'object' && (json.error_description || json.error))
      ? String(json.error_description || json.error)
      : `HTTP ${res.status}`;
    throw new Error(`token endpoint request failed: ${msg}`);
  }

  const accessToken = String(json?.access_token || '').trim();
  if (!accessToken) throw new Error('token endpoint response missing access_token');

  return {
    accessToken,
    expiresIn: Number(json?.expires_in || 0) || 0,
    tokenType: String(json?.token_type || '').trim(),
    raw: json,
  };
}

async function persistDashboardAccessToken(credId, accessToken, extraSettings = null) {
  try {
    const pool = getMysqlPool();
    const current = await getDashboardCredential({ force: true });
    const currentSettings = current?.id === credId ? (current.settings || {}) : {};

    const mergedSettings = {
      ...(currentSettings && typeof currentSettings === 'object' ? currentSettings : {}),
      ...(extraSettings && typeof extraSettings === 'object' ? extraSettings : {}),
    };

    await pool.query(
      `UPDATE credentials
       SET access_token = ?, settings = ?, updated_at = NOW()
       WHERE id = ?`,
      [String(accessToken || ''), JSON.stringify(mergedSettings || {}), Number(credId)]
    );
  } catch (err) {
    console.warn('[bridge] mysql persistDashboardAccessToken failed', { message: err?.message });
  }
}

async function refreshDashboardAccessTokenFromDbCredential({ force } = { force: false }) {
  const cred = await getDashboardCredential({ force: Boolean(force) });
  if (!cred) return null;

  const existing = String(cred.access_token || '').trim();
  if (existing && !isJwtExpiredOrExpiring(existing)) {
    cachedDashboardAccessToken.value = existing;
    cachedDashboardAccessToken.expMs = getJwtExpMs(existing);
    cachedDashboardAccessToken.fetchedAt = Date.now();
    return existing;
  }

  // Try minting a new token via client_credentials.
  try {
    const now = Date.now();
    if ((now - lastDashboardTokenMintAttemptAtMs) < DASHBOARD_TOKEN_MINT_COOLDOWN_MS) {
      // Avoid hammering the token endpoint.
      return existing || null;
    }
    lastDashboardTokenMintAttemptAtMs = now;

    const minted = await mintClientCredentialsAccessToken(cred);
    const expMs = getJwtExpMs(minted.accessToken);
    const expiresAtIso = expMs ? new Date(expMs).toISOString() : null;
    await persistDashboardAccessToken(cred.id, minted.accessToken, {
      last_token_refreshed_at: new Date().toISOString(),
      last_token_expires_at: expiresAtIso,
      last_token_expires_in: minted.expiresIn || null,
      last_token_type: minted.tokenType || null,
    });

    cachedDashboardAccessToken.value = minted.accessToken;
    cachedDashboardAccessToken.expMs = expMs;
    cachedDashboardAccessToken.fetchedAt = Date.now();

    return minted.accessToken;
  } catch (err) {
    console.error('[bridge] dashboard token mint failed', { 
      message: err?.message,
      credential_id: cred?.id || null,
      token_url: cred?.settings?.oauth_token_url || cred?.settings?.token_url || null,
      has_client_id: Boolean(cred?.api_key || cred?.settings?.client_id),
      has_client_secret: Boolean(cred?.secret_key || cred?.settings?.client_secret),
    });
    // Fall back to existing token even if expired, to preserve legacy behavior (server may still accept it).
    if (existing) return existing;
    return null;
  }
}

/**
 * Get the monitor dashboard access token.
 *
 * This prefers a DB-backed OAuth-minted JWT (stored in `credentials.access_token`), but keeps
 * env var support as a fallback for local debugging / legacy deployments.
 *
 * @param {{ force?: boolean }} opts
 * @returns {Promise<string>}
 */
async function getMonitorDashboardAccessToken(opts = {}) {
  const force = Boolean(opts.force);
  const now = Date.now();
  const isFresh = cachedDashboardAccessToken.value && (now - cachedDashboardAccessToken.fetchedAt) < DASHBOARD_TOKEN_TTL_MS;
  const cachedToken = cachedDashboardAccessToken.value ? String(cachedDashboardAccessToken.value || '').trim() : '';

  if (!force && isFresh && cachedToken) {
    return cachedToken;
  }

  if (cachedDashboardAccessToken.inFlight) {
    try {
      await cachedDashboardAccessToken.inFlight;
    } catch {
      // ignore
    }
    return String(cachedDashboardAccessToken.value || '').trim() || (DASHBOARD_AUTH_TOKEN_ENV || '');
  }

  cachedDashboardAccessToken.inFlight = (async () => {
    // Prefer DB if configured to do so, otherwise env-first.
    if (DASHBOARD_TOKEN_PREFER_DB) {
      const dbToken = await refreshDashboardAccessTokenFromDbCredential({ force });
      if (dbToken) return;
      if (DASHBOARD_AUTH_TOKEN_ENV) {
        cachedDashboardAccessToken.value = DASHBOARD_AUTH_TOKEN_ENV;
        cachedDashboardAccessToken.expMs = getJwtExpMs(DASHBOARD_AUTH_TOKEN_ENV);
        cachedDashboardAccessToken.fetchedAt = Date.now();
      }
      return;
    }

    if (DASHBOARD_AUTH_TOKEN_ENV) {
      cachedDashboardAccessToken.value = DASHBOARD_AUTH_TOKEN_ENV;
      cachedDashboardAccessToken.expMs = getJwtExpMs(DASHBOARD_AUTH_TOKEN_ENV);
      cachedDashboardAccessToken.fetchedAt = Date.now();
      return;
    }

    const dbToken = await refreshDashboardAccessTokenFromDbCredential({ force });
    if (dbToken) return;
  })();

  try {
    await cachedDashboardAccessToken.inFlight;
  } finally {
    cachedDashboardAccessToken.inFlight = null;
  }

  return String(cachedDashboardAccessToken.value || '').trim() || '';
}

/**
 * Fetch the newest monitor-bridge credential (token) from MySQL.
 *
 * @returns {Promise<{ id: number, user_id: number, api_key: string } | null>}
 */
async function fetchMonitorBridgeCredential() {
  try {
    const pool = getMysqlPool();
    const [rows] = await pool.query(
      `SELECT id, user_id, api_key
       FROM credentials
       WHERE type IN ('backup_agent', 'development')
         AND service = 'monitor-bridge'
         AND api_key IS NOT NULL
         AND api_key != ''
       ORDER BY updated_at DESC, id DESC
       LIMIT 1`
    );

    const row = Array.isArray(rows) ? rows[0] : null;
    if (!row) return null;

    return {
      id: Number(row.id),
      user_id: Number(row.user_id),
      api_key: String(row.api_key || ''),
    };
  } catch (err) {
    console.warn('[bridge] mysql fetchBackupAgentCredential error', { message: err?.message });
    return null;
  }
}

/**
 * Get the cached backup_agent credential (refreshing from MySQL as needed).
 *
 * @param {{ force?: boolean }} opts
 * @returns {Promise<{ id: number, user_id: number, api_key: string } | null>}
 */
async function getBridgeCredential(opts = {}) {
  const force = Boolean(opts.force);
  const now = Date.now();
  const isFresh = cachedBridgeCredential.value && (now - cachedBridgeCredential.fetchedAt) < BRIDGE_CREDENTIAL_TTL_MS;

  if (!force && isFresh) {
    return cachedBridgeCredential.value;
  }

  if (cachedBridgeCredential.inFlight) {
    try {
      await cachedBridgeCredential.inFlight;
    } catch {
      // ignore
    }
    return cachedBridgeCredential.value;
  }

  cachedBridgeCredential.inFlight = (async () => {
    const next = await fetchMonitorBridgeCredential();
    if (next?.api_key) {
      cachedBridgeCredential.value = next;
      cachedBridgeCredential.fetchedAt = Date.now();
    }
  })();

  try {
    await cachedBridgeCredential.inFlight;
  } finally {
    cachedBridgeCredential.inFlight = null;
  }

  return cachedBridgeCredential.value;
}

/**
 * Validate a UI-provided token against the current DB token, with a one-time refresh.
 *
 * @param {string} token
 * @returns {Promise<boolean>}
 */
async function validateBridgeToken(token) {
  const t = String(token || '').trim();
  if (!t) return false;

  const current = await getBridgeCredential({ force: false });
  if (current?.api_key && t === current.api_key) return true;

  const refreshed = await getBridgeCredential({ force: true });
  return Boolean(refreshed?.api_key && t === refreshed.api_key);
}

function originAllowed(origin) {
  if (!origin) return true; // non-browser / same-origin cases
  if (BRIDGE_ALLOWED_ORIGINS.includes('*')) return true;
  return BRIDGE_ALLOWED_ORIGINS.includes(origin);
}

function buildBridgeStatusPayload() {
  return {
    monitor_connected: Boolean(bridgeState.monitor_connected),
    monitor_socket_id: bridgeState.monitor_socket_id,
    ui_clients_count: uiNamespace?.sockets?.size ?? 0,
    last_monitor_event_at: bridgeState.last_monitor_event_at,
    ts: new Date().toISOString(),
  };
}

function emitBridgeStatus() {
  try {
    uiNamespace.emit('bridge_status', buildBridgeStatusPayload());
  } catch (err) {
    // ignore
  }
}

function markMonitorEvent() {
  bridgeState.last_monitor_event_at = new Date().toISOString();
}

function clearShellFirstOutputWatchdog(session) {
  const sess = String(session || '').trim();
  if (!sess) return;
  const t = shellFirstOutputWatchdog.get(sess);
  if (!t) return;
  try { clearTimeout(t); } catch { /* ignore */ }
  shellFirstOutputWatchdog.delete(sess);
}

function startShellFirstOutputWatchdog({ session, clientId }) {
  const sess = String(session || '').trim();
  if (!sess) return;

  clearShellFirstOutputWatchdog(sess);

  const timer = setTimeout(() => {
    shellFirstOutputWatchdog.delete(sess);

    const ownerSocketId = shellSessionOwnerSocketId.get(sess) || null;
    const target = ownerSocketId ? uiNamespace.sockets.get(ownerSocketId) : null;

    const message = [
      'Shell session started but no shell_output received.',
      'Likely causes: PTY unavailable on agent, agent-side shell spawn failure, or monitor server not relaying output.',
      `session=${sess}`,
      clientId ? `clientId=${String(clientId)}` : null,
    ].filter(Boolean).join(' ');

    try {
      if (target) target.emit('shell_error', { message, clientId: clientId || null, session: sess });
      else uiNamespace.emit('shell_error', { message, clientId: clientId || null, session: sess });
    } catch {
      // ignore
    }
  }, Number(process.env.BRIDGE_SHELL_NO_OUTPUT_MS || 2500));

  try { timer.unref?.(); } catch { /* ignore */ }
  shellFirstOutputWatchdog.set(sess, timer);
}

function hmacSig(clientId, ts, token) {
  const payload = JSON.stringify({ clientId, ts: Number(ts) });
  return crypto.createHmac('sha256', token).update(payload).digest('hex');
}

async function ingest(eventType, payload) {
  if (!SPORK_INGEST_URL) {
    console.warn('[bridge] ingest skipped: SPORK_INGEST_URL not configured');
    return;
  }
  
  const credential = await getBridgeCredential({ force: false });
  const token = String(credential?.api_key || '').trim();
  if (!token) {
    console.warn('[bridge] ingest skipped: no bridge credential token available', {
      credential_id: credential?.id || null,
      event_type: eventType,
      client_id: payload?.clientId || payload?.client_id || null,
    });
    return;
  }
  
  const clientId = payload?.clientId || payload?.client_id || null;
  
  try {
    const res = await fetch(SPORK_INGEST_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`, // Standard HTTP header
      },
      body: JSON.stringify({
        event_type: eventType,
        payload,
        received_at: new Date().toISOString(),
      }),
    });

    if (!res.ok) {
      const text = await res.text().catch(() => '');
      console.warn('[bridge] ingest failed', { 
        status: res.status, 
        event_type: eventType,
        client_id: clientId,
        text: text.slice(0, 300) 
      });
    } else {
      console.log('[bridge] ingest success', { 
        event_type: eventType,
        client_id: clientId,
        status: res.status,
      });
    }
  } catch (err) {
    console.warn('[bridge] ingest error', { 
      message: err?.message,
      event_type: eventType,
      client_id: clientId,
      url: SPORK_INGEST_URL,
    });
  }
}
async function authenticate() {
  const payload = { clientId: CLIENT_ID, ts: Date.now() };
  const sig = createHmac('sha256', AGENT_AUTH_TOKEN).update(JSON.stringify(payload)).digest('hex');
  return { ...payload, sig };
}

function isAuthErrorMessage(message) {
  const m = String(message || '').toLowerCase();
  return (
    m.includes('unauthorized')
    || m.includes('forbidden')
    || m.includes('jwt')
    || m.includes('token')
    || m.includes('invalid')
    || m.includes('authentication')
    || m.includes('auth')
    || m.includes('401')
    || m.includes('403')
  );
}

async function reconnectDashboardWithFreshToken(reason) {
  if (dashboardReconnectInFlight) {
    return dashboardReconnectInFlight;
  }

  dashboardReconnectInFlight = (async () => {
    try {
      const now = Date.now();
      const sinceLast = now - lastDashboardReconnectAtMs;
      if (sinceLast < DASHBOARD_RECONNECT_MIN_INTERVAL_MS) {
        return;
      }
      lastDashboardReconnectAtMs = now;

      console.warn('[bridge] refreshing dashboard auth and reconnecting', { reason: String(reason || '') });
      await getMonitorDashboardAccessToken({ force: true });

      try { monitorDashboardSocket?.disconnect(); } catch { /* ignore */ }
      monitorDashboardSocket = await connectDashboard();
    } catch (err) {
      console.warn('[bridge] dashboard reconnect failed', { message: err?.message });
    } finally {
      dashboardReconnectInFlight = null;
    }
  })();

  return dashboardReconnectInFlight;
}

function scheduleDashboardReconnect(reason, { forceTokenRefresh = false } = {}) {
  if (dashboardReconnectTimer) {
    return;
  }

  const now = Date.now();
  const sinceLast = now - lastDashboardReconnectAtMs;
  const baseDelay = Math.max(0, DASHBOARD_RECONNECT_MIN_INTERVAL_MS - sinceLast);
  const backoff = Math.min(DASHBOARD_RECONNECT_MAX_BACKOFF_MS, 500 * (2 ** Math.min(10, dashboardReconnectAttempts)));
  const delay = Math.max(baseDelay, backoff);

  dashboardReconnectTimer = setTimeout(async () => {
    dashboardReconnectTimer = null;
    dashboardReconnectAttempts += 1;

    try {
      // Only force mint/refresh when explicitly requested; otherwise rely on cached token to avoid hammering.
      await getMonitorDashboardAccessToken({ force: Boolean(forceTokenRefresh) });
      await reconnectDashboardWithFreshToken(reason);
    } catch {
      // ignore
    }
  }, delay);

  try { dashboardReconnectTimer.unref?.(); } catch { /* ignore */ }
}

async function connectDashboard() {
  const accessToken = await getMonitorDashboardAccessToken({ force: false });

  if (!accessToken) {
    console.warn('[bridge] dashboard auth missing: set up monitor-dashboard credential (DB) or MONITOR_SERVER_DASHBOARD_TOKEN');
  }
  
const auth = await authenticate();

  const extraHeaders = {};
  if (accessToken) {
    // Primary server path is cookie-based OIDC session (`appSession`), so for debugging we
    // also try sending our token as that cookie value. We still keep Bearer auth as a fallback.
    extraHeaders.Authorization = `Bearer ${accessToken}`;
  }

  const socket = io(`${MONITOR_URL}/dashboard`, {
    transports: ['websocket'],
    reconnection: true,
    timeout: 10_000,
    // /dashboard requires OIDC auth (cookie or access token).
    // We pass the token via Socket.IO handshake auth and an Authorization header fallback.
    ...(accessToken ? { auth: { accessToken }, extraHeaders } : {}),
    query: { clientId: CLIENT_ID, ts: auth.ts, sig: auth.sig },
  });

  if (BRIDGE_LOG_SOCKET_EVENTS) {
    socket.onAny((event, ...args) => logSocketEvent({ source: 'monitor_dashboard', event, socket_id: socket.id, args }));
  }

  socket.on('connect', () => {
    bridgeState.monitor_connected = true;
    bridgeState.monitor_socket_id = socket.id;
    console.log('[bridge] connected dashboard', { 
      id: socket.id,
      url: MONITOR_URL,
      timestamp: new Date().toISOString(),
    });
    dashboardReconnectAttempts = 0;
    emitBridgeStatus();
  });
  socket.on('disconnect', (reason) => {
    bridgeState.monitor_connected = false;
    bridgeState.monitor_socket_id = null;
    console.warn('[bridge] dashboard disconnected', { 
      reason: String(reason || 'unknown'),
      socket_id: socket.id,
      timestamp: new Date().toISOString(),
      reconnect_attempts: dashboardReconnectAttempts,
    });
    emitBridgeStatus();
  });
  socket.on('connect_error', async (err) => {
    const msg = String(err?.message || '');
    console.warn('[bridge] dashboard connect_error (bridge -> server.mjs)', { 
      message: msg,
      url: MONITOR_URL,
      timestamp: new Date().toISOString(),
      has_token: Boolean(accessToken),
      token_expiring: accessToken ? isJwtExpiredOrExpiring(accessToken) : false,
    });

    // If token is expired/expiring or server says unauthorized, refresh and reconnect (rate-limited/backed off).
    const force = Boolean(accessToken && (isJwtExpiredOrExpiring(accessToken) || isAuthErrorMessage(msg)));
    if (force || isAuthErrorMessage(msg)) {
      console.log('[bridge] scheduling dashboard reconnect', { 
        reason: `connect_error:${msg}`,
        force_token_refresh: force,
      });
      scheduleDashboardReconnect(`connect_error:${msg}`, { forceTokenRefresh: force });
    }
  });

  socket.on('client_list', (msg) => {
    markMonitorEvent();
    uiNamespace.emit('client_list', msg);

    // Ingest: fan out a single client_list to per-client payloads so Spork can attach telemetry.
    try {
      const list = Array.isArray(msg?.clientIds) ? msg.clientIds : [];
      console.log('[bridge] client_list received', { 
        total_clients: list.length, 
        timestamp: msg?.timestamp || null 
      });
      
      for (const entry of list) {
        const clientId = typeof entry?.clientId === 'string' ? entry.clientId : null;
        if (!clientId) continue;
        
        console.log('[bridge] discovered device', { 
          clientId, 
          machine_id: entry?.machine_id || entry?.machineId || null,
          provider_server_id: entry?.provider_server_id || entry?.providerServerId || null,
        });
        
        ingest('client_list', { clientId, data: entry, timestamp: msg?.timestamp || null });
      }
    } catch (err) {
      console.warn('[bridge] client_list processing error', { message: err?.message });
    }
  });
  socket.on('stats', (msg) => {
    markMonitorEvent();
    uiNamespace.emit('stats', msg);
    
    const clientId = msg?.clientId || msg?.client_id || null;
    if (clientId) {
      console.log('[bridge] stats received', { clientId });
    }
    
    ingest('stats', msg);
  });
  socket.on('command_output', (msg) => {
    markMonitorEvent();
    uiNamespace.emit('command_output', msg);
  });
  socket.on('command_complete', (msg) => {
    markMonitorEvent();
    uiNamespace.emit('command_complete', msg);
  });

  // PTY flow (dashboard side)
  socket.on('shell_started', (msg) => {
    markMonitorEvent();
    const clientId = msg?.clientId;
    const session = msg?.session;
    if (clientId && pendingShellStartSocketIds.has(clientId)) {
      const queue = pendingShellStartSocketIds.get(clientId) || [];
      const socketId = queue.shift();
      if (queue.length) pendingShellStartSocketIds.set(clientId, queue); else pendingShellStartSocketIds.delete(clientId);

      if (socketId) {
        if (session) shellSessionOwnerSocketId.set(String(session), socketId);
        if (session) startShellFirstOutputWatchdog({ session, clientId });
        const target = uiNamespace.sockets.get(socketId);
        if (target) {
          target.emit('shell_started', msg);
          return;
        }
      }
    }
    if (session) startShellFirstOutputWatchdog({ session, clientId });
    uiNamespace.emit('shell_started', msg);
  });
  socket.on('shell_output', (msg) => {
    markMonitorEvent();
    const session = msg?.session;
    if (session) clearShellFirstOutputWatchdog(session);
    const socketId = session ? shellSessionOwnerSocketId.get(String(session)) : null;
    const target = socketId ? uiNamespace.sockets.get(socketId) : null;
    if (target) target.emit('shell_output', msg);
    else uiNamespace.emit('shell_output', msg);
  });
  socket.on('shell_closed', (msg) => {
    markMonitorEvent();
    const session = msg?.session;
    if (session) clearShellFirstOutputWatchdog(session);
    const socketId = session ? shellSessionOwnerSocketId.get(String(session)) : null;
    if (session) shellSessionOwnerSocketId.delete(String(session));
    const target = socketId ? uiNamespace.sockets.get(socketId) : null;
    if (target) target.emit('shell_closed', msg);
    else uiNamespace.emit('shell_closed', msg);
  });
  socket.on('shell_error', (msg) => {
    markMonitorEvent();
    uiNamespace.emit('shell_error', msg);
  });

  return socket;
}

async function connectAgents() {
  if (!AGENT_AUTH_TOKEN) {
    console.warn('[bridge] MONITOR_SERVER_AUTH_TOKEN missing; skipping /agents connection');
    return null;
  }

  return;
  const auth = await authenticate();

  const query = { clientId: auth.clientId, ts: auth.ts, sig: auth.sig };
  const socket = io(`${MONITOR_URL}/agents`, {
    transports: ['websocket'],
    reconnection: true,
    timeout: 10_000,
    query,
    extraHeaders: { Authorization: `Bearer ${AGENT_AUTH_TOKEN}` },
  });

  if (BRIDGE_LOG_SOCKET_EVENTS) {
    socket.onAny((event, ...args) => logSocketEvent({ source: 'monitor_agents', event, socket_id: socket.id, args }));
  }

  socket.on('connect', () => console.log('[bridge] connected agents', { id: socket.id, clientId: CLIENT_ID }));
  socket.on('disconnect', (reason) => console.log('[bridge] agents disconnected', { reason }));
  socket.on('connect_error', (err) => console.warn('[bridge] agents connect_error', { message: err.message }));

  // Keepalive: backup-server-access/server.mjs periodically emits a 'ping' event to agents and expects a 'pong'
  // response to keep lastPong fresh (and avoid disconnecting the client as timed out).
  socket.on('ping', (msg) => {
    try {
      socket.emit('pong', { ts: Date.now(), ...(msg && typeof msg === 'object' ? { serverTs: msg.ts } : {}) });
    } catch {
      // ignore
    }
  });

  socket.on('stats', (msg) => {
    uiNamespace.emit('agent_stats', msg);
    const clientId = msg?.clientId || msg?.client_id || null;
    if (clientId) {
      console.log('[bridge] agent_stats received', { clientId });
    }
    ingest('agent_stats', msg);
  });
  socket.on('net_status', (msg) => {
    uiNamespace.emit('net_status', msg);
    const clientId = msg?.clientId || msg?.client_id || null;
    if (clientId) {
      console.log('[bridge] net_status received', { clientId });
    }
    ingest('net_status', msg);
  });
  socket.on('pong', (msg) => {
    uiNamespace.emit('pong', msg);
    const clientId = msg?.clientId || msg?.client_id || null;
    if (clientId) {
      console.log('[bridge] pong received', { clientId });
    }
    ingest('pong', msg);
  });
  socket.on('admin_result', (msg) => uiNamespace.emit('admin_result', msg));

  socket.on('shell_output', (msg) => uiNamespace.emit('agent_shell_output', msg));
  socket.on('shell_closed', (msg) => uiNamespace.emit('agent_shell_closed', msg));
  socket.on('shell_exit', (msg) => uiNamespace.emit('agent_shell_exit', msg));

  return socket;
}

console.log('[bridge] starting', {
  monitorUrl: MONITOR_URL,
  ingestUrl: SPORK_INGEST_URL || null,
  clientId: CLIENT_ID,
  timestamp: new Date().toISOString(),
});

// Verify credentials are available at startup
(async () => {
  // Check bridge credential (for ingest authentication)
  const bridgeCred = await getBridgeCredential({ force: true });
  if (bridgeCred?.api_key) {
    console.log('[bridge] bridge credential verified', {
      credential_id: bridgeCred.id,
      user_id: bridgeCred.user_id,
      has_token: Boolean(bridgeCred.api_key),
    });
  } else {
    console.error('[bridge] bridge credential missing or invalid', {
      credential_id: bridgeCred?.id || null,
      user_id: bridgeCred?.user_id || null,
      message: 'Server syncs will fail until a valid monitor-bridge credential is created',
      hint: 'Run: php artisan infrastructure:monitor-bridge-credential',
    });
  }

  // Check dashboard credential (for OAuth JWT)
  const dashboardCred = await getDashboardCredential({ force: true });
  if (dashboardCred) {
    const hasClientId = Boolean(dashboardCred.api_key || dashboardCred.settings?.client_id);
    const hasClientSecret = Boolean(dashboardCred.secret_key || dashboardCred.settings?.client_secret);
    const hasTokenUrl = Boolean(dashboardCred.settings?.oauth_token_url || dashboardCred.settings?.token_url);
    const hasAccessToken = Boolean(dashboardCred.access_token);

    if (hasClientId && hasClientSecret && hasTokenUrl) {
      console.log('[bridge] dashboard credential verified', {
        credential_id: dashboardCred.id,
        user_id: dashboardCred.user_id,
        has_oauth_config: true,
        has_access_token: hasAccessToken,
        token_url: dashboardCred.settings?.oauth_token_url || dashboardCred.settings?.token_url || null,
      });
    } else {
      console.error('[bridge] dashboard credential missing OAuth configuration', {
        credential_id: dashboardCred.id,
        user_id: dashboardCred.user_id,
        has_client_id: hasClientId,
        has_client_secret: hasClientSecret,
        has_token_url: hasTokenUrl,
        message: 'Dashboard connection will fail until OAuth is configured',
        hint: 'Run: php artisan infrastructure:monitor-dashboard-credential (reads from OIDC_* env vars)',
      });
    }
  } else {
    console.error('[bridge] dashboard credential missing', {
      message: 'Dashboard connection will fail until monitor-dashboard credential is created',
      hint: 'Run: php artisan infrastructure:monitor-dashboard-credential (reads from MONITOR_DASHBOARD_OAUTH_* env vars)',
    });
  }
})();

const httpServer = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ ok: true, service: 'infrastructure-monitor-bridge' }));
});

const ioServer = new IOServer(httpServer, {
  transports: ['websocket'],
  serveClient: false,
  cors: {
    origin: (origin, cb) => {
      if (originAllowed(origin)) return cb(null, true);
      return cb(new Error('origin not allowed'));
    },
    credentials: true,
  },
});

const uiNamespace = ioServer.of('/dashboard');

function shutdown(reason) {
  console.log('[bridge] shutting down', { reason });
  try { monitorDashboardSocket?.disconnect(); } catch { /* ignore */ }
  try { ioServer.close(); } catch { /* ignore */ }
  try { httpServer.close(); } catch { /* ignore */ }
  try { mysqlPool?.end(); } catch { /* ignore */ }
  process.exit(0);
}

process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));

uiNamespace.use(async (socket, next) => {
  try {
    const token = socket.handshake?.auth?.token
      || socket.handshake?.query?.token
      || '';

    const ok = await validateBridgeToken(token);
    if (!ok) {
      return next(new Error('unauthorized'));
    }

    socket.data.authenticated = true;
    return next();
  } catch (err) {
    console.warn('[bridge] ui auth error', { message: err?.message });
    return next(new Error('unauthorized'));
  }
});

uiNamespace.on('connection', (socket) => {
  console.log('[bridge] ui connected', { id: socket.id });
  socket.emit('bridge_status', buildBridgeStatusPayload());
  emitBridgeStatus();

  if (BRIDGE_LOG_SOCKET_EVENTS) {
    socket.onAny((event, ...args) => logSocketEvent({ source: 'bridge_ui', event, socket_id: socket.id, args }));
  }

  socket.on('disconnect', (reason) => {
    console.log('[bridge] ui disconnected', { id: socket.id, reason });
    emitBridgeStatus();
  });

  socket.on('shell_start', ({ clientId } = {}) => {
    const cid = String(clientId || '').trim();
    if (!cid) return;
    if (!monitorDashboardSocket?.connected) {
      socket.emit('shell_error', { message: 'Monitor server is not connected.', clientId: cid });
      return;
    }

    const queue = pendingShellStartSocketIds.get(cid) || [];
    queue.push(socket.id);
    pendingShellStartSocketIds.set(cid, queue);

    monitorDashboardSocket.emit('shell_start', { clientId: cid });
  });

  socket.on('shell_input', ({ session, data } = {}) => {
    const sess = String(session || '').trim();
    if (!sess) return;
    const owner = shellSessionOwnerSocketId.get(sess);
    if (owner && owner !== socket.id) {
      socket.emit('shell_error', { message: 'Not session owner.' });
      return;
    }
    if (!monitorDashboardSocket?.connected) {
      socket.emit('shell_error', { message: 'Monitor server is not connected.' });
      return;
    }
    monitorDashboardSocket.emit('shell_input', { session: sess, data });
  });

  socket.on('shell_close', ({ session } = {}) => {
    const sess = String(session || '').trim();
    if (!sess) return;
    const owner = shellSessionOwnerSocketId.get(sess);
    if (owner && owner !== socket.id) return;
    if (!monitorDashboardSocket?.connected) return;
    monitorDashboardSocket.emit('shell_close', { session: sess });
  });

  socket.on('shell_resize', ({ session, cols, rows } = {}) => {
    const sess = String(session || '').trim();
    if (!sess) return;
    const owner = shellSessionOwnerSocketId.get(sess);
    if (owner && owner !== socket.id) return;
    if (!monitorDashboardSocket?.connected) return;
    monitorDashboardSocket.emit('shell_resize', { session: sess, cols: Number(cols) || 0, rows: Number(rows) || 0 });
  });
});

httpServer.listen(BRIDGE_PORT, BRIDGE_HOST, () => {
  console.log('[bridge] ui socket server listening', { host: BRIDGE_HOST, port: BRIDGE_PORT });
});

setInterval(() => {
  emitBridgeStatus();
}, Number(process.env.BRIDGE_STATUS_INTERVAL_MS || 5000));

// Preflight should never take down the UI server by default; keep it non-fatal unless explicitly required.
// This keeps the dashboard page usable even when auth is misconfigured.
await requireAutHairMachineInfoOk();

monitorDashboardSocket =
  await connectDashboard();
await connectAgents();

// Periodically ensure the dashboard token stays fresh.
// Note: a JWT cannot be "updated in place" on an existing Socket.IO connection,
// so if it's expiring we reconnect to avoid mid-session auth failures.
setInterval(async () => {
  try {
    const token = await getMonitorDashboardAccessToken({ force: false });
    if (!token) return;
    if (!isJwtExpiredOrExpiring(token)) return;
    await reconnectDashboardWithFreshToken('token_expiring');
  } catch (err) {
    // Keep this quiet; we don't want a noisy loop.
  }
}, Number(process.env.DASHBOARD_TOKEN_REFRESH_INTERVAL_MS || 60_000));

async function connectRedis() {
  const url = REDIS_URL || (REDIS_PASSWORD ? `redis://:${encodeURIComponent(REDIS_PASSWORD)}@${REDIS_HOST}:${REDIS_PORT}` : `redis://${REDIS_HOST}:${REDIS_PORT}`);
  const sub = createRedisClient({ url });

  sub.on('error', (err) => console.warn('[bridge] redis error', { message: err?.message }));

  await sub.connect();
  console.log('[bridge] redis connected', { channel: REDIS_CHANNEL });

  await sub.subscribe(REDIS_CHANNEL, (message) => {
    let parsed;
    try {
      parsed = JSON.parse(message);
    } catch {
      return;
    }

    if (!parsed || typeof parsed !== 'object') return;

    if (parsed.type === 'shell_start') {
      if (!monitorDashboardSocket?.connected) return;
      monitorDashboardSocket.emit('shell_start', { clientId: parsed.clientId });
      return;
    }

    if (parsed.type === 'shell_input') {
      if (!monitorDashboardSocket?.connected) return;
      monitorDashboardSocket.emit('shell_input', { session: parsed.monitor_session, data: parsed.data });
    }
  });
}

connectRedis().catch((err) => {
  console.warn('[bridge] redis connect failed', { message: err?.message });
});


