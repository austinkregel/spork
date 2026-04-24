<template>
  <component
    :is="as"
    :class="[
      'rounded-lg border',
      blurClass,
      tone === 'strong' ? strongToneClass : defaultToneClass,
      borderClass,
      shadowClass,
      contrastMoreClass,
    ]"
  >
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  as: { type: String, default: 'div' },
  tone: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'strong'].includes(v),
  },
  elevation: {
    type: String,
    default: 'sm',
    validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
  },
});

const shadowClass = computed(() => {
  switch (props.elevation) {
    case 'none':
      return '';
    case 'md':
      return 'shadow-md shadow-stone-900/5 dark:shadow-black/20';
    case 'lg':
      return 'shadow-[var(--shadow-glass)] dark:shadow-[var(--shadow-glass-dark)]';
    default:
      return 'shadow-sm shadow-stone-900/5 dark:shadow-black/15';
  }
});

const blurClass = computed(() => 'backdrop-blur-glass supports-[backdrop-filter]:bg-opacity-100');

const defaultToneClass = computed(
  () => 'bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)]',
);

const strongToneClass = computed(
  () => 'bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)]',
);

const borderClass = computed(
  () => 'border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]',
);

const contrastMoreClass = computed(
  () =>
    'contrast-more:border-stone-400 contrast-more:bg-white contrast-more:dark:bg-stone-900 contrast-more:backdrop-blur-none',
);

</script>
