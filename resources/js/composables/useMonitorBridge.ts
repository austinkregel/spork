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
    if (socket?.connected) {
        return socket;
    }

    const token = await fetchBridgeToken();
    const baseUrl = getBridgeBaseUrl();

    socket = io(`${baseUrl}/dashboard`, {
        transports: ['websocket'],
        auth: { token },
    });

    socket.on('connect', () => {
        connected.value = true;
    });

    socket.on('disconnect', () => {
        connected.value = false;
    });

    socket.on('connect_error', () => {
        connected.value = false;
    });

    socket.on('bridge_status', (payload: BridgeStatus) => {
        bridgeStatus.value = payload;
    });

    socket.on('client_list', (payload: ClientListPayload) => {
        clientList.value = payload;
    });

    socket.on('stats', (payload: StatsPayload) => {
        if (!payload?.clientId || !payload?.data) {
            return;
        }

        statsMap[payload.clientId] = payload.data;
    });

    return socket;
}

export function useMonitorBridge() {
    const isMonitorConnected = computed(() => bridgeStatus.value?.monitor_connected ?? false);
    const uiClientCount = computed(() => bridgeStatus.value?.ui_clients_count ?? 0);

    const shellStart = async (clientId: string) => {
        const sio = await connectMonitorBridge();
        sio.emit('shell_start', { clientId });
    };

    const shellInput = async (session: string, data: string) => {
        const sio = await connectMonitorBridge();
        sio.emit('shell_input', { session, data });
    };

    const shellClose = async (session: string) => {
        const sio = await connectMonitorBridge();
        sio.emit('shell_close', { session });
    };

    const shellResize = async (session: string, cols: number, rows: number) => {
        const sio = await connectMonitorBridge();
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





