<script setup>
import dayjs from 'dayjs';
import Manage from "@/Layouts/Manage.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassMetricCard from "@/Components/Glass/GlassMetricCard.vue";

defineProps({
    title: String,
    metrics: Object,
    activity: Object,
});

const formatDateIso = (date) => dayjs(date).format('YYYY-MM-DD HH:mm:ss');
</script>

<template>
    <Manage :title="title" sub-title="Manage" home="/-/manage">
        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <GlassMetricCard
                    v-for="(value, metric) in metrics"
                    :key="metric"
                    :label="metric"
                    :value="value"
                />
            </div>

            <GlassCard title="Recent activity" subtitle="Audit log">
                <ul class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                    <li v-for="item in activity?.data ?? []" :key="item.id" class="py-3">
                        <div class="flex flex-col">
                            <div class="flex flex-wrap items-baseline gap-2 text-sm text-stone-700 dark:text-stone-200">
                                <span class="text-xs font-medium uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ item.log_name }}</span>
                                <span>{{ item.description }}</span>
                                <span class="font-semibold text-stone-900 dark:text-stone-50">{{ item.properties?.attributes?.name ?? item.properties?.attributes?.headline }}</span>
                            </div>
                            <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                {{ formatDateIso(item.created_at) }}
                            </div>
                        </div>
                    </li>
                </ul>
            </GlassCard>
        </div>
    </Manage>
</template>
