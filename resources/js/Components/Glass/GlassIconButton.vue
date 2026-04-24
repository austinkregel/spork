<template>
  <component
    :is="tag"
    :type="isButton ? type : undefined"
    :href="href"
    :disabled="isButton ? disabled : undefined"
    :aria-disabled="!isButton && disabled ? 'true' : undefined"
    :aria-label="label"
    :class="classes"
  >
    <span class="sr-only">{{ label }}</span>
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  label: { type: String, required: true },
  href: { type: String, default: null },
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  variant: {
    type: String,
    default: 'ghost',
    validator: (v) => ['ghost', 'subtle', 'destructive'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
});

const tag = computed(() => (props.href ? Link : 'button'));
const isButton = computed(() => tag.value === 'button');

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-7 w-7';
    case 'lg':
      return 'h-11 w-11';
    default:
      return 'h-9 w-9';
  }
});

const variantClass = computed(() => {
  switch (props.variant) {
    case 'subtle':
      return 'bg-stone-200/70 text-stone-700 hover:bg-stone-300/70 dark:bg-stone-700/60 dark:text-stone-200 dark:hover:bg-stone-600/70';
    case 'destructive':
      return 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10';
    default:
      return 'text-stone-600 hover:bg-stone-200/70 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700/60 dark:hover:text-white';
  }
});

const classes = computed(() => [
  'inline-flex items-center justify-center rounded-md transition-colors motion-reduce:transition-none',
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950',
  props.variant === 'destructive' ? 'focus-visible:ring-red-500' : 'focus-visible:ring-indigo-500',
  sizeClass.value,
  variantClass.value,
  props.disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : 'cursor-pointer',
]);
</script>
