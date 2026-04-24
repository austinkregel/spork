<template>
    <ServerInfrastucture title="SSH Console" :server="server" :navigation="navigation">
        <div class="space-y-4">
            <GlassCard
                title="Backed by the central monitor server. This is separate from SSH."
                subtitle="Monitor PTY"
            />

            <MonitorTerminalPanel
                :client-id="monitorClientId"
                :server-label="server.ip_address ?? server.name"
            />
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
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

const monitorClientId = computed(() => {
    const telemetry = props.server?.telemetry;
    const payload = telemetry?.payload;
    const cid = payload?.clientId ?? payload?.client_id ?? payload?.client ?? null;
    return (typeof cid === 'string' && cid.trim()) ? cid.trim() : props.server.name;
});
</script>
