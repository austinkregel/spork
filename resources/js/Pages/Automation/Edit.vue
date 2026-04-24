<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import StepBuilder from '@/Components/Automation/StepBuilder.vue';
import { ArrowLeftIcon, BoltIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  title: { type: String, default: 'Edit Automation' },
  automation: { type: Object, required: true },
  steps: { type: Array, default: () => [] },
});

const form = useForm({
  name: props.automation.name,
  slug: props.automation.slug,
  enabled: props.automation.enabled,
  cron_expression: props.automation.cron_expression,
  timezone: props.automation.timezone,
  pacing_per_host_ms: props.automation.pacing_per_host_ms,
  max_concurrency: props.automation.max_concurrency,
  tags: props.automation.tags?.map((t) => t.id) ?? [],
  steps: props.steps ?? [],
});

const submit = () => {
  form.put(route('automations.automations.update', props.automation.id));
};
</script>

<template>
  <AppLayout :title="title">
    <form @submit.prevent="submit" class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <Link
        :href="route('automations.automations.show', automation.id)"
        class="inline-flex items-center gap-1 text-xs font-medium text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950 rounded-md px-1 py-0.5 w-fit"
      >
        <ArrowLeftIcon class="h-3.5 w-3.5" aria-hidden="true" />
        Back to {{ automation.name }}
      </Link>

      <header class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div class="flex items-start gap-3">
          <BoltIcon class="h-8 w-8 shrink-0 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
              Edit automation
            </p>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
              {{ automation.name }}
            </h1>
            <p class="mt-1 max-w-2xl text-sm text-stone-600 dark:text-stone-300">
              Update cadence, status, and steps. Saved changes apply on the next scheduled run.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <GlassButton
            variant="ghost"
            type="button"
            :href="route('automations.automations.show', automation.id)"
          >
            Cancel
          </GlassButton>
          <GlassButton
            type="submit"
            :icon-left="CheckIcon"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Saving…' : 'Save changes' }}
          </GlassButton>
        </div>
      </header>

      <GlassCard title="Details" subtitle="Identifying information and run cadence.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <GlassField
            v-slot="{ id, describedby, invalid }"
            label="Name"
            :error="form.errors.name"
            required
          >
            <GlassInput
              :id="id"
              v-model="form.name"
              :invalid="invalid"
              :describedby="describedby"
              required
            />
          </GlassField>

          <GlassField
            v-slot="{ id, describedby, invalid }"
            label="Cron expression"
            hint="Leave blank for manual-only runs."
            :error="form.errors.cron_expression"
          >
            <GlassInput
              :id="id"
              v-model="form.cron_expression"
              placeholder="*/5 * * * *"
              :invalid="invalid"
              :describedby="describedby"
            />
          </GlassField>

          <GlassField
            v-slot="{ id, describedby, invalid }"
            label="Timezone"
            hint="IANA name (defaults to UTC if blank)."
            :error="form.errors.timezone"
          >
            <GlassInput
              :id="id"
              v-model="form.timezone"
              placeholder="UTC"
              :invalid="invalid"
              :describedby="describedby"
            />
          </GlassField>

          <GlassField label="Status" :error="form.errors.enabled">
            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-stone-800 dark:text-stone-200">
              <input
                v-model="form.enabled"
                type="checkbox"
                class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800"
              />
              <span>Enabled — Spork may run this automation on its schedule.</span>
            </label>
          </GlassField>
        </div>
      </GlassCard>

      <GlassCard title="Steps" subtitle="Each step runs in order and can pass values to the next.">
        <StepBuilder v-model="form.steps" />
      </GlassCard>
    </form>
  </AppLayout>
</template>
