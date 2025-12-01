<script setup>
import { Link } from "@inertiajs/vue3";
import Manage from "@/Layouts/Manage.vue";

const props = defineProps({
  title: String,
  automation: Object,
  steps: Array,
});
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation">
    <div class="space-y-6">
      <header class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">{{ automation.name }}</h1>
          <p class="text-sm text-stone-600 dark:text-stone-300">Cron: {{ automation.cron_expression ?? '—' }}</p>
        </div>
        <div class="flex items-center gap-2">
          <Link :href="route('automation.automations.edit', automation.id)" class="px-3 py-2 text-sm rounded-md border border-stone-300 dark:border-stone-700">Edit</Link>
          <Link :href="route('automation.automations.run-now', automation.id)" method="post" as="button" class="px-3 py-2 text-sm rounded-md bg-indigo-500 dark:bg-indigo-600 text-white">Run now</Link>
        </div>
      </header>

      <section class="bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-lg p-4">
        <h3 class="text-sm font-medium text-stone-800 dark:text-stone-200 mb-3">Steps</h3>
        <ol class="list-decimal list-inside space-y-2">
          <li v-for="s in steps" :key="s.id" class="text-sm text-stone-700 dark:text-stone-200">
            <span class="font-medium">{{ s.type }}</span>
            <span class="text-stone-500 dark:text-stone-400">#{{ s.order }}</span>
            <span class="ml-2">{{ JSON.stringify(s.config) }}</span>
          </li>
        </ol>
      </section>
    </div>
  </Manage>
</template>


