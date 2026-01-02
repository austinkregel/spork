<template>
    <ServerInfrastucture title="SSH Console" :server="server" :navigation="navigation">
        <div class="space-y-4">
            <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                            Monitor PTY
                        </p>
                        <p class="text-sm text-stone-600 dark:text-stone-300 mt-1">
                            Backed by the central monitor server. This is separate from SSH.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <SporkButton primary :disabled="startingSession || !!monitorSession" @click="startSession">
                            {{ monitorSession ? 'Session active' : (startingSession ? 'Starting…' : 'Start session') }}
                        </SporkButton>
                    </div>
                </div>

                <div v-if="monitorSession" class="mt-4 grid gap-3">
                    <div class="flex gap-3">
                        <SporkInput
                            class="flex-1"
                            v-model="inputBuffer"
                            placeholder="Type a command and press Enter"
                            @keydown.enter.prevent="sendInput"
                        />
                        <SporkButton primary :disabled="!inputBuffer || sendingInput" @click="sendInput">
                            Send
                        </SporkButton>
                    </div>
                </div>

                <div v-if="errorMessage" class="mt-4 text-sm text-red-500 dark:text-red-400">
                    {{ errorMessage }}
                </div>
            </div>

            <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-black text-green-400 shadow-inner">
                <div class="px-4 py-2 border-b border-stone-800 flex justify-between text-xs uppercase tracking-widest text-stone-400">
                    <span>Interactive shell</span>
                    <span>{{ server.ip_address }}</span>
                </div>
                <div class="px-4 py-6">
                    <div ref="xterm" class="xterm h-96"></div>
                </div>
            </div>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import 'xterm/css/xterm.css';
import { Terminal } from 'xterm';
import { FitAddon } from 'xterm-addon-fit';
import { WebLinksAddon } from 'xterm-addon-web-links';
import { Unicode11Addon } from 'xterm-addon-unicode11';
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import { onMounted, onBeforeUnmount, ref, computed } from "vue";
import { buildServerNavigation } from '@/Pages/Infrastructure/serverNavigation';
import { connectMonitorBridge } from '@/composables/useMonitorBridge';

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

const navigation = computed(() => buildServerNavigation(props.server));
const xterm = ref(null);
const termRef = ref(null);

const monitorSession = ref(null);
const startingSession = ref(false);
const sendingInput = ref(false);
const inputBuffer = ref('');
const errorMessage = ref('');

const monitorSocket = ref(null);

const appendOutput = (text) => {
    if (!termRef.value) {
        return;
    }

    termRef.value.write(text);
};

const startSession = async () => {
    errorMessage.value = '';
    startingSession.value = true;

    try {
        const socket = monitorSocket.value;
        if (!socket?.connected) {
            errorMessage.value = 'Bridge is not connected.';
            return;
        }

        // Ask bridge to start shell for hostname=server.name
        socket.emit('shell_start', { clientId: props.server.name });
    } finally {
        startingSession.value = false;
    }
};

const sendInput = async () => {
    if (!monitorSession.value || !inputBuffer.value) {
        return;
    }

    sendingInput.value = true;
    errorMessage.value = '';

    try {
        const socket = monitorSocket.value;
        if (!socket?.connected) {
            errorMessage.value = 'Bridge is not connected.';
            return;
        }

        socket.emit('shell_input', { session: monitorSession.value, data: `${inputBuffer.value}\n` });

        inputBuffer.value = '';
    } finally {
        sendingInput.value = false;
    }
};

onMounted(() => {
    const term = new Terminal({
        allowProposedApi: true,
        fontFamily: '"JetBrains Mono", ui-monospace, SFMono-Regular',
        fontSize: 14,
        theme: {
            background: '#000000',
        },
    });
    const fitAddon = new FitAddon();
    term.loadAddon(fitAddon);
    term.loadAddon(new WebLinksAddon());
    term.loadAddon(new Unicode11Addon());

    term.open(xterm.value);
    termRef.value = term;
    term.unicode.activeVersion = '11';
    fitAddon.fit();

    let socket;

    connectMonitorBridge().then((sio) => {
        socket = sio;
        monitorSocket.value = sio;

        sio.on('connect', () => {
            term.writeln(`[bridge] connected`);
        });

        sio.on('disconnect', (reason) => {
            term.writeln(`[bridge] disconnected: ${reason}`);
            monitorSession.value = null;
        });

        sio.on('shell_started', (msg) => {
        if (msg?.clientId !== props.server.name) {
            return;
        }

        monitorSession.value = msg?.session ?? null;

        if (monitorSession.value) {
            term.writeln(`[bridge] session started: ${monitorSession.value}`);
        }
        });

        sio.on('shell_output', (msg) => {
        if (!monitorSession.value || msg?.session !== monitorSession.value) {
            return;
        }

        appendOutput(msg?.data ?? '');
        });

        sio.on('shell_closed', (msg) => {
        if (!monitorSession.value || msg?.session !== monitorSession.value) {
            return;
        }

        term.writeln(`\r\n[bridge] session closed\r\n`);
        monitorSession.value = null;
        });

        sio.on('shell_error', (msg) => {
        const message = msg?.message ?? 'Unknown error';
        term.writeln(`\r\n[bridge] error: ${message}\r\n`);
        });

        term.writeln('Monitor PTY (via bridge): click “Start session” to begin.');
    }).catch(() => {
        term.writeln(`\r\n[bridge] error: failed to connect\r\n`);
    });
});

onBeforeUnmount(() => {
    // Do not disconnect the shared socket here; other UI elements may use it.
});
</script>
