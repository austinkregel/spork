<template>
    <ServerInfrastucture title="SSH Console" :server="server" :navigation="navigation">
        <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-black text-green-400 shadow-inner">
            <div class="px-4 py-2 border-b border-stone-800 flex justify-between text-xs uppercase tracking-widest text-stone-400">
                <span>Interactive shell</span>
                <span>{{ server.ip_address }}</span>
            </div>
            <div class="px-4 py-6">
                <div ref="xterm" class="xterm h-96"></div>
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
import { onMounted, ref, computed } from "vue";
import { buildServerNavigation } from '@/Pages/Infrastructure/serverNavigation';

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

const navigation = computed(() => buildServerNavigation(props.server));
const xterm = ref(null);

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
    term.unicode.activeVersion = '11';
    fitAddon.fit();
    term.writeln('Connecting to agent... (demo output)');
});
</script>
