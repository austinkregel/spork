<template>
  <component
    :is="tag"
    :href="href"
    :class="rootClasses"
    :aria-label="ariaLabel"
  >
    <GlassSurface
      :elevation="href ? 'md' : 'sm'"
      :class="['p-4 h-full flex flex-col gap-2', href ? 'transition-shadow motion-reduce:transition-none hover:shadow-lg' : '']"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 space-y-1">
          <p class="text-xs font-medium uppercase tracking-wide text-stone-500 dark:text-stone-400 truncate">
            {{ label }}
          </p>
          <p class="text-2xl font-semibold text-stone-900 dark:text-stone-50 truncate">
            <slot name="value">{{ value }}</slot>
          </p>
        </div>
        <component
          v-if="iconComponent"
          :is="iconComponent"
          class="h-6 w-6 shrink-0 text-indigo-500 dark:text-indigo-400"
          aria-hidden="true"
        />
      </div>

      <div v-if="$slots.default || description || trendLabel" class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
        <GlassPill v-if="trendLabel" :tone="trendTone" size="sm" dot>{{ trendLabel }}</GlassPill>
        <span v-if="description" class="truncate">{{ description }}</span>
        <slot />
      </div>
    </GlassSurface>
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import * as outline from '@heroicons/vue/24/outline';
import GlassSurface from './GlassSurface.vue';
import GlassPill from './GlassPill.vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: null },
  description: { type: String, default: null },
  href: { type: String, default: null },
  icon: { type: String, default: null },
  trendLabel: { type: String, default: null },
  trendTone: {
    type: String,
    default: 'neutral',
  },
});

const tag = computed(() => (props.href ? Link : 'div'));

const iconComponent = computed(() => (props.icon ? outline[props.icon] ?? null : null));

const ariaLabel = computed(() => (props.href ? `${props.label}: ${props.value ?? ''}`.trim() : undefined));

const rootClasses = computed(() => [
  'block rounded-lg',
  props.href
    ? 'focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950'
    : '',
]);
</script>
