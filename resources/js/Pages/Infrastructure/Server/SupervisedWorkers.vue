<template>
    <ServerInfrastucture title="Supervised Workers" :server="server" :navigation="navigation">
        <GlassCard
            title="Processes managed by the agent"
            subtitle="Workers"
        >
            <template #actions>
                <GlassButton size="sm">Restart selected</GlassButton>
            </template>

            <GlassSurface class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] text-sm">
                        <thead class="bg-stone-100/40 dark:bg-stone-800/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Service</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Last heartbeat</th>
                                <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                            <tr v-for="worker in workers" :key="worker.id ?? worker.service">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-stone-800 dark:text-stone-50">{{ worker.service ?? worker.name }}</p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ worker.path ?? worker.description ?? 'Managed process' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <GlassPill :tone="worker.status === 'running' ? 'success' : 'danger'" size="sm">
                                        {{ worker.status ?? 'unknown' }}
                                    </GlassPill>
                                </td>
                                <td class="px-4 py-3 text-stone-600 dark:text-stone-300">
                                    {{ worker.last_ping_at ? formatDate(worker.last_ping_at) : 'No data' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <GlassButton variant="secondary" size="sm">Restart</GlassButton>
                                </td>
                            </tr>
                            <tr v-if="!workers.length">
                                <td class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400" colspan="4">
                                    No supervised workers have been detected yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </GlassSurface>
        </GlassCard>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassPill from "@/Components/Glass/GlassPill.vue";
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
const workers = computed(() => props.server.services ?? []);

const formatDate = (value) => dayjs(value).format('MMM D, YYYY h:mm A');
</script>
