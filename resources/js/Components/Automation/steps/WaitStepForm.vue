<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ ms: 0 }),
  },
});

const emit = defineEmits(['update:modelValue']);

const local = reactive({
  seconds: 0,
});

watch(
  () => props.modelValue,
  (val) => {
    const ms = Number(val?.ms ?? 0);
    local.seconds = Math.round(ms / 1000);
  },
  { immediate: true }
);

watch(
  () => local.seconds,
  (val) => emit('update:modelValue', { ms: Math.max(0, Number(val)) * 1000 }),
);
</script>

<template>
  <div class="grid grid-cols-1 gap-3">
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Delay (seconds)</label>
      <input
        v-model.number="local.seconds"
        type="number"
        min="0"
        max="300"
        class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm"
      />
      <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">Maximum 300 seconds (5 minutes).</p>
    </div>
  </div>
</template>

