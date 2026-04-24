<template>
  <nav
    class="hidden lg:flex lg:flex-col lg:w-16 xl:w-56 shrink-0 gap-2 px-2 py-4 backdrop-blur-glass border-r border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-rail-light)] dark:bg-[var(--color-glass-rail-dark)]"
    role="navigation"
    aria-label="Primary"
  >
    <Link
      :href="route('dashboard')"
      :class="homeLinkClass"
      :aria-label="homeLabel"
      :aria-current="current_pillar === null ? 'page' : undefined"
    >
      <CpuChipIcon class="h-7 w-7 shrink-0 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
      <span class="hidden xl:inline text-sm font-medium">{{ homeLabel }}</span>
    </Link>

    <Link
      v-for="p in pillars"
      :key="p.slug"
      :href="p.href"
      :class="linkClass(p, current_pillar)"
      :aria-label="p.label"
      :aria-current="p.slug === current_pillar ? 'page' : undefined"
    >
      <DynamicIcon :icon-name="p.icon" class="h-6 w-6 shrink-0" aria-hidden="true" />
      <span class="hidden xl:inline text-sm">{{ p.label }}</span>
    </Link>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CpuChipIcon } from '@heroicons/vue/24/outline';
import DynamicIcon from '@/Components/DynamicIcon.vue';

const props = defineProps({
  pillars: { type: Array, default: () => [] },
  current_pillar: { type: String, default: null },
  homeLabel: { type: String, default: 'Home' },
});

const baseClass =
  'flex items-center justify-center xl:justify-start gap-3 rounded-md px-2 py-2 text-sm transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950';

const idleClass =
  'text-stone-700 hover:bg-stone-200/70 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700/70 dark:hover:text-white';

const activeClass =
  'bg-indigo-500/15 text-indigo-700 ring-1 ring-indigo-500/30 dark:bg-indigo-500/20 dark:text-indigo-100 dark:ring-indigo-400/40';

const homeLinkClass = computed(() => [
  baseClass,
  props.current_pillar === null ? activeClass : idleClass,
]);

function linkClass(p, current) {
  return [baseClass, p.slug === current ? activeClass : idleClass];
}
</script>
