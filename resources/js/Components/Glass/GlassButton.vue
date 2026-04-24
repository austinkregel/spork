<template>
  <component
    :is="tag"
    :type="isButton ? type : undefined"
    :href="href"
    :disabled="isButton ? disabled : undefined"
    :aria-disabled="!isButton && disabled ? 'true' : undefined"
    :class="classes"
  >
    <component :is="iconLeft" v-if="iconLeft" class="h-4 w-4 shrink-0" aria-hidden="true" />
    <slot />
    <component :is="iconRight" v-if="iconRight" class="h-4 w-4 shrink-0" aria-hidden="true" />
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'ghost', 'destructive', 'outline', 'success'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  type: { type: String, default: 'button' },
  href: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
  iconLeft: { type: [Object, Function], default: null },
  iconRight: { type: [Object, Function], default: null },
});

const tag = computed(() => (props.href ? Link : 'button'));
const isButton = computed(() => tag.value === 'button');

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'px-2.5 py-1 text-xs gap-1.5';
    case 'lg':
      return 'px-5 py-2.5 text-base gap-2.5';
    default:
      return 'px-3 py-2 text-sm gap-2';
  }
});

const variantClass = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'bg-indigo-500 text-white hover:bg-indigo-600 active:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400 shadow-sm';
    case 'secondary':
      return 'bg-stone-200/80 text-stone-800 hover:bg-stone-300/80 active:bg-stone-300 dark:bg-stone-700/80 dark:text-stone-100 dark:hover:bg-stone-600/80';
    case 'ghost':
      return 'bg-transparent text-stone-700 hover:bg-stone-200/70 dark:text-stone-200 dark:hover:bg-stone-700/60';
    case 'outline':
      return 'border border-stone-300 bg-white/40 text-stone-800 hover:bg-stone-100 dark:border-stone-600 dark:bg-stone-800/40 dark:text-stone-100 dark:hover:bg-stone-700/60';
    case 'destructive':
      return 'bg-red-500 text-white hover:bg-red-600 active:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400 shadow-sm';
    case 'success':
      return 'bg-emerald-500 text-white hover:bg-emerald-600 active:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 shadow-sm';
    default:
      return '';
  }
});

const classes = computed(() => [
  'inline-flex items-center justify-center font-medium rounded-md transition-colors motion-reduce:transition-none',
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950',
  props.variant === 'destructive' ? 'focus-visible:ring-red-500' : 'focus-visible:ring-indigo-500',
  sizeClass.value,
  variantClass.value,
  props.disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : 'cursor-pointer',
  props.block ? 'w-full' : '',
]);
</script>
