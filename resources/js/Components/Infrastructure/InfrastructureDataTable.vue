<template>
    <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-800 shadow-sm flex flex-col">
        <header class="px-4 py-3 border-b border-stone-200 dark:border-stone-700 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-stone-800 dark:text-white">{{ title }}</p>
                <p class="text-xs text-stone-500 dark:text-stone-400">{{ description }}</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-stone-400 dark:text-stone-500">
                <slot name="header-actions" />
            </div>
        </header>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700 text-sm">
                <thead class="bg-stone-50 dark:bg-stone-900/40">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="px-4 py-3 text-left font-semibold text-stone-500 dark:text-stone-300 uppercase tracking-wide text-xs"
                        >
                            {{ column.label }}
                        </th>
                        <th v-if="$slots.actions" class="px-4 py-3 text-right text-xs font-semibold uppercase text-stone-500 dark:text-stone-300">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 dark:divide-stone-700">
                    <tr v-for="row in rows" :key="row[rowKey] ?? row.id" class="hover:bg-stone-50/80 dark:hover:bg-stone-900/20 transition">
                        <td
                            v-for="column in columns"
                            :key="`${row[rowKey] ?? row.id}-${column.key}`"
                            class="px-4 py-3 align-top"
                        >
                            <slot :name="`column-${column.key}`" :row="row" :column="column" :value="resolveValue(row, column)">
                                <p class="text-sm text-stone-700 dark:text-stone-200">
                                    {{ resolveValue(row, column) }}
                                </p>
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-3">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!rows.length" class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400">
            {{ emptyMessage }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    columns: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
    rowKey: {
        type: String,
        default: 'id',
    },
    emptyMessage: {
        type: String,
        default: 'No records found with the current filters.',
    },
});

const columns = computed(() => props.columns ?? []);
const rows = computed(() => props.rows ?? []);
const rowKey = computed(() => props.rowKey ?? 'id');

const resolveValue = (row, column) => {
    if (typeof column.accessor === 'function') {
        return column.accessor(row);
    }

    if (!column.accessor) {
        return row[column.key];
    }

    const pathSegments = column.accessor.split('.');
    return pathSegments.reduce((carry, segment) => (carry ? carry[segment] : undefined), row);
};
</script>

























