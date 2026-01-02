<template>
    <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-800 text-sm">
                <thead class="bg-stone-50 dark:bg-stone-900/30">
                    <tr>
                        <th v-for="column in columns" :key="column.key" class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-xs text-stone-500 dark:text-stone-400">
                            {{ column.label }}
                        </th>
                        <th v-if="showActions" class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 dark:divide-stone-800">
                    <tr v-for="record in records" :key="record.id ?? `${record.type}-${record.name}`">
                        <td class="px-4 py-3 font-semibold text-stone-800 dark:text-white">{{ record.type }}</td>
                        <td class="px-4 py-3 text-stone-600 dark:text-stone-300">{{ record.name }}</td>
                        <td class="px-4 py-3 text-stone-600 dark:text-stone-300">{{ record.ttl ?? 'Auto' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-stone-800 dark:text-stone-100 whitespace-pre-wrap">{{ record.value }}</td>
                        <td class="px-4 py-3" v-if="showActions">
                            <div class="flex gap-2 justify-end">
                                <SporkButton secondary xsmall @click="$emit('edit', record)">
                                    Edit
                                </SporkButton>
                                <SporkButton danger xsmall @click="$emit('delete', record)">
                                    Remove
                                </SporkButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!records.length">
                        <td class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400" :colspan="columns.length + (showActions ? 1 : 0)">
                            {{ emptyMessage }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import SporkButton from "@/Components/Spork/SporkButton.vue";

defineProps({
    records: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        default: () => ([
            { key: 'type', label: 'Type' },
            { key: 'name', label: 'Name' },
            { key: 'ttl', label: 'TTL' },
            { key: 'value', label: 'Value' },
        ]),
    },
    showActions: {
        type: Boolean,
        default: true,
    },
    emptyMessage: {
        type: String,
        default: 'No DNS records available.',
    },
});

defineEmits(['edit', 'delete']);
</script>



















