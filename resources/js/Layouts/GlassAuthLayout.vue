<template>
  <div class="relative min-h-screen overflow-hidden bg-stone-50 dark:bg-stone-950">
    <Head :title="title" />

    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
      <div class="absolute -top-24 left-1/3 h-72 w-72 rounded-full bg-indigo-300/40 blur-3xl dark:bg-indigo-500/20" />
      <div class="absolute bottom-0 right-1/4 h-80 w-80 rounded-full bg-sky-300/30 blur-3xl dark:bg-sky-500/15" />
      <div class="absolute top-1/2 -left-20 h-64 w-64 rounded-full bg-fuchsia-300/30 blur-3xl dark:bg-fuchsia-500/15" />
    </div>

    <main class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6">
      <Link :href="'/'" class="mb-6 inline-flex items-center gap-2 text-stone-700 dark:text-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950 rounded-md">
        <CpuChipIcon class="h-10 w-10 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
        <span class="text-lg font-semibold">Spork</span>
      </Link>

      <GlassSurface tone="strong" class="w-full max-w-md p-6 sm:p-8">
        <header v-if="heading" class="mb-6 space-y-1">
          <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-50">{{ heading }}</h1>
          <p v-if="subheading" class="text-sm text-stone-600 dark:text-stone-300">{{ subheading }}</p>
        </header>

        <slot />
      </GlassSurface>

      <p class="mt-6 text-xs text-stone-500 dark:text-stone-400">
        &copy; {{ currentYear }} Spork
      </p>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CpuChipIcon } from '@heroicons/vue/24/outline';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';

defineProps({
  title: { type: String, default: 'Spork' },
  heading: { type: String, default: null },
  subheading: { type: String, default: null },
});

const currentYear = computed(() => new Date().getFullYear());
</script>
