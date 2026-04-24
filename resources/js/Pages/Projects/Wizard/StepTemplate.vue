<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Shell from './Shell.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import { CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  step: { type: String, required: true },
  steps: { type: Array, default: () => [] },
  state: { type: Object, default: () => ({}) },
  templates: { type: Array, default: () => [] },
});

const selected = ref(props.state?.template ?? props.templates?.[0]?.key ?? 'custom');
const saving = ref(false);

function submit() {
  saving.value = true;
  router.post(
    '/-/projects/wizard/template',
    { template: selected.value },
    {
      preserveScroll: true,
      onFinish: () => (saving.value = false),
    },
  );
}
</script>

<template>
  <Shell
    title="Pick a starting template"
    subtitle="Templates seed the project with the resource sections you'll most likely need. You can change this later."
    :step="step"
    :steps="steps"
  >
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <button
        v-for="template in templates"
        :key="template.key"
        type="button"
        class="flex w-full items-start gap-3 rounded-md border p-4 text-left backdrop-blur-glass transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
        :class="selected === template.key
          ? 'border-indigo-400 bg-indigo-50/40 dark:border-indigo-400/60 dark:bg-indigo-500/10'
          : 'border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] hover:bg-stone-100/40 dark:hover:bg-stone-800/40'"
        :aria-pressed="selected === template.key"
        @click="selected = template.key"
      >
        <CheckCircleIcon
          v-if="selected === template.key"
          class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500"
          aria-hidden="true"
        />
        <span
          v-else
          class="mt-0.5 h-5 w-5 shrink-0 rounded-full border border-stone-300 dark:border-stone-600"
          aria-hidden="true"
        />
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold text-stone-900 dark:text-stone-50">
            {{ template.label }}
          </span>
          <span class="mt-1 block text-xs text-stone-600 dark:text-stone-300">
            {{ template.description }}
          </span>
        </span>
      </button>
    </div>

    <div class="mt-6 flex items-center justify-end">
      <GlassButton :disabled="saving || !selected" @click="submit">
        {{ saving ? 'Saving…' : 'Continue' }}
      </GlassButton>
    </div>
  </Shell>
</template>
