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
const DASHBOARD_AUTH_TOKEN = process.env.MONITOR_SERVER_DASHBOARD_TOKEN || '';
const SPORK_INGEST_URL = process.env.SPORK_INGEST_URL || 'http://echo.kregel.dev/api/infrastructure/monitor/ingest';
const SPORK_INGEST_TOKEN = process.env.SPORK_INGEST_TOKEN || '';

const CLIENT_ID = process.env.MONITOR_BRIDGE_CLIENT_ID || `spork-bridge-${crypto.randomUUID()}`;

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

const bridgeState = {
  monitor_connected: false,
  monitor_socket_id: null,
  last_monitor_event_at: null,
};

/** @type {mysql.Pool | null} */
let mysqlPool = null;

const BRIDGE_CREDENTIAL_TTL_MS = Number(process.env.BRIDGE_CREDENTIAL_TTL_MS || 30_000);

/** @type {{ value: { id: number, user_id: number, api_key: string } | null, fetchedAt: number, inFlight: Promise<any> | null }} */
const cachedBridgeCredential = {
  value: null,
  fetchedAt: 0,
  inFlight: null,
};

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

/**
 * Fetch the newest backup_agent credential (token) from MySQL.
 *
 * @returns {Promise<{ id: number, user_id: number, api_key: string } | null>}
 */
async function fetchBackupAgentCredential() {
  try {
    const pool = getMysqlPool();
    const [rows] = await pool.query(
      `SELECT id, user_id, api_key
       FROM credentials
       WHERE type = 'backup_agent'
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
    const next = await fetchBackupAgentCredential();
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

function hmacSig(clientId, ts, token) {
  const payload = JSON.stringify({ clientId, ts: Number(ts) });
  return crypto.createHmac('sha256', token).update(payload).digest('hex');
}

async function ingest(eventType, payload) {
  if (!SPORK_INGEST_URL || !SPORK_INGEST_TOKEN) return;
  try {
    const res = await fetch(SPORK_INGEST_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authentication': `Bearer ${SPORK_INGEST_TOKEN}`,
      },
      body: JSON.stringify({
        event_type: eventType,
        payload,
        received_at: new Date().toISOString(),
      }),
    });

    if (!res.ok) {
      const text = await res.text().catch(() => '');
      console.warn('[bridge] ingest failed', { status: res.status, text: text.slice(0, 300) });
    }
  } catch (err) {
    console.warn('[bridge] ingest error', { message: err?.message });
  }
}
async function authenticate() {
  const payload = { clientId: CLIENT_ID, ts: Date.now() };
  const sig = createHmac('sha256', AGENT_AUTH_TOKEN).update(JSON.stringify(payload)).digest('hex');
  return { ...payload, sig };
}

async function connectDashboard() {
  const accessToken = String(DASHBOARD_AUTH_TOKEN || '').trim();

  if (!accessToken) {
    console.warn('[bridge] dashboard auth missing: set MONITOR_SERVER_DASHBOARD_TOKEN');
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

  socket.on('connect', () => {
    bridgeState.monitor_connected = true;
    bridgeState.monitor_socket_id = socket.id;
    console.log('[bridge] connected dashboard', { id: socket.id });
    emitBridgeStatus();
  });
  socket.on('disconnect', (reason) => {
    bridgeState.monitor_connected = false;
    bridgeState.monitor_socket_id = null;
    console.log('[bridge] dashboard disconnected', { reason });
    emitBridgeStatus();
  });
  socket.on('connect_error', (err) => console.warn('[bridge] dashboard connect_error', { message: err.message }));

  socket.on('client_list', (msg) => {
    markMonitorEvent();
    uiNamespace.emit('client_list', msg);
  });
  socket.on('stats', (msg) => {
    markMonitorEvent();
    uiNamespace.emit('stats', msg);
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
        const target = uiNamespace.sockets.get(socketId);
        if (target) {
          target.emit('shell_started', msg);
          return;
        }
      }
    }
    uiNamespace.emit('shell_started', msg);
  });
  socket.on('shell_output', (msg) => {
    markMonitorEvent();
    const session = msg?.session;
    const socketId = session ? shellSessionOwnerSocketId.get(String(session)) : null;
    const target = socketId ? uiNamespace.sockets.get(socketId) : null;
    if (target) target.emit('shell_output', msg);
    else uiNamespace.emit('shell_output', msg);
  });
  socket.on('shell_closed', (msg) => {
    markMonitorEvent();
    const session = msg?.session;
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

  const auth = await authenticate();

  const query = { clientId: auth.clientId, ts: auth.ts, sig: auth.sig };
  const socket = io(`${MONITOR_URL}/agents`, {
    transports: ['websocket'],
    reconnection: true,
    timeout: 10_000,
    query,
    extraHeaders: { Authorization: `Bearer ${AGENT_AUTH_TOKEN}` },
  });

  socket.on('connect', () => console.log('[bridge] connected agents', { id: socket.id, clientId: CLIENT_ID }));
  socket.on('disconnect', (reason) => console.log('[bridge] agents disconnected', { reason }));
  socket.on('connect_error', (err) => console.warn('[bridge] agents connect_error', { message: err.message }));

  socket.on('stats', (msg) => uiNamespace.emit('agent_stats', msg));
  socket.on('net_status', (msg) => uiNamespace.emit('net_status', msg));
  socket.on('pong', (msg) => uiNamespace.emit('pong', msg));
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
});

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

monitorDashboardSocket = 
  await connectDashboard();
await connectAgents();

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


