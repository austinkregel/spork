<template>
  <span :class="classes">
    <span v-if="dot" :class="['h-1.5 w-1.5 rounded-full', dotColor]" aria-hidden="true" />
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  tone: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'indigo', 'success', 'warning', 'danger', 'info'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md'].includes(v),
  },
  dot: { type: Boolean, default: false },
});

const sizeClass = computed(() =>
  props.size === 'sm' ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-0.5 text-xs',
);

const toneMap = {
  neutral: 'bg-stone-200/70 text-stone-700 dark:bg-stone-700/60 dark:text-stone-200',
  indigo: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200',
  success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
  warning: 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
  danger: 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-200',
  info: 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-200',
};

const dotMap = {
  neutral: 'bg-stone-500',
  indigo: 'bg-indigo-500',
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  danger: 'bg-red-500',
  info: 'bg-sky-500',
};

const classes = computed(() => [
  'inline-flex items-center gap-1.5 rounded-full font-medium',
  sizeClass.value,
  toneMap[props.tone],
]);

const dotColor = computed(() => dotMap[props.tone]);
</script>
