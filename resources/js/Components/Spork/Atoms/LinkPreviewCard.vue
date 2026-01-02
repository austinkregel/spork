<script setup>
import { computed } from 'vue';

const props = defineProps({
    preview: {
        type: Object,
        required: true,
    },
});

const host = computed(() => props.preview.host ?? props.preview.url);
const title = computed(() => props.preview.title || host.value);
const description = computed(() => props.preview.description || 'Link preview unavailable');
const hasImage = computed(() => Boolean(props.preview.image));
</script>

<template>
    <a
        :href="preview.url"
        target="_blank"
        rel="noopener noreferrer"
        class="flex gap-3 border border-stone-200 dark:border-stone-700 rounded-xl p-3 bg-white dark:bg-stone-900 hover:border-indigo-300 dark:hover:border-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
    >
        <div
            v-if="hasImage"
            class="h-16 w-16 rounded-lg overflow-hidden bg-stone-100 dark:bg-stone-800 flex items-center justify-center"
        >
            <img
                :src="preview.image"
                :alt="title"
                class="h-full w-full object-cover"
                loading="lazy"
            />
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-stone-800 dark:text-stone-100 truncate">
                {{ title }}
            </div>
            <div class="text-xs text-stone-500 dark:text-stone-400 line-clamp-2">
                {{ description }}
            </div>
            <div class="text-xs text-indigo-600 dark:text-indigo-300 mt-1">
                {{ host }}
            </div>
        </div>
    </a>
</template>



















