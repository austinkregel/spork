<script setup>
import { computed } from 'vue';

const props = defineProps({
    avatars: {
        type: Array,
        default: () => [],
    },
    size: {
        type: String,
        default: 'md',
    },
    maxVisible: {
        type: Number,
        default: 3,
    },
});

const sizeClasses = {
    sm: 'h-7 w-7',
    md: 'h-9 w-9',
    lg: 'h-11 w-11',
};

const visibleAvatars = computed(() => props.avatars.slice(0, props.maxVisible));
const remainingCount = computed(() => Math.max(props.avatars.length - props.maxVisible, 0));
</script>

<template>
    <div class="flex -space-x-3">
        <img
            v-for="avatar in visibleAvatars"
            :key="avatar.id ?? avatar.alt ?? avatar.src"
            :src="avatar.src"
            :alt="avatar.alt ?? ''"
            :class="[sizeClasses[size] ?? sizeClasses.md, 'rounded-full ring-2 ring-white dark:ring-stone-900 object-cover']"
        />
        <div
            v-if="remainingCount > 0"
            :class="[sizeClasses[size] ?? sizeClasses.md, 'rounded-full bg-stone-200 dark:bg-stone-800 text-[11px] font-semibold text-stone-600 dark:text-stone-300 inline-flex items-center justify-center ring-2 ring-white dark:ring-stone-900']"
        >
            +{{ remainingCount }}
        </div>
    </div>
</template>

