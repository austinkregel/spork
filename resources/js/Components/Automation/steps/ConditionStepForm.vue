<script setup>
import { reactive, watch, computed } from 'vue';
import InlineConditionsEditor from '@/Components/Automation/steps/InlineConditionsEditor.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ conditions: [], on_false: 'skip' }),
  },
  sources: {
    type: Array,
    default: () => [{
      value: 'globals',
      label: 'Global context',
      parameters: [],
    }],
  },
});

const emit = defineEmits(['update:modelValue']);

const local = reactive({
  conditions: [],
  on_false: 'skip',
  source: 'globals',
});

watch(
  () => props.modelValue,
  (val) => {
    local.conditions = Array.isArray(val?.conditions) ? val.conditions : [];
    local.on_false = val?.on_false ?? 'skip';
    local.source = val?.source ?? (props.sources[0]?.value ?? 'globals');
  },
  { immediate: true }
);

watch(
  () => ({ ...local }),
  (val) => {
    emit('update:modelValue', {
      conditions: val.conditions,
      on_false: val.on_false,
      source: val.source,
    });
  },
  { deep: true }
);

const selectedSource = computed(() => {
  return props.sources.find((s) => s.value === local.source) ?? props.sources[0] ?? { parameters: [] };
});
</script>

<template>
  <div class="space-y-4">
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Data source</label>
      <select v-model="local.source" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
        <option v-for="source in sources" :key="source.value" :value="source.value">{{ source.label }}</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Action when conditions fail</label>
      <select v-model="local.on_false" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
        <option value="skip">Skip remaining steps</option>
        <option value="fail">Fail automation</option>
      </select>
    </div>

    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Conditions</label>
      <InlineConditionsEditor v-model="local.conditions" :parameter-options="selectedSource?.parameters ?? []" />
    </div>
  </div>
</template>

