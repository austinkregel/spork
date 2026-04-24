<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import { ChevronRightIcon, CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  step: { type: String, required: true },
  steps: { type: Array, default: () => [] },
  title: { type: String, default: 'Project Wizard' },
  subtitle: { type: String, default: null },
});

const orderedSteps = computed(() =>
  props.steps.map((entry) => ({
    ...entry,
    current: entry.key === props.step,
  })),
);
</script>

<template>
  <AppLayout :title="title">
    <template #header>
      <div class="flex items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-200">
        <Link
          href="/-/projects/list"
          class="rounded-sm hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
        >
          Projects
        </Link>
        <ChevronRightIcon class="h-4 w-4 text-stone-400" aria-hidden="true" />
        <span class="text-stone-900 dark:text-stone-50">Wizard</span>
      </div>
    </template>

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <div class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
          New project
        </p>
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="text-sm text-stone-600 dark:text-stone-300">
          {{ subtitle }}
        </p>
      </div>

      <ol
        class="flex flex-wrap items-center gap-2 text-xs font-medium"
        aria-label="Wizard progress"
      >
        <li
          v-for="(entry, idx) in orderedSteps"
          :key="entry.key"
          class="flex items-center gap-2"
        >
          <GlassPill
            :tone="entry.current ? 'indigo' : entry.completed ? 'success' : 'neutral'"
            size="sm"
          >
            <CheckCircleIcon
              v-if="entry.completed && !entry.current"
              class="h-3.5 w-3.5"
              aria-hidden="true"
            />
            <span v-else aria-hidden="true">{{ idx + 1 }}</span>
            <span class="ml-1">{{ entry.label }}</span>
          </GlassPill>
          <ChevronRightIcon
            v-if="idx < orderedSteps.length - 1"
            class="h-3.5 w-3.5 text-stone-400"
            aria-hidden="true"
          />
        </li>
      </ol>

      <GlassCard>
        <slot />
      </GlassCard>
    </div>
  </AppLayout>
</template>
