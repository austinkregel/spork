<template>
    <ServerInfrastucture title="Supervised Workers" :server="server" :navigation="navigation">
        <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 shadow-sm">
            <header class="px-4 py-3 border-b border-stone-200 dark:border-stone-800 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                        Workers
                    </p>
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-white">
                        Processes managed by the agent
                    </h2>
                </div>
                <SporkButton primary xsmall>
                    Restart selected
                </SporkButton>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-800 text-sm">
                    <thead class="bg-stone-50 dark:bg-stone-900/30">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-xs text-stone-500 dark:text-stone-400">Service</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-xs text-stone-500 dark:text-stone-400">Status</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-xs text-stone-500 dark:text-stone-400">Last heartbeat</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 dark:divide-stone-800">
                        <tr v-for="worker in workers" :key="worker.id ?? worker.service">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-stone-800 dark:text-white">{{ worker.service ?? worker.name }}</p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ worker.path ?? worker.description ?? 'Managed process' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-semibold"
                                    :class="worker.status === 'running' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-300'"
                                >
                                    {{ worker.status ?? 'unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-stone-600 dark:text-stone-300">
                                {{ worker.last_ping_at ? formatDate(worker.last_ping_at) : 'No data' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <SporkButton secondary xsmall>
                                    Restart
                                </SporkButton>
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
const workers = computed(() => props.server.services ?? []);

const formatDate = (value) => dayjs(value).format('MMM D, YYYY h:mm A');
</script>
