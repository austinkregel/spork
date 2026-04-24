<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import { Link } from '@inertiajs/vue3';
import {
  CheckCircleIcon,
  ShieldCheckIcon,
  SparklesIcon,
  ClockIcon,
  BoltIcon,
  TagIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  title: { type: String, default: 'Automation control center' },
  blueprints: { type: Array, default: () => [] },
  pipelines: { type: Array, default: () => [] },
  safety: { type: Array, default: () => [] },
  integrations: { type: Array, default: () => [] },
});

const sections = [
  { id: 'quick-actions', label: 'Quick actions' },
  { id: 'blueprint', label: 'Blueprint' },
  { id: 'playbooks', label: 'Pipelines' },
  { id: 'scheduling', label: 'Safety + tooling' },
];
</script>

<template>
  <AppLayout :title="title">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
      <header class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
          Control center
        </p>
        <h1 class="text-3xl font-semibold text-stone-900 dark:text-stone-50">
          Guide how Spork automates safely
        </h1>
        <p class="max-w-3xl text-sm text-stone-600 dark:text-stone-300">
          Use this space to orchestrate crawlers, browser interactions, and automated reactions. The
          blueprint below maps the backend on top of operations, using Laravel Dusk for full browsers
          and the existing curl/Guzzle crawler for lightweight scrapes.
        </p>

        <nav
          aria-label="On this page"
          class="flex flex-wrap items-center gap-2 pt-2 text-xs"
        >
          <a
            v-for="section in sections"
            :key="section.id"
            :href="`#${section.id}`"
            class="rounded-full border border-[var(--color-glass-border-light)] bg-[var(--color-glass-surface-light)] px-3 py-1 font-medium text-stone-600 transition-colors hover:bg-stone-100/60 hover:text-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 motion-reduce:transition-none dark:border-[var(--color-glass-border-dark)] dark:bg-[var(--color-glass-surface-dark)] dark:text-stone-300 dark:hover:bg-stone-800/60 dark:hover:text-stone-100 dark:focus-visible:ring-offset-stone-950"
          >
            {{ section.label }}
          </a>
        </nav>
      </header>

      <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <GlassCard padding="md">
          <div class="flex items-center gap-2 text-sm font-semibold text-stone-900 dark:text-stone-50">
            <SparklesIcon class="h-5 w-5 text-sky-500 dark:text-sky-400" aria-hidden="true" />
            Operations
          </div>
          <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">
            Automation-specific operation types with reusable payloads and tagging.
          </p>
        </GlassCard>
        <GlassCard padding="md">
          <div class="flex items-center gap-2 text-sm font-semibold text-stone-900 dark:text-stone-50">
            <ShieldCheckIcon class="h-5 w-5 text-emerald-500 dark:text-emerald-400" aria-hidden="true" />
            Safety
          </div>
          <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">
            Host-aware pacing, robots.txt awareness, and credential audits.
          </p>
        </GlassCard>
        <GlassCard padding="md">
          <div class="flex items-center gap-2 text-sm font-semibold text-stone-900 dark:text-stone-50">
            <ClockIcon class="h-5 w-5 text-amber-500 dark:text-amber-400" aria-hidden="true" />
            Scheduling
          </div>
          <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">
            Reoccurring cadences that stagger traffic to mimic human behavior.
          </p>
        </GlassCard>
      </section>

      <section id="quick-actions" class="space-y-3 scroll-mt-24">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Quick actions
          </p>
          <h2 class="text-xl font-semibold text-stone-900 dark:text-stone-50">
            Jump into automation
          </h2>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <GlassCard padding="md">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2 text-sm font-semibold text-stone-900 dark:text-stone-50">
                  <BoltIcon class="h-4 w-4 text-indigo-500" aria-hidden="true" />
                  Manage automations
                </div>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                  Create, edit, and run playbooks.
                </p>
              </div>
              <GlassButton :href="route('automations.automations.index')" size="sm">
                Open
              </GlassButton>
            </div>
          </GlassCard>
          <GlassCard padding="md">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2 text-sm font-semibold text-stone-900 dark:text-stone-50">
                  <TagIcon class="h-4 w-4 text-indigo-500" aria-hidden="true" />
                  Automation tags
                </div>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                  Route outputs and control access.
                </p>
              </div>
              <GlassButton variant="secondary" :href="route('automations.tags')" size="sm">
                Open
              </GlassButton>
            </div>
          </GlassCard>
        </div>
      </section>

      <section id="blueprint" class="space-y-3 scroll-mt-24">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Blueprint
          </p>
          <h2 class="text-xl font-semibold text-stone-900 dark:text-stone-50">Backend plan</h2>
        </div>
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
          <GlassCard
            v-for="item in blueprints"
            :key="item.name"
            :title="item.name"
            :subtitle="item.summary"
          >
            <ul class="space-y-2 text-sm text-stone-700 dark:text-stone-200">
              <li v-for="point in item.items" :key="point" class="flex items-start gap-2">
                <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" aria-hidden="true" />
                <span>{{ point }}</span>
              </li>
            </ul>
          </GlassCard>
        </div>
      </section>

      <section id="playbooks" class="space-y-3 scroll-mt-24">
        <div class="flex items-center gap-2">
          <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Playbooks
          </p>
          <GlassPill tone="warning" size="sm">Planned</GlassPill>
        </div>
        <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-50">
          Common pipelines to configure
        </h3>
        <p class="text-sm text-stone-600 dark:text-stone-300">
          Each pipeline will become a reusable playbook that wires inputs, cadence, and output destinations.
        </p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <GlassCard
            v-for="pipeline in pipelines"
            :key="pipeline.name"
            :title="pipeline.name"
            :subtitle="pipeline.description"
            padding="none"
          />
        </div>
      </section>

      <section id="scheduling" class="grid grid-cols-1 gap-3 scroll-mt-24 lg:grid-cols-2">
        <GlassCard title="Safety rails">
          <ul class="space-y-2 text-sm text-stone-700 dark:text-stone-200">
            <li v-for="item in safety" :key="item" class="flex items-start gap-2">
              <ShieldCheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" aria-hidden="true" />
              <span>{{ item }}</span>
            </li>
          </ul>
        </GlassCard>
        <GlassCard title="Tooling">
          <ul class="space-y-2 text-sm text-stone-700 dark:text-stone-200">
            <li v-for="item in integrations" :key="item" class="flex items-start gap-2">
              <SparklesIcon class="mt-0.5 h-4 w-4 shrink-0 text-sky-500" aria-hidden="true" />
              <span>{{ item }}</span>
            </li>
          </ul>
        </GlassCard>
      </section>
    </div>
  </AppLayout>
</template>
