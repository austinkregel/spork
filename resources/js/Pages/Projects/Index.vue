<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';
import { ChevronRightIcon, PlusIcon, SparklesIcon } from '@heroicons/vue/24/outline';

defineProps({
  data: { type: Array, default: () => [] },
  paginator: { type: Object, default: null },
});
</script>

<template>
  <AppLayout title="Projects">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Workspace
          </p>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">Projects</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <GlassButton variant="secondary" :icon-left="PlusIcon" href="/-/projects/create">
            New project
          </GlassButton>
          <GlassButton :icon-left="SparklesIcon" href="/-/projects/wizard">
            Start wizard
          </GlassButton>
        </div>
      </div>

      <div v-if="data.length" class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Link
          v-for="item in data"
          :key="item.id"
          :href="`/-/projects/${item.id}`"
          class="block rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
        >
          <GlassCard class="flex items-center justify-between gap-3 transition-colors hover:bg-stone-100/30 dark:hover:bg-stone-800/30">
            <span class="text-sm font-medium text-stone-900 dark:text-stone-50">{{ item.name }}</span>
            <ChevronRightIcon class="h-5 w-5 shrink-0 text-stone-400" aria-hidden="true" />
          </GlassCard>
        </Link>
      </div>

      <GlassEmptyState
        v-else
        title="No projects yet"
        description="Create your first project from a template or start fresh with the guided wizard."
      >
        <GlassButton :icon-left="SparklesIcon" href="/-/projects/wizard">
          Launch wizard
        </GlassButton>
        <GlassButton variant="secondary" :icon-left="PlusIcon" href="/-/projects/create">
          New project
        </GlassButton>
      </GlassEmptyState>
    </div>
  </AppLayout>
</template>
