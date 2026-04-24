<template>
    <ServerInfrastucture title="Logs" :server="server" :navigation="navigation">
        <div class="space-y-6">
            <GlassSurface class="overflow-hidden bg-stone-950 text-emerald-400">
                <header class="flex items-center justify-between border-b border-stone-800 px-4 py-2 text-xs uppercase tracking-widest text-stone-400">
                    <span>Live tail</span>
                    <span>{{ server.ip_address }}</span>
                </header>
                <div class="h-80 overflow-y-auto px-4 py-6 font-mono text-xs leading-relaxed">
                    <p v-for="line in logLines" :key="line.id">
                        <span class="text-stone-500">{{ line.timestamp }}</span>
                        <span class="ml-2">{{ line.message }}</span>
                    </p>
                    <p v-if="!logLines.length">No log entries streamed yet.</p>
                </div>
            </GlassSurface>

            <GlassCard
                title="Recent log bundles"
                subtitle="Saved snapshots"
            >
                <ul class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                    <li v-for="bundle in bundles" :key="bundle.id" class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-semibold text-stone-800 dark:text-stone-50">{{ bundle.label }}</p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">{{ bundle.size }} · {{ bundle.created }}</p>
                        </div>
                        <GlassButton variant="secondary" size="sm">Download</GlassButton>
                    </li>
                    <li v-if="!bundles.length" class="py-4 text-sm text-stone-500 dark:text-stone-400">
                        No log bundles archived yet.
                    </li>
                </ul>
            </GlassCard>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
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
