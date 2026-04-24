<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'ghost',
    },
    size: {
        type: String,
        default: 'md',
    },
    srText: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);

const sizeClasses = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-9 w-9 text-sm',
    lg: 'h-10 w-10 text-base',
};

const variantClasses = {
    ghost: 'border border-transparent text-stone-600 hover:text-indigo-600 hover:border-indigo-200 dark:text-stone-200 dark:hover:text-indigo-300 dark:hover:border-indigo-500 bg-transparent',
    subtle: 'border border-stone-200 dark:border-stone-700 bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-200 hover:border-indigo-300 hover:text-indigo-600 dark:hover:text-indigo-300',
    primary: 'border border-indigo-600 bg-indigo-600 text-white hover:bg-indigo-500',
    danger: 'border border-red-200 dark:border-red-800 text-red-500 hover:text-red-400 hover:border-red-400 dark:text-red-400 dark:hover:text-red-300',
};

const buttonClass = computed(() => {
    const base = 'inline-flex items-center justify-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    return [
        base,
        sizeClasses[props.size] ?? sizeClasses.md,
        variantClasses[props.variant] ?? variantClasses.ghost,
    ].join(' ');
});
</script>

<template>
    <button
        :type="type"
        :title="title || srText"
        :class="buttonClass"
        :disabled="disabled"
        @click="$emit('click', $event)"
    >
        <slot />
        <span v-if="srText" class="sr-only">{{ srText }}</span>
    </button>
</template>

