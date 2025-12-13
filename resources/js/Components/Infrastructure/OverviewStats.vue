<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="stat in stats"
            :key="stat.key ?? stat.label"
            class="border border-stone-200 dark:border-stone-800 rounded-lg p-4 bg-white dark:bg-stone-800 shadow-sm flex flex-col gap-3"
        >
            <div class="flex items-center justify-between text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400">
                <span>{{ stat.label }}</span>
                <span v-if="stat.caption" class="text-stone-400 dark:text-stone-500">{{ stat.caption }}</span>
            </div>
            <div class="text-3xl font-semibold text-stone-800 dark:text-white">
                {{ formatValue(stat.value) }}
            </div>
            <div v-if="stat.delta !== undefined && stat.delta !== null" class="flex items-center text-sm font-medium" :class="stat.delta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                <span>{{ formatDelta(stat.delta) }}</span>
                <span class="ml-2 text-xs text-stone-500 dark:text-stone-400">{{ stat.delta_label ?? 'vs last period' }}</span>
            </div>
            <div v-if="stat.meta" class="text-xs text-stone-500 dark:text-stone-400">
                {{ stat.meta }}
            </div>
        </div>
    </div>
</template>

<script setup>
const numberFormatter = new Intl.NumberFormat(undefined, { notation: 'compact' });
const percentFormatter = new Intl.NumberFormat(undefined, { style: 'percent', maximumFractionDigits: 1 });

defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
});

const formatValue = (value) => {
    if (value === undefined || value === null || value === '') {
        return '—';
    }

    if (typeof value === 'number') {
        return numberFormatter.format(value);
    }

    return value;
};

const formatDelta = (value) => {
    if (typeof value === 'number' && value > -1 && value < 1) {
        return percentFormatter.format(value);
    }

    if (typeof value === 'number') {
        return `${value > 0 ? '+' : ''}${value}%`;
    }

    return value;
};
</script>







