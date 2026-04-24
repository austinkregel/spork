import { computed, reactive, ref } from 'vue';
import { io, Socket } from 'socket.io-client';

type BridgeStatus = {
    monitor_connected: boolean;
    monitor_socket_id: string | null;
    ui_clients_count: number;
    last_monitor_event_at: string | null;
    ts: string;
};

type ClientListPayload = {
    clientIds?: Array<{ clientId: string; lastPong?: number; authenticated?: boolean; agentVersion?: string }>;
    timestamp?: string;
};

type StatsPayload = {
    clientId?: string;
    data?: Record<string, unknown>;
};

const connected = ref(false);
const bridgeStatus = ref<BridgeStatus | null>(null);
const clientList = ref<ClientListPayload | null>(null);
const statsMap = reactive<Record<string, Record<string, unknown>>>({});

let tokenPromise: Promise<string> | null = null;
let socket: Socket | null = null;
let socketPromise: Promise<Socket> | null = null;

const DEBUG = String(import.meta.env.VITE_MONITOR_BRIDGE_DEBUG ?? '').trim() === '1';

function debugLog(message: string, meta?: Record<string, unknown>) {
    if (!DEBUG) {
        return;
    }

    try {
        // eslint-disable-next-line no-console
        console.log(`[monitor-bridge] ${message}`, meta ?? {});
    } catch {
        // ignore
    }
}

async function fetchBridgeToken(): Promise<string> {
    if (tokenPromise) {
        return tokenPromise;
    }

    tokenPromise = (async () => {
        const res = await fetch('/api/infrastructure/monitor-bridge/token', {
            method: 'GET',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!res.ok) {
            tokenPromise = null;
            throw new Error(`Failed to fetch bridge token (${res.status})`);
        }

        const json = await res.json();
        const token = String(json?.token ?? '').trim();

        if (!token) {
            tokenPromise = null;
            throw new Error('Bridge token missing from response');
        }

        return token;
    })();

    return tokenPromise;
}

function getBridgeBaseUrl(): string {
    const url = import.meta.env.VITE_MONITOR_BRIDGE_URL;
    if (typeof url === 'string' && url.trim()) {
        return url.trim().replace(/\/$/, '');
    }

    const envPort = Number.parseInt(String(import.meta.env.VITE_MONITOR_BRIDGE_PORT ?? ''), 10);
    const port = Number.isFinite(envPort) ? envPort : 6002;

    // Local fallback: same host, bridge port.
    // This avoids accidentally connecting to the app origin (often :80/:443) when the bridge runs separately.
    return `${window.location.protocol}//${window.location.hostname}:${port}`;
}

export async function connectMonitorBridge(): Promise<Socket> {
    // Important: treat the socket as a singleton even while it is still connecting.
    // If we only return it when `.connected` is true, callers can race and create a
    // second socket, leading to "emit on socket B while listening on socket A".
    if (socket) {
        return socket;
    }

    if (socketPromise) {
        return await socketPromise;
    }

    socketPromise = (async () => {
        const token = await fetchBridgeToken();
        const baseUrl = getBridgeBaseUrl();

        const sio = io(`${baseUrl}/dashboard`, {
            transports: ['websocket'],
            auth: { token },
        });

        socket = sio;

        sio.on('connect', () => {
            connected.value = true;
            debugLog('connected', { id: sio.id, url: baseUrl });
        });

        sio.on('disconnect', (reason) => {
            connected.value = false;
            debugLog('disconnected', { reason });
        });

        sio.on('connect_error', (err) => {
            connected.value = false;
            debugLog('connect_error', { message: String((err as Error | undefined)?.message ?? '') });
        });

        sio.on('bridge_status', (payload: BridgeStatus) => {
            bridgeStatus.value = payload;
        });

        sio.on('client_list', (payload: ClientListPayload) => {
            clientList.value = payload;
        });

        sio.on('stats', (payload: StatsPayload) => {
            if (!payload?.clientId || !payload?.data) {
                return;
            }

            statsMap[payload.clientId] = payload.data;
        });

        // Optional debug instrumentation (very helpful for "Start session does nothing")
        // Keep payloads minimal to avoid dumping terminal output.
        sio.on('shell_started', (payload: unknown) => {
            const msg = payload as { session?: string | null; clientId?: string | null } | null;
            debugLog('recv shell_started', { clientId: msg?.clientId ?? null, session: msg?.session ?? null });
        });
        sio.on('shell_output', (payload: unknown) => {
            const msg = payload as { session?: string | null; data?: string | null } | null;
            debugLog('recv shell_output', { session: msg?.session ?? null, bytes: String(msg?.data ?? '').length });
        });
        sio.on('shell_closed', (payload: unknown) => {
            const msg = payload as { session?: string | null; reason?: string | null; code?: number | null } | null;
            debugLog('recv shell_closed', { session: msg?.session ?? null, reason: msg?.reason ?? null, code: msg?.code ?? null });
        });
        sio.on('shell_error', (payload: unknown) => {
            const msg = payload as { message?: string | null; clientId?: string | null } | null;
            debugLog('recv shell_error', { clientId: msg?.clientId ?? null, message: msg?.message ?? null });
        });

        return sio;
    })();

    try {
        return await socketPromise;
    } finally {
        socketPromise = null;
    }
}

export function useMonitorBridge() {
    const isMonitorConnected = computed(() => bridgeStatus.value?.monitor_connected ?? false);
    const uiClientCount = computed(() => bridgeStatus.value?.ui_clients_count ?? 0);

    const shellStart = async (clientId: string) => {
        const sio = await connectMonitorBridge();
        debugLog('emit shell_start', { clientId });
        sio.emit('shell_start', { clientId });
    };

    const shellInput = async (session: string, data: string) => {
        const sio = await connectMonitorBridge();
        debugLog('emit shell_input', { session, bytes: data?.length ?? 0 });
        sio.emit('shell_input', { session, data });
    };

    const shellClose = async (session: string) => {
        const sio = await connectMonitorBridge();
        debugLog('emit shell_close', { session });
        sio.emit('shell_close', { session });
    };

    const shellResize = async (session: string, cols: number, rows: number) => {
        const sio = await connectMonitorBridge();
        debugLog('emit shell_resize', { session, cols, rows });
        sio.emit('shell_resize', { session, cols, rows });
    };

    return {
        connected,
        bridgeStatus,
        clientList,
        statsMap,
        isMonitorConnected,
        uiClientCount,
        connect: connectMonitorBridge,
        shellStart,
        shellInput,
        shellClose,
        shellResize,
    };
}





