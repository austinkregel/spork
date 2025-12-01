<script setup>
import { reactive, watch, computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  parameterOptions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

const local = reactive({
  items: [],
});

const comparators = [
  { value: 'EQUALS', name: 'equal to' },
  { value: 'NOT_EQUAL', name: 'not equal to' },
  { value: 'LIKE', name: 'like' },
  { value: 'NOTLIKE', name: 'not like' },
  { value: 'IN', name: 'in' },
  { value: 'NOTIN', name: 'not in' },
  { value: 'STARTS_WITH', name: 'starts with' },
  { value: 'ENDS_WITH', name: 'ends with' },
  { value: 'LESS_THAN', name: 'less than' },
  { value: 'LESS_THAN_EQUAL', name: 'less than equal' },
  { value: 'GREATER_THAN', name: 'greater than' },
  { value: 'GREATER_THAN_EQUAL', name: 'greater than equal' },
];

const computedParameters = computed(() => {
  return props.parameterOptions.length > 0 ? props.parameterOptions : [
    { value: 'email.from_email', name: 'Email Sender' },
    { value: 'email.subject', name: 'Email Subject' },
    { value: 'transaction.name', name: 'Transaction Name' },
    { value: 'transaction.amount', name: 'Transaction Amount' },
    { value: 'transaction.category.name', name: 'Category Name' },
    { value: 'article.headline', name: 'Article Title' },
    { value: 'article.content', name: 'Article Content' },
  ];
});

watch(
  () => props.modelValue,
  (val) => {
    const fallbackParam = computedParameters.value[0]?.value ?? '';
    local.items = Array.isArray(val)
      ? val.map((c) => ({
          parameter: c.parameter ?? fallbackParam,
          comparator: c.comparator ?? comparators[0].value,
          value: c.value ?? '',
        }))
      : [];
  },
  { immediate: true }
);

watch(
  () => local.items,
  (val) => emit('update:modelValue', val.map((c) => ({ ...c }))),
  { deep: true }
);

const addCondition = () => {
  const fallbackParam = computedParameters.value[0]?.value ?? '';
  local.items.push({
    parameter: fallbackParam,
    comparator: comparators[0].value,
    value: '',
  });
};

const removeCondition = (index) => {
  local.items = local.items.filter((_, i) => i !== index);
};
</script>

<template>
  <div class="flex flex-col gap-3">
    <div v-for="(condition, index) in local.items" :key="index" class="flex flex-col md:flex-row gap-2 items-start md:items-end">
      <div class="flex-1 w-full">
        <label class="text-xs text-stone-600 dark:text-stone-400 mb-1 block">Parameter</label>
        <select v-model="condition.parameter" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
          <option v-for="param in computedParameters" :key="param.value" :value="param.value">{{ param.name }}</option>
        </select>
      </div>
      <div class="flex-1 w-full">
        <label class="text-xs text-stone-600 dark:text-stone-400 mb-1 block">Comparator</label>
        <select v-model="condition.comparator" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
          <option v-for="comp in comparators" :key="comp.value" :value="comp.value">{{ comp.name }}</option>
        </select>
      </div>
      <div class="flex-1 w-full">
        <label class="text-xs text-stone-600 dark:text-stone-400 mb-1 block">Value</label>
        <input v-model="condition.value" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
      </div>
      <button type="button" @click="removeCondition(index)" class="text-xs px-3 py-1.5 rounded-md bg-red-500 text-white">Remove</button>
    </div>

    <div>
      <button type="button" @click="addCondition" class="px-3 py-1.5 text-sm rounded-md bg-emerald-500 text-white">Add Condition</button>
    </div>
  </div>
</template>

