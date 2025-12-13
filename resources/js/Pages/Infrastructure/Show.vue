<template>
    <ServerInfrastucture :title="`Server · ${server.name}`" :server="server">
        <div class="space-y-6">
            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-4">
                <header class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                            Overview
                        </p>
                        <h2 class="text-2xl font-semibold text-stone-900 dark:text-white">
                            Operational snapshot
                        </h2>
                    </div>
                    <div class="flex gap-2">
                        <SporkButton secondary @click="() => router.visit('/-/servers')">
                            Back to infrastructure
                        </SporkButton>
                        <SporkButton primary>
                            Trigger deploy
                        </SporkButton>
                    </div>
                </header>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div v-for="metric in overviewMetrics" :key="metric.label" class="p-4 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                            {{ metric.label }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-stone-900 dark:text-white">
                            {{ metric.value }}
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            {{ metric.caption }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm">
                    <header class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                                Linked domains
                            </p>
                            <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                                Traffic targets
                            </h3>
                        </div>
                        <SporkButton secondary xsmall @click="() => router.visit('/-/servers')">
                            Manage links
                        </SporkButton>
                    </header>

                    <ul class="divide-y divide-stone-200 dark:divide-stone-800">
                        <li
                            v-for="domain in linkedDomains"
                            :key="domain.id ?? domain.name"
                            class="py-3 flex items-center justify-between text-sm"
                        >
                            <div>
                                <p class="font-medium text-stone-800 dark:text-stone-100">{{ domain.name }}</p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">
                                    {{ domain.provider ?? 'Registrar unknown' }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-200">
                                {{ domain.dns_provider ?? 'DNS TBD' }}
                            </span>
                        </li>
                        <li v-if="!linkedDomains.length" class="py-6 text-sm text-stone-500 dark:text-stone-400 text-center">
                            No domains linked yet. Use the infrastructure hub to attach traffic.
                        </li>
                    </ul>
                </div>

                <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-4">
                    <header>
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                            Services & agents
                        </p>
                        <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                            Observed workloads
                        </h3>
                    </header>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="service in services"
                            :key="service.id ?? service.service"
                            class="px-3 py-1.5 text-xs rounded-full border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-200 bg-stone-50 dark:bg-stone-800/60"
                        >
                            {{ service.service ?? service.name }}
                            <span v-if="service.status" class="ml-1 text-[10px] uppercase tracking-wide text-stone-400 dark:text-stone-500">
                                {{ service.status }}
                            </span>
                        </span>
                        <p v-if="!services.length" class="text-sm text-stone-500 dark:text-stone-400">
                            No agents have reported running services yet.
                        </p>
                    </div>

                    <div class="border border-dashed border-stone-300 dark:border-stone-700 rounded-lg p-4 space-y-2">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                            Automation hooks
                        </p>
                        <p class="text-sm text-stone-600 dark:text-stone-300">
                            Register deployment or monitoring automations here to roll out updates, rotate keys, or push config.
                        </p>
                    </div>
                </div>
            </section>

            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm">
                <header class="mb-4">
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                        Activity
                    </p>
                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                        Recent events
                    </h3>
                </header>

                <ol class="relative border-l border-stone-200 dark:border-stone-800 pl-6 space-y-6">
                    <li v-for="event in activityTimeline" :key="event.id" class="relative">
                        <span class="absolute -left-2 top-1 w-3 h-3 rounded-full bg-indigo-500"></span>
                        <p class="text-sm font-semibold text-stone-800 dark:text-white">
                            {{ event.title }}
                        </p>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            {{ event.timestamp }}
                        </p>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                            {{ event.description }}
                        </p>
                    </li>
                    <li v-if="!activityTimeline.length" class="text-sm text-stone-500 dark:text-stone-400">
                        No recent events recorded for this server.
                    </li>
                </ol>
            </section>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import { computed } from "vue";
import dayjs from "dayjs";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

const linkedDomains = computed(() => props.server.domains ?? []);
const services = computed(() => props.server.services ?? []);

const overviewMetrics = computed(() => [
    {
        label: 'Uptime',
        value: props.server.booted_at ? `${dayjs().diff(dayjs(props.server.booted_at), 'hour')}h` : 'Unknown',
        caption: props.server.booted_at ? `Booted ${dayjs(props.server.booted_at).format('MMM D, YYYY')}` : 'No boot record yet',
    },
    {
        label: 'Agent heartbeat',
        value: props.server.last_ping_at ? `${Math.max(dayjs().diff(dayjs(props.server.last_ping_at), 'minute'), 1)}m ago` : 'Never',
        caption: props.server.last_ping_at ? dayjs(props.server.last_ping_at).format('MMM D, h:mm A') : 'No heartbeat yet',
    },
    {
        label: 'Monthly cost',
        value: props.server.cost_per_hour ? `$${(Number(props.server.cost_per_hour) * 24 * 30).toFixed(0)}` : '—',
        caption: props.server.cost_per_hour ? `$${Number(props.server.cost_per_hour).toFixed(2)}/hr` : 'Unknown rate',
    },
    {
        label: 'Projects linked',
        value: props.server.projects?.length ?? 0,
        caption: 'Deployments referencing this host',
    },
]);

const activityTimeline = computed(() => {
    if (props.server.activity) {
        return props.server.activity.map((event) => ({
            id: event.id ?? event.created_at,
            title: event.title ?? event.description ?? 'Activity',
            description: event.description ?? event.meta ?? '',
            timestamp: dayjs(event.created_at ?? event.timestamp).format('MMM D, YYYY h:mm A'),
        }));
    }

    const fallback = [];

    if (props.server.created_at) {
        fallback.push({
            id: 'created',
            title: 'Server linked to Spork',
            description: 'Initial metadata synced from provider.',
            timestamp: dayjs(props.server.created_at).format('MMM D, YYYY h:mm A'),
        });
    }

    if (props.server.updated_at) {
        fallback.push({
            id: 'updated',
            title: 'Metadata refreshed',
            description: 'Latest status pulled from automation agent.',
            timestamp: dayjs(props.server.updated_at).format('MMM D, YYYY h:mm A'),
        });
    }

    return fallback;
});
</script>
