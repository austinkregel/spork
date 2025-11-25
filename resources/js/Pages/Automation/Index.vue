<script setup>
import Manage from "@/Layouts/Manage.vue";
import { CheckCircleIcon, ShieldCheckIcon, SparklesIcon, ClockIcon } from "@heroicons/vue/24/outline";

const { title, blueprints, pipelines, safety, integrations } = defineProps({
  title: String,
  blueprints: Array,
  pipelines: Array,
  safety: Array,
  integrations: Array,
});
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation">
    <div class="space-y-8">
      <section class="bg-white/70 dark:bg-stone-800/60 border border-stone-200 dark:border-stone-700 rounded-xl p-6 shadow-sm">
        <div class="flex flex-col gap-3">
          <p class="text-sm uppercase tracking-widest text-stone-500 dark:text-stone-400">Control center</p>
          <h1 class="text-3xl font-semibold text-stone-900 dark:text-white">Guide how Spork automates safely</h1>
          <p class="text-stone-700 dark:text-stone-200 leading-relaxed">
            Use this space to orchestrate crawlers, browser interactions, and automated reactions. The blueprint below maps the
            backend we will build on top of Dealer Inspire operations, using Laravel Dusk for full browsers and the existing
            curl/Guzzle crawler for lightweight scrapes.
          </p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
              <div class="flex items-center gap-2 text-blue-700 dark:text-blue-200 font-semibold">
                <SparklesIcon class="w-5 h-5" />
                Operations
              </div>
              <p class="text-sm text-blue-900 dark:text-blue-100 mt-2">Automation-specific operation types with reusable payloads and tagging.</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
              <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-200 font-semibold">
                <ShieldCheckIcon class="w-5 h-5" />
                Safety
              </div>
              <p class="text-sm text-emerald-900 dark:text-emerald-100 mt-2">Host-aware pacing, robots.txt awareness, and credential audits.</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
              <div class="flex items-center gap-2 text-amber-700 dark:text-amber-200 font-semibold">
                <ClockIcon class="w-5 h-5" />
                Scheduling
              </div>
              <p class="text-sm text-amber-900 dark:text-amber-100 mt-2">Reoccurring cadences that stagger traffic to mimic human behavior.</p>
            </div>
          </div>
        </div>
      </section>

      <section id="playbooks" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm uppercase tracking-widest text-stone-500 dark:text-stone-400">Blueprint</p>
            <h2 class="text-2xl font-semibold text-stone-900 dark:text-white">Backend plan</h2>
          </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <article
            v-for="item in blueprints"
            :key="item.name"
            class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-xl p-5 shadow-sm flex flex-col gap-3"
          >
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm text-stone-500 dark:text-stone-400 uppercase tracking-wide">{{ item.name }}</p>
                <p class="text-lg font-semibold text-stone-900 dark:text-white">{{ item.summary }}</p>
              </div>
            </div>
            <ul class="space-y-2 text-sm text-stone-700 dark:text-stone-200">
              <li v-for="point in item.items" :key="point" class="flex gap-2">
                <CheckCircleIcon class="w-5 h-5 text-emerald-500 shrink-0" />
                <span>{{ point }}</span>
              </li>
            </ul>
          </article>
        </div>
      </section>

      <section id="scheduling" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-3">
          <div>
            <p class="text-sm uppercase tracking-widest text-stone-500 dark:text-stone-400">Playbooks</p>
            <h3 class="text-xl font-semibold text-stone-900 dark:text-white">Common pipelines to configure</h3>
            <p class="text-sm text-stone-700 dark:text-stone-200">Each pipeline will become a reusable playbook that wires inputs, cadence, and output destinations.</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
              v-for="pipeline in pipelines"
              :key="pipeline.name"
              class="p-4 rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm"
            >
              <h4 class="text-lg font-semibold text-stone-900 dark:text-white">{{ pipeline.name }}</h4>
              <p class="text-sm text-stone-700 dark:text-stone-200 mt-1">{{ pipeline.description }}</p>
            </div>
          </div>
        </div>
        <div class="space-y-3">
          <div class="p-5 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 shadow-sm">
            <h4 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">Safety rails</h4>
            <ul class="mt-3 space-y-2 text-sm text-emerald-900 dark:text-emerald-100">
              <li v-for="item in safety" :key="item" class="flex gap-2 items-start">
                <ShieldCheckIcon class="w-5 h-5 mt-0.5" />
                <span>{{ item }}</span>
              </li>
            </ul>
          </div>
          <div class="p-5 rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 shadow-sm">
            <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Tooling</h4>
            <ul class="mt-3 space-y-2 text-sm text-blue-900 dark:text-blue-100">
              <li v-for="item in integrations" :key="item" class="flex gap-2 items-start">
                <SparklesIcon class="w-5 h-5 mt-0.5" />
                <span>{{ item }}</span>
              </li>
            </ul>
          </div>
        </div>
      </section>
    </div>
  </Manage>
</template>
