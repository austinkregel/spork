<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Shell from './Shell.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const props = defineProps({
  step: { type: String, required: true },
  steps: { type: Array, default: () => [] },
  state: { type: Object, default: () => ({}) },
});

const name = ref(props.state?.name ?? '');
const goal = ref(props.state?.goal ?? '');
const errors = ref({});
const saving = ref(false);

function submit() {
  saving.value = true;
  errors.value = {};
  router.post(
    '/-/projects/wizard/identity',
    { name: name.value, goal: goal.value },
    {
      preserveScroll: true,
      onError: (err) => (errors.value = err),
      onFinish: () => (saving.value = false),
    },
  );
}

function back() {
  router.visit('/-/projects/wizard/template');
}
</script>

<template>
  <Shell
    title="Name your project"
    subtitle="Give the project a recognizable name and capture the outcome you're aiming for."
    :step="step"
    :steps="steps"
  >
    <div class="grid grid-cols-1 gap-4">
      <GlassField
        v-slot="{ id, describedby, invalid }"
        label="Project name"
        :error="errors.name"
        required
      >
        <GlassInput
          :id="id"
          v-model="name"
          placeholder="e.g. Automation Ops"
          :invalid="invalid"
          :describedby="describedby"
          required
        />
      </GlassField>

      <GlassField
        v-slot="{ id, describedby, invalid }"
        label="Goal"
        hint="What outcome are you driving toward?"
        :error="errors.goal"
      >
        <textarea
          :id="id"
          v-model="goal"
          rows="3"
          :aria-invalid="invalid || undefined"
          :aria-describedby="describedby"
          placeholder="What outcome are you driving toward?"
          class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100 dark:placeholder:text-stone-500"
        />
      </GlassField>
    </div>

    <div class="mt-6 flex items-center justify-between">
      <GlassButton variant="ghost" :disabled="saving" @click="back">Back</GlassButton>
      <GlassButton :disabled="saving || !name" @click="submit">
        {{ saving ? 'Saving…' : 'Continue' }}
      </GlassButton>
    </div>
  </Shell>
</template>
