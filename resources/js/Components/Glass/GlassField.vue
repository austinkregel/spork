<template>
  <div class="space-y-1.5">
    <label
      v-if="label"
      :for="inputId"
      class="block text-sm font-medium text-stone-700 dark:text-stone-200"
    >
      {{ label }}
      <span v-if="required" class="text-red-500" aria-hidden="true">*</span>
    </label>

    <slot :id="inputId" :describedby="describedby" :invalid="Boolean(error)" />

    <p
      v-if="error"
      :id="errorId"
      class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1"
    >
      <ExclamationCircleIcon class="h-3.5 w-3.5" aria-hidden="true" />
      <span>{{ error }}</span>
    </p>
    <p
      v-else-if="hint"
      :id="hintId"
      class="text-xs text-stone-500 dark:text-stone-400"
    >
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue';
import { ExclamationCircleIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
  label: { type: String, default: null },
  hint: { type: String, default: null },
  error: { type: String, default: null },
  required: { type: Boolean, default: false },
  for: { type: String, default: null },
});

const generatedId = useId();
const inputId = computed(() => props.for ?? `glass-field-${generatedId}`);
const errorId = computed(() => `${inputId.value}-error`);
const hintId = computed(() => `${inputId.value}-hint`);
const describedby = computed(() => {
  if (props.error) return errorId.value;
  if (props.hint) return hintId.value;
  return undefined;
});
</script>
