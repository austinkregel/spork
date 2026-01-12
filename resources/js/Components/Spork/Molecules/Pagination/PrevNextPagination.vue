<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    paginator: {
        type: Object,
        default: () => ({}),
    },
    preserveScroll: {
        type: Boolean,
        default: true,
    },
    preserveState: {
        type: Boolean,
        default: true,
    },
});

const prevUrl = computed(() => props.paginator?.prev_page_url ?? null);
const nextUrl = computed(() => props.paginator?.next_page_url ?? null);

const visit = (url) => {
    if (!url) return;

    router.visit(url, {
        preserveScroll: props.preserveScroll,
        preserveState: props.preserveState,
    });
};
</script>

<template>
    <div class="flex items-center justify-between text-sm text-stone-500 dark:text-stone-400">
        <button
            v-if="prevUrl"
            type="button"
            @click="visit(prevUrl)"
            class="px-4 py-2 rounded-lg border border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-900 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
        >
            Previous
        </button>
        <span v-else />

        <button
            v-if="nextUrl"
            type="button"
            @click="visit(nextUrl)"
            class="px-4 py-2 rounded-lg border border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-900 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
        >
            Next
        </button>
    </div>
</template>





