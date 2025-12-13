<template>
    <ServerInfrastucture title="Logs" :server="server" :navigation="navigation">
        <div class="space-y-6">
            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-black text-green-400 shadow-inner">
                <header class="px-4 py-2 border-b border-stone-800 flex items-center justify-between text-xs uppercase tracking-widest text-stone-400">
                    <span>Live tail</span>
                    <span>{{ server.ip_address }}</span>
                </header>
                <div class="px-4 py-6 h-80 overflow-y-auto font-mono text-xs leading-relaxed">
                    <p v-for="line in logLines" :key="line.id">
                        <span class="text-stone-500">{{ line.timestamp }}</span>
                        <span class="ml-2">{{ line.message }}</span>
                    </p>
                    <p v-if="!logLines.length">No log entries streamed yet.</p>
                </div>
            </section>

            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm">
                <header class="mb-4">
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                        Saved snapshots
                    </p>
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-white">
                        Recent log bundles
                    </h2>
                </header>
                <ul class="divide-y divide-stone-200 dark:divide-stone-800">
                    <li v-for="bundle in bundles" :key="bundle.id" class="py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-semibold text-stone-800 dark:text-white">{{ bundle.label }}</p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                {{ bundle.size }} · {{ bundle.created }}
                            </p>
                        </div>
                        <SporkButton secondary xsmall>
                            Download
                        </SporkButton>
                    </li>
                    <li v-if="!bundles.length" class="py-4 text-sm text-stone-500 dark:text-stone-400">
                        No log bundles archived yet.
                    </li>
                </ul>
            </section>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import { computed } from "vue";
import dayjs from "dayjs";
import { buildServerNavigation } from '@/Pages/Infrastructure/serverNavigation';

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

const navigation = computed(() => buildServerNavigation(props.server));

const logLines = computed(() => (props.server.logs ?? []).map((entry, index) => ({
    id: entry.id ?? index,
    timestamp: entry.timestamp ? dayjs(entry.timestamp).format('HH:mm:ss') : '--:--:--',
    message: entry.message ?? JSON.stringify(entry),
})));

const bundles = computed(() => (props.server.log_bundles ?? []).map((bundle) => ({
    id: bundle.id ?? bundle.created_at,
    label: bundle.label ?? 'Log export',
    size: bundle.size ?? 'Unknown size',
    created: bundle.created_at ? dayjs(bundle.created_at).format('MMM D, YYYY h:mm A') : 'Unknown',
})));
</script>
