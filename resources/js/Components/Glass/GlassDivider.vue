<template>
  <hr
    :class="[
      orientation === 'vertical' ? 'h-full w-px' : 'h-px w-full',
      'border-0 bg-[var(--color-glass-border-light)] dark:bg-[var(--color-glass-border-dark)]',
    ]"
    role="separator"
    :aria-orientation="orientation"
  />
</template>

<script setup>
defineProps({
  orientation: {
    type: String,
    default: 'horizontal',
    validator: (v) => ['horizontal', 'vertical'].includes(v),
  },
});
</script>
