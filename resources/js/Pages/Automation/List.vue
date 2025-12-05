<script setup>
import { Link } from "@inertiajs/vue3";
import Manage from "@/Layouts/Manage.vue";

const props = defineProps({
  title: String,
  automations: Object,
});
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation" content-width-class="max-w-3xl">
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">Automations</h1>
        <Link :href="route('automation.automations.create')" class="px-3 py-2 text-sm rounded-md bg-indigo-500 dark:bg-indigo-600 text-white">New</Link>
      </div>

      <div class="bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-lg divide-y divide-stone-200 dark:divide-stone-600">
        <div v-for="a in automations.data" :key="a.id" class="p-4 flex items-center justify-between">
          <div>
            <div class="font-medium text-stone-900 dark:text-white">{{ a.name }}</div>
            <div class="text-sm text-stone-600 dark:text-stone-300">
              <span class="mr-2">Enabled: {{ a.enabled ? 'yes' : 'no' }}</span>
              <span class="mr-2">Cron: {{ a.cron_expression ?? '—' }}</span>
              <span class="mr-2">Steps: {{ a.steps_count ?? 0 }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Link :href="route('automation.automations.show', a.id)" class="px-2 py-1.5 text-xs rounded-md border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-200">View</Link>
            <Link :href="route('automation.automations.edit', a.id)" class="px-2 py-1.5 text-xs rounded-md border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-200">Edit</Link>
            <Link :href="route('automation.automations.run-now', a.id)" method="post" as="button" class="px-2 py-1.5 text-xs rounded-md bg-indigo-500 dark:bg-indigo-600 text-white">Run now</Link>
          </div>
        </div>
      </div>
    </div>
  </Manage>
  </template>


