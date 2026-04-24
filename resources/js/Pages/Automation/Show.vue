<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';
import {
  BoltIcon,
  PencilSquareIcon,
  PlayIcon,
  ClockIcon,
  ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  title: { type: String, default: '' },
  automation: { type: Object, required: true },
  steps: { type: Array, default: () => [] },
});

function runNow() {
  router.post(route('automations.run-now', props.automation.id), {}, { preserveScroll: true });
}

function configPreview(config) {
  if (!config || (typeof config === 'object' && Object.keys(config).length === 0)) return '—';
  try {
    return JSON.stringify(config, null, 2);
  } catch {
    return String(config);
  }
}
</script>

<template>
  <AppLayout :title="title">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <Link
        :href="route('automations.automations.index')"
        class="inline-flex items-center gap-1 text-xs font-medium text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950 rounded-md px-1 py-0.5 w-fit"
      >
        <ArrowLeftIcon class="h-3.5 w-3.5" aria-hidden="true" />
        All automations
      </Link>

      <header class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div class="flex items-start gap-3">
          <BoltIcon class="h-8 w-8 shrink-0 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
              Automation
            </p>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
              {{ automation.name }}
            </h1>
            <div class="mt-1 flex flex-wrap items-center gap-1.5">
              <GlassPill :tone="automation.enabled ? 'success' : 'neutral'" size="sm" dot>
                {{ automation.enabled ? 'Enabled' : 'Disabled' }}
              </GlassPill>
              <GlassPill v-if="automation.cron_expression" tone="info" size="sm">
                <ClockIcon class="h-3 w-3" aria-hidden="true" />
                <span>{{ automation.cron_expression }}</span>
              </GlassPill>
              <GlassPill v-if="automation.timezone" tone="neutral" size="sm">
                {{ automation.timezone }}
              </GlassPill>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <GlassButton
            variant="secondary"
            :icon-left="PencilSquareIcon"
            :href="route('automations.automations.edit', automation.id)"
          >
            Edit
          </GlassButton>
          <GlassButton :icon-left="PlayIcon" @click="runNow">Run now</GlassButton>
        </div>
      </header>

      <GlassCard title="Steps" subtitle="Ordered actions Spork will execute on each run.">
        <ol v-if="steps.length" class="space-y-2">
          <li
            v-for="s in steps"
            :key="s.id"
            class="flex items-start gap-3 rounded-md border border-stone-200/70 bg-white/40 p-3 text-sm dark:border-stone-700/60 dark:bg-stone-800/40"
          >
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-500/15 text-xs font-semibold text-indigo-600 dark:text-indigo-300">
              {{ (s.order ?? 0) + 1 }}
            </span>
            <div class="min-w-0 flex-1 space-y-1">
              <div class="flex items-center gap-2">
                <span class="font-semibold text-stone-900 dark:text-stone-50">{{ s.type }}</span>
              </div>
              <pre class="overflow-x-auto rounded bg-stone-900/5 p-2 text-xs leading-snug text-stone-700 dark:bg-stone-100/5 dark:text-stone-200">{{ configPreview(s.config) }}</pre>
            </div>
          </li>
        </ol>
        <GlassEmptyState
          v-else
          icon="ListBulletIcon"
          title="No steps configured"
          description="Open the editor to add steps for this automation."
        >
          <GlassButton
            variant="secondary"
            :icon-left="PencilSquareIcon"
            :href="route('automations.automations.edit', automation.id)"
          >
            Edit automation
          </GlassButton>
        </GlassEmptyState>
      </GlassCard>
    </div>
  </AppLayout>
</template>
