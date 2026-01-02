<template>
    <section
        class="border border-stone-200 dark:border-stone-800 rounded-lg overflow-hidden shadow-sm"
        :class="session ? 'bg-black' : 'bg-white dark:bg-stone-900'"
        :style="session ? { height: `${panelHeight}px`, minHeight: `${MIN_HEIGHT}px` } : {}"
    >
        <!-- Resize handle (active only when session is running) -->
        <div
            v-if="session"
            class="w-full h-2 cursor-row-resize bg-stone-200 dark:bg-stone-700 hover:bg-stone-300 dark:hover:bg-stone-600 transition-colors"
            title="Drag to resize"
            @mousedown="beginResize"
        />

        <!-- Header -->
        <header
            class="px-4 py-2 border-b border-stone-200 dark:border-stone-800 flex flex-wrap items-center justify-between gap-3"
            :class="session ? 'bg-stone-950 text-stone-200 border-stone-800' : 'bg-white dark:bg-stone-900'"
        >
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-semibold" :class="session ? 'text-stone-100' : 'text-stone-900 dark:text-white'">
                    Interactive shell
                </h2>

                <span class="text-[10px] font-mono text-stone-500 dark:text-stone-400">
                    {{ serverLabel }}
                </span>

                <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                    :class="connectedPillClass"
                >
                    {{ connectedLabel }}
                </span>

                <span v-if="session" class="text-[10px] font-mono text-stone-500 dark:text-stone-400">
                    session: {{ session }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <SporkButton
                    primary
                    xsmall
                    :disabled="starting || !!session || !bridgeConnected"
                    @click="start"
                >
                    {{ session ? 'Session active' : (starting ? 'Starting…' : 'Start session') }}
                </SporkButton>

                <SporkButton
                    v-if="session"
                    secondary
                    xsmall
                    @click="clear"
                >
                    Clear
                </SporkButton>

                <SporkButton
                    v-if="session"
                    secondary
                    xsmall
                    @click="close"
                >
                    Close
                </SporkButton>
            </div>
        </header>

        <!-- Terminal -->
        <div v-if="session" ref="terminalWrapper" class="bg-black h-full overflow-hidden">
            <div ref="termContainer" class="h-full w-full font-hack" />
        </div>

        <!-- Placeholder -->
        <div v-else class="p-6">
            <p class="text-sm text-stone-600 dark:text-stone-300">
                No active shell session. Click “Start session” to open a monitor-backed PTY.
            </p>
            <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">
                Tip: once started, you can type directly into the terminal. Resize the panel by dragging the handle.
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
import 'xterm/css/xterm.css';
import { Terminal } from 'xterm';
import { FitAddon } from 'xterm-addon-fit';
import { WebLinksAddon } from 'xterm-addon-web-links';
import { Unicode11Addon } from 'xterm-addon-unicode11';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import type { Socket } from 'socket.io-client';
import SporkButton from '@/Components/Spork/SporkButton.vue';
import { useMonitorBridge } from '@/composables/useMonitorBridge';

type ShellStartedPayload = { session?: string | null; clientId?: string | null };
type ShellOutputPayload = { session?: string | null; data?: string | null };
type ShellClosedPayload = { session?: string | null; reason?: string | null; code?: number | null };
type ShellErrorPayload = { message?: string | null; clientId?: string | null };

const props = defineProps<{
    clientId: string;
    serverLabel?: string;
}>();

const STORAGE_KEY = 'spork.infrastructure.terminal.height';
const DEFAULT_HEIGHT = 420;
const MIN_HEIGHT = 180;
const MAX_HEIGHT = 1200;

const termContainer = ref<HTMLElement | null>(null);
const terminalWrapper = ref<HTMLElement | null>(null);

const session = ref<string>('');
const starting = ref(false);

let term: Terminal | null = null;
let fitAddon: FitAddon | null = null;
let resizeObserver: ResizeObserver | null = null;
let resizeTimer: number | null = null;
let socket: Socket | null = null;

let handleConnect: (() => void) | null = null;
let handleDisconnect: ((reason: string) => void) | null = null;
let handleShellStarted: ((msg: ShellStartedPayload) => void) | null = null;
let handleShellOutput: ((msg: ShellOutputPayload) => void) | null = null;
let handleShellClosed: ((msg: ShellClosedPayload) => void) | null = null;
let handleShellError: ((msg: ShellErrorPayload) => void) | null = null;

let pendingOutput: string[] = [];
let terminalOpened = false;
let lastOpenedContainer: HTMLElement | null = null;

function clamp(value: number, min: number, max: number): number {
    return Math.min(Math.max(value, min), max);
}

function calcMaxHeight(): number {
    if (typeof window === 'undefined' || !window.innerHeight || window.innerHeight <= 0) {
        return MAX_HEIGHT;
    }

    return Math.min(MAX_HEIGHT, Math.floor(window.innerHeight * 0.7));
}

function loadStoredHeight(): number {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return DEFAULT_HEIGHT;
        }

        const parsed = Number(raw);
        if (!Number.isFinite(parsed)) {
            return DEFAULT_HEIGHT;
        }

        return clamp(parsed, MIN_HEIGHT, calcMaxHeight());
    } catch {
        return DEFAULT_HEIGHT;
    }
}

function storeHeight(value: number) {
    try {
        localStorage.setItem(STORAGE_KEY, String(value));
    } catch {
        // ignore
    }
}

const panelHeight = ref(loadStoredHeight());

const { connected, isMonitorConnected, connect, shellStart, shellInput, shellClose, shellResize } = useMonitorBridge();

const DEBUG = String(import.meta.env.VITE_MONITOR_BRIDGE_DEBUG ?? '').trim() === '1';

function debugLog(message: string, meta?: Record<string, unknown>) {
    if (!DEBUG) {
        return;
    }

    try {
        // eslint-disable-next-line no-console
        console.log(`[MonitorTerminalPanel] ${message}`, meta ?? {});
    } catch {
        // ignore
    }
}

const bridgeConnected = computed(() => connected.value && isMonitorConnected.value);
const connectedLabel = computed(() => {
    if (!connected.value) {
        return 'Bridge offline';
    }

    if (!isMonitorConnected.value) {
        return 'Monitor offline';
    }

    return 'Online';
});

const connectedPillClass = computed(() => {
    if (!connected.value) {
        return 'border-red-300 text-red-700 bg-red-50 dark:border-red-600 dark:text-red-200 dark:bg-red-900/30';
    }

    if (!isMonitorConnected.value) {
        return 'border-amber-300 text-amber-700 bg-amber-50 dark:border-amber-600 dark:text-amber-200 dark:bg-amber-900/30';
    }

    return 'border-green-300 text-green-700 bg-green-50 dark:border-green-600 dark:text-green-200 dark:bg-green-900/30';
});

const serverLabel = computed(() => props.serverLabel || props.clientId);

function initTerminal() {
    if (!termContainer.value) {
        return;
    }

    // If the session container was torn down (v-if) and recreated, we must recreate xterm.
    // Otherwise it remains bound to a detached DOM node and appears "blank".
    if (term && lastOpenedContainer && lastOpenedContainer !== termContainer.value) {
        destroyTerminal();
    }

    if (term) {
        return;
    }

    term = new Terminal({
        allowProposedApi: true,
        convertEol: true,
        scrollback: 2000,
        fontSize: 13,
        theme: {
            background: '#000000',
            foreground: '#e5e7eb',
            cursor: '#e5e7eb',
        },
    });

    fitAddon = new FitAddon();
    term.loadAddon(fitAddon);
    term.loadAddon(new WebLinksAddon());
    term.loadAddon(new Unicode11Addon());
    term.unicode.activeVersion = '11';

    const tryOpenTerminal = () => {
        if (!term || !termContainer.value || terminalOpened) {
            return;
        }

        const container = termContainer.value;

        // Ensure container is visible and has dimensions before opening.
        // If we open too early, xterm can report cols/rows as 0 and we won't be able to send
        // the initial `shell_resize`, which some agents rely on to begin output.
        if (container.offsetWidth === 0 || container.offsetHeight === 0) {
            window.setTimeout(tryOpenTerminal, 100);
            return;
        }

        try {
            term.open(container);
            terminalOpened = true;
            lastOpenedContainer = container;
            fitAddon?.fit();

            if (pendingOutput.length) {
                try {
                    term.write(pendingOutput.join(''));
                } finally {
                    pendingOutput = [];
                }
            }

            // Immediately attempt to fit+resize once xterm is mounted.
            scheduleFitAndResize();
        } catch {
            // If xterm wasn't ready to open (rare), retry once more shortly.
            window.setTimeout(tryOpenTerminal, 100);
        }
    };

    requestAnimationFrame(tryOpenTerminal);

    term.onData((data) => {
        if (!session.value) {
            return;
        }

        // Mirror the authoritative dashboard client behavior:
        // normalize Enter (`\\r`) to newline (`\\n`) for the monitor PTY.
        let payload = data;
        if (payload === '\r') {
            payload = '\n';
        }

        shellInput(session.value, payload).catch(() => {
            // error UI is handled via socket events; keep typing resilient
        });
    });
}

function destroyTerminal() {
    try {
        term?.dispose();
    } catch {
        // ignore
    }

    term = null;
    fitAddon = null;
    terminalOpened = false;
    lastOpenedContainer = null;
    pendingOutput = [];

    // Best-effort: clear any detached DOM remnants.
    try {
        if (termContainer.value) {
            termContainer.value.innerHTML = '';
        }
    } catch {
        // ignore
    }
}

function scheduleFitAndResize() {
    if (!term || !fitAddon) {
        return;
    }

    if (resizeTimer) {
        window.clearTimeout(resizeTimer);
    }

    resizeTimer = window.setTimeout(() => {
        try {
            fitAddon?.fit();
        } catch {
            return;
        }

        if (!session.value || !term) {
            return;
        }

        const cols = term.cols || 0;
        const rows = term.rows || 0;

        if (cols <= 0 || rows <= 0) {
            // Container may not have layout yet; retry shortly.
            window.setTimeout(() => scheduleFitAndResize(), 120);
            return;
        }

        debugLog('emit shell_resize', { session: session.value, cols, rows });
        shellResize(session.value, cols, rows).catch(() => {
            // ignore
        });
    }, 150);
}

function writeLine(text: string) {
    if (!term) {
        pendingOutput.push(`${text}\r\n`);
        return;
    }

    term.writeln(text);
}

async function start() {
    if (!props.clientId || starting.value || session.value) {
        return;
    }

    starting.value = true;

    try {
        await shellStart(props.clientId);
    } finally {
        starting.value = false;
    }
}

function clear() {
    term?.clear();
}

async function close() {
    if (!session.value) {
        return;
    }

    try {
        await shellClose(session.value);
    } catch {
        // ignore
    }
}

// Panel resizing
let resizing = false;
let resizeStartY = 0;
let resizeStartHeight = 0;

function beginResize(e: MouseEvent) {
    e.preventDefault();
    resizing = true;
    resizeStartY = e.clientY;
    resizeStartHeight = panelHeight.value;

    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', endResize, { once: true });
}

function onDragMove(e: MouseEvent) {
    if (!resizing) {
        return;
    }

    const deltaY = resizeStartY - e.clientY;
    panelHeight.value = clamp(resizeStartHeight + deltaY, MIN_HEIGHT, calcMaxHeight());

    scheduleFitAndResize();
}

function endResize() {
    resizing = false;
    document.removeEventListener('mousemove', onDragMove);
    storeHeight(panelHeight.value);
    scheduleFitAndResize();
}

onMounted(async () => {
    try {
        // IMPORTANT: use the shared singleton socket from the composable so emits/listeners
        // always happen on the same underlying connection.
        socket = await connect();
    } catch {
        // still render; connected pill will reflect offline
        socket = null;
    }

    // Ensure we have a terminal instance ready to receive output.
    initTerminal();

    // Initial guidance
    writeLine('Monitor PTY (via bridge): click “Start session” to begin.');

    handleConnect = () => {
        writeLine('[bridge] connected');
    };

    handleDisconnect = (reason: string) => {
        writeLine(`[bridge] disconnected: ${reason}`);
        session.value = '';
        destroyTerminal();
    };

    handleShellStarted = async (msg: ShellStartedPayload) => {
        if (msg?.clientId !== props.clientId) {
            return;
        }

        session.value = String(msg?.session ?? '').trim();

        if (session.value) {
            // Wait for the terminal container to exist (it is behind v-if="session").
            await nextTick();
            initTerminal();
            writeLine(`[bridge] session started: ${session.value}`);

            // Give the terminal a moment to fully lay out before sending initial resize.
            // This matches the behavior of backup-server-access/client TerminalPanel.vue.
            window.setTimeout(() => scheduleFitAndResize(), 300);
        }
    };

    handleShellOutput = (msg: ShellOutputPayload) => {
        if (!session.value || String(msg?.session ?? '') !== session.value) {
            return;
        }

        const data = String(msg?.data ?? '');
        if (!data) {
            return;
        }

        if (term) {
            term.write(data);
        } else {
            pendingOutput.push(data);
        }
    };

    handleShellClosed = (msg: ShellClosedPayload) => {
        if (!session.value || String(msg?.session ?? '') !== session.value) {
            return;
        }

        const reason = msg?.reason ? ` (${msg.reason})` : '';
        writeLine(`\r\n[bridge] session closed${reason}\r\n`);
        session.value = '';
        destroyTerminal();
    };

    handleShellError = (msg: ShellErrorPayload) => {
        const cid = String(msg?.clientId ?? '').trim();
        if (cid && cid !== props.clientId) {
            return;
        }

        const message = msg?.message ?? 'Unknown error';
        writeLine(`\r\n[bridge] error: ${message}\r\n`);
    };

    if (socket) {
        if (handleConnect) socket.on('connect', handleConnect);
        if (handleDisconnect) socket.on('disconnect', handleDisconnect);
        if (handleShellStarted) socket.on('shell_started', handleShellStarted);
        if (handleShellOutput) socket.on('shell_output', handleShellOutput);
        if (handleShellClosed) socket.on('shell_closed', handleShellClosed);
        if (handleShellError) socket.on('shell_error', handleShellError);
    }

    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(() => {
            scheduleFitAndResize();
        });

        if (terminalWrapper.value) {
            resizeObserver.observe(terminalWrapper.value);
        }
    }

    window.addEventListener('resize', scheduleFitAndResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', scheduleFitAndResize);

    if (resizeTimer) {
        window.clearTimeout(resizeTimer);
        resizeTimer = null;
    }

    resizeObserver?.disconnect();
    resizeObserver = null;

    if (socket) {
        if (handleConnect) socket.off('connect', handleConnect);
        if (handleDisconnect) socket.off('disconnect', handleDisconnect);
        if (handleShellStarted) socket.off('shell_started', handleShellStarted);
        if (handleShellOutput) socket.off('shell_output', handleShellOutput);
        if (handleShellClosed) socket.off('shell_closed', handleShellClosed);
        if (handleShellError) socket.off('shell_error', handleShellError);
    }
});
</script>


