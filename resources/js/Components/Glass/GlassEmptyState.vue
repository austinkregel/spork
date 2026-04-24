<template>
  <GlassSurface class="p-8 text-center">
    <div class="mx-auto flex max-w-md flex-col items-center gap-3">
      <component
        v-if="iconComponent"
        :is="iconComponent"
        class="h-10 w-10 text-stone-400 dark:text-stone-500"
        aria-hidden="true"
      />
      <h3 class="text-base font-semibold text-stone-900 dark:text-stone-50">
        {{ title }}
      </h3>
      <p v-if="description" class="text-sm text-stone-500 dark:text-stone-400">
        {{ description }}
      </p>
      <div v-if="$slots.default" class="mt-2 flex items-center justify-center gap-2">
        <slot />
      </div>
    </div>
  </GlassSurface>
</template>

<script setup>
import { computed } from 'vue';
import * as outline from '@heroicons/vue/24/outline';
import GlassSurface from './GlassSurface.vue';

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
  icon: { type: String, default: null },
});

const iconComponent = computed(() => {
  if (!props.icon) return null;
  return outline[props.icon] ?? null;
});
</script>
