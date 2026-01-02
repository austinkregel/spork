<template>
    <div
        class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 shadow-sm p-3"
        :title="tooltip"
    >
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                    Bridge
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full" :class="dotClass" />
                    <span class="text-sm font-semibold" :class="labelClass">
                        {{ statusLabel }}
                    </span>
                </span>
            </div>
            <span class="text-xs text-stone-500 dark:text-stone-400">
                UI: {{ uiClientCount }}
            </span>
        </div>
        <div class="mt-2 text-xs text-stone-500 dark:text-stone-400">
            Monitor: <span class="font-medium">{{ monitorLabel }}</span>
            <span v-if="lastEventAt"> · last event {{ lastEventAt }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { useMonitorBridge } from '@/composables/useMonitorBridge';

dayjs.extend(relativeTime);

const { connect, connected, bridgeStatus, isMonitorConnected, uiClientCount } = useMonitorBridge();

onMounted(() => {
    connect().catch(() => {
        // status UI will reflect disconnected
    });
});

const statusLabel = computed(() => {
    if (!connected.value) {
        return 'Offline';
    }

    if (!isMonitorConnected.value) {
        return 'Degraded';
    }

    return 'Online';
});

const monitorLabel = computed(() => (isMonitorConnected.value ? 'connected' : 'disconnected'));

const dotClass = computed(() => {
    if (!connected.value) {
        return 'bg-red-500';
    }

    if (!isMonitorConnected.value) {
        return 'bg-amber-400';
    }

    return 'bg-green-500';
});

const labelClass = computed(() => {
    if (!connected.value) {
        return 'text-red-600 dark:text-red-400';
    }

    if (!isMonitorConnected.value) {
        return 'text-amber-700 dark:text-amber-300';
    }

    return 'text-green-700 dark:text-green-300';
});

const lastEventAt = computed(() => {
    const ts = bridgeStatus.value?.last_monitor_event_at;
    if (!ts) {
        return null;
    }

    try {
        return dayjs(ts).fromNow();
    } catch {
        return ts;
    }
});

const tooltip = computed(() => {
    const parts = [
        `UI socket: ${connected.value ? 'connected' : 'disconnected'}`,
        `Monitor: ${monitorLabel.value}`,
    ];

    const last = bridgeStatus.value?.last_monitor_event_at;
    if (last) {
        parts.push(`Last event: ${last}`);
    }

    return parts.join(' · ');
});
</script>




