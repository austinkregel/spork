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
                </div>
            </div>

            <MonitorTerminalPanel
                :client-id="monitorClientId"
                :server-label="server.ip_address ?? server.name"
            />
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import MonitorTerminalPanel from "@/Components/Infrastructure/MonitorTerminalPanel.vue";
import { computed } from "vue";
import { buildServerNavigation } from '@/Pages/Infrastructure/serverNavigation';

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

const navigation = computed(() => buildServerNavigation(props.server));

// The bridge/monitor uses agent clientId (usually hostname) as the authoritative identifier for PTY sessions.
// Prefer the most recently ingested telemetry clientId, falling back to the server name.
const monitorClientId = computed(() => {
    const telemetry = props.server?.telemetry;
    const payload = telemetry?.payload;
    const cid = payload?.clientId ?? payload?.client_id ?? payload?.client ?? null;
    return (typeof cid === 'string' && cid.trim()) ? cid.trim() : props.server.name;
});
</script>
