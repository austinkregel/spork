<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    timestamp?: string | number | null;
    onlineThresholdMs?: number;
}>();

const ONLINE_THRESHOLD_MS = computed(() => props.onlineThresholdMs ?? 300_000); // 5 minutes
const now = ref(Date.now());
let timer: number | null = null;

onMounted(() => {
    timer = window.setInterval(() => {
        now.value = Date.now();
    }, 5000);
});

onUnmounted(() => {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }
});

const lastTimestampMs = computed(() => {
    const val = props.timestamp;
    if (typeof val === 'number') {
        return val;
    }
    if (typeof val === 'string') {
        const ms = Date.parse(val);
        return Number.isNaN(ms) ? 0 : ms;
    }
    return 0;
});

const online = computed(() => lastTimestampMs.value && (now.value - lastTimestampMs.value < ONLINE_THRESHOLD_MS.value));
const lastSeenSeconds = computed(() =>
    lastTimestampMs.value ? Math.floor((now.value - lastTimestampMs.value) / 1000) : null
);
const tooltip = computed(() => {
    if (!lastTimestampMs.value) {
        return 'No data received yet';
    }
    return `Last seen ${lastSeenSeconds.value}s ago`;
});
</script>

<template>
    <span
        v-if="online"
        :title="tooltip"
        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300"
    >
        Online
    </span>
    <span
        v-else
        :title="tooltip"
        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300"
    >
        Offline
    </span>
</template>




