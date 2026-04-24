<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';
import {
  BoltIcon,
  PlusIcon,
  PlayIcon,
  PencilSquareIcon,
  EyeIcon,
  ClockIcon,
  ListBulletIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  title: { type: String, default: 'Automations' },
  automations: { type: Object, required: true },
});

function runNow(id) {
  router.post(route('automations.run-now', id), {}, { preserveScroll: true });
}
</script>

<template>
  <AppLayout :title="title">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <header class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Workflows
          </p>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">Automations</h1>
          <p class="mt-1 max-w-3xl text-sm text-stone-600 dark:text-stone-300">
            Each automation captures a sequence of steps Spork should run for you, on cadence or on demand.
          </p>
        </div>
        <GlassButton :icon-left="PlusIcon" :href="route('automations.automations.create')">
          New automation
        </GlassButton>
      </header>

      <div v-if="automations.data.length" class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <GlassCard v-for="a in automations.data" :key="a.id" padding="md">
          <div class="flex items-start gap-3">
            <BoltIcon class="h-7 w-7 shrink-0 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <Link
                :href="route('automations.automations.show', a.id)"
                class="block truncate text-base font-semibold text-stone-900 hover:text-indigo-600 dark:text-stone-50 dark:hover:text-indigo-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950 rounded"
              >
                {{ a.name }}
              </Link>
              <div class="mt-1 flex flex-wrap items-center gap-1.5">
                <GlassPill :tone="a.enabled ? 'success' : 'neutral'" size="sm" dot>
                  {{ a.enabled ? 'Enabled' : 'Disabled' }}
                </GlassPill>
                <GlassPill v-if="a.cron_expression" tone="info" size="sm">
                  <ClockIcon class="h-3 w-3" aria-hidden="true" />
                  <span>{{ a.cron_expression }}</span>
                </GlassPill>
                <GlassPill tone="neutral" size="sm">
                  <ListBulletIcon class="h-3 w-3" aria-hidden="true" />
                  <span>{{ a.steps_count ?? 0 }} steps</span>
                </GlassPill>
              </div>
            </div>
          </div>

          <template #footer>
            <div class="flex flex-wrap items-center justify-end gap-2">
              <GlassButton
                variant="ghost"
                size="sm"
                :icon-left="EyeIcon"
                :href="route('automations.automations.show', a.id)"
              >
                View
              </GlassButton>
              <GlassButton
                variant="secondary"
                size="sm"
                :icon-left="PencilSquareIcon"
                :href="route('automations.automations.edit', a.id)"
              >
                Edit
              </GlassButton>
              <GlassButton
                size="sm"
                :icon-left="PlayIcon"
                @click="runNow(a.id)"
              >
                Run now
              </GlassButton>
            </div>
          </template>
        </GlassCard>
      </div>

      <GlassEmptyState
        v-else
        icon="BoltIcon"
        title="No automations yet"
        description="Create your first automation to schedule crawls, browser flows, and reactive jobs."
      >
        <GlassButton :icon-left="PlusIcon" :href="route('automations.automations.create')">
          New automation
        </GlassButton>
      </GlassEmptyState>
    </div>
  </AppLayout>
</template>
