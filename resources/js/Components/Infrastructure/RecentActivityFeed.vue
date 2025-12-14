<template>
    <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-800 shadow-sm divide-y divide-stone-200 dark:divide-stone-700">
        <header class="px-4 py-3">
            <p class="text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-300">
                Recent activity
            </p>
            <p class="text-xs text-stone-500 dark:text-stone-400">
                DNS, links, automation.
            </p>
        </header>

        <ol class="divide-y divide-stone-200 dark:divide-stone-700">
            <li
                v-for="item in resolvedItems"
                :key="item.id"
                class="px-4 py-3 flex justify-between items-start gap-4"
            >
                <div class="flex flex-col gap-1">
                    <p class="text-sm font-medium text-stone-800 dark:text-stone-100">
                        {{ item.description }}
                    </p>
                    <p class="text-xs text-stone-500 dark:text-stone-400">
                        {{ item.subject_type }} {{ item.subject?.id}}
                    </p>
                    <div v-if="item.meta" class="flex flex-wrap gap-2 text-xs text-stone-400 dark:text-stone-500">
                        <span v-for="meta in item.meta" :key="meta">{{ meta }}</span>
                    </div>
                </div>
                <div class="text-xs text-stone-400 dark:text-stone-500 whitespace-nowrap">
                    {{ formatDate(item.timestamp) }}
                </div>
            </li>
        </ol>

        <div v-if="!resolvedItems.length" class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400">
            No recent events.
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const resolvedItems = computed(() => props.items ?? []);

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(value));
    } catch (error) {
        return value;
    }
};
</script>







