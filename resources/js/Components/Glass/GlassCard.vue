<template>
  <GlassSurface :tone="tone" :as="as" :class="['flex flex-col', interactiveClass]">
    <header v-if="title || $slots.header || actions || $slots.actions" class="flex items-start justify-between gap-3 px-5 pt-5">
      <div class="min-w-0 flex-1 space-y-1">
        <slot name="header">
          <h3 v-if="title" class="text-base font-semibold text-stone-900 dark:text-stone-50 truncate">
            {{ title }}
          </h3>
          <p v-if="subtitle" class="text-xs text-stone-500 dark:text-stone-400">{{ subtitle }}</p>
        </slot>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </header>

    <div :class="['flex-1', bodyPadding]">
      <slot />
    </div>

    <footer
      v-if="$slots.footer"
      class="px-5 pb-5 pt-3 border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]"
    >
      <slot name="footer" />
    </footer>
  </GlassSurface>
</template>

<script setup>
import { computed } from 'vue';
import GlassSurface from './GlassSurface.vue';

const props = defineProps({
  as: { type: String, default: 'div' },
  title: { type: String, default: null },
  subtitle: { type: String, default: null },
  tone: { type: String, default: 'default' },
  interactive: { type: Boolean, default: false },
  padding: {
    type: String,
    default: 'md',
    validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
  },
});

const bodyPadding = computed(() => {
  switch (props.padding) {
    case 'none':
      return '';
    case 'sm':
      return 'p-3';
    case 'lg':
      return 'p-6';
    default:
      return 'p-5';
  }
});

const interactiveClass = computed(() =>
  props.interactive
    ? 'transition-shadow motion-reduce:transition-none hover:shadow-xl hover:shadow-black/10 cursor-pointer'
    : '',
);
</script>
