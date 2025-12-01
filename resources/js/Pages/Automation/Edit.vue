<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import Manage from "@/Layouts/Manage.vue";
import StepBuilder from "@/Components/Automation/StepBuilder.vue";

const props = defineProps({
  title: String,
  automation: Object,
  steps: Array,
});

const form = useForm({
  name: props.automation.name,
  slug: props.automation.slug,
  enabled: props.automation.enabled,
  cron_expression: props.automation.cron_expression,
  timezone: props.automation.timezone,
  pacing_per_host_ms: props.automation.pacing_per_host_ms,
  max_concurrency: props.automation.max_concurrency,
  tags: props.automation.tags?.map(t => t.id) ?? [],
  steps: props.steps ?? [],
});

const submit = () => {
  form.put(route('automation.automations.update', props.automation.id));
};
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation">
    <form @submit.prevent="submit" class="space-y-6">
      <header class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">Edit Automation</h1>
        <div class="flex items-center gap-2">
          <Link :href="route('automation.automations.show', automation.id)" class="px-3 py-2 text-sm rounded-md border border-stone-300 dark:border-stone-700">Cancel</Link>
          <button type="submit" class="px-3 py-2 text-sm rounded-md bg-indigo-500 dark:bg-indigo-600 text-white">Save</button>
        </div>
      </header>

      <section class="bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-lg p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Name</label>
            <input v-model="form.name" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-3 py-2 text-sm" required />
          </div>
          <div>
            <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Cron expression</label>
            <input v-model="form.cron_expression" placeholder="*/5 * * * *" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="form.enabled" />
              Enabled
            </label>
          </div>
          <div>
            <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Timezone</label>
            <input v-model="form.timezone" placeholder="UTC" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-3 py-2 text-sm" />
          </div>
        </div>
      </section>

      <section class="bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-lg p-4">
        <StepBuilder v-model="form.steps" />
      </section>
    </form>
  </Manage>
</template>


