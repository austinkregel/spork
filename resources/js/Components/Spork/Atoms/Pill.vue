<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: {
        type: String,
        default: 'neutral',
    },
    size: {
        type: String,
        default: 'xs',
    },
});

const toneClasses = {
    neutral: 'bg-stone-200 dark:bg-stone-800 text-stone-600 dark:text-stone-300',
    info: 'bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300',
    success: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300',
    warning: 'bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300',
    danger: 'bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-300',
};

const sizeClasses = {
    xs: 'text-[10px] px-2 py-0.5',
    sm: 'text-xs px-2.5 py-1',
};

const classes = computed(() => {
    const base = 'inline-flex items-center rounded-full font-medium';

    return [
        base,
        toneClasses[props.tone] ?? toneClasses.neutral,
        sizeClasses[props.size] ?? sizeClasses.xs,
    ].join(' ');
});
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>





