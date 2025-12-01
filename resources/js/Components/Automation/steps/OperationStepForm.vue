<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import axios from 'axios';
import Multiselect from '@/Components/Multiselect.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      operation: '',
      queue: '',
      attributes: [],
    }),
  },
});

const emit = defineEmits(['update:modelValue']);

const local = reactive({
  operation: '',
  queue: '',
  attributes: [],
});

const normalizeAttributes = (attributes) => {
  if (Array.isArray(attributes)) {
    return attributes.map((pair) => ({
      key: pair?.key ?? '',
      value: pair?.value ?? '',
    }));
  }

  if (attributes && typeof attributes === 'object') {
    return Object.entries(attributes).map(([key, value]) => ({ key, value }));
  }

  return [];
};

const operationOptions = ref([]);

watch(
  () => props.modelValue,
  (val) => {
    local.operation = val?.operation ?? '';
    local.queue = val?.queue ?? '';
    local.attributes = normalizeAttributes(val?.attributes ?? []);
  },
  { immediate: true }
);

watch(
  () => ({ ...local }),
  (val) => {
    emit('update:modelValue', {
      operation: val.operation,
      queue: val.queue,
      attributes: val.attributes,
    });
  },
  { deep: true }
);

const addAttribute = () => {
  local.attributes.push({ key: '', value: '' });
};

const removeAttribute = (idx) => {
  local.attributes = local.attributes.filter((_, index) => index !== idx);
};

const fetchOperations = async () => {
  const { data } = await axios.get('/api/suggest/operations');
  operationOptions.value = (data.data || []).map((item) => ({
    label: item.label ?? item.value,
    value: item.value,
  }));
};

onMounted(fetchOperations);
</script>

<template>
  <div class="space-y-4">
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Operation</label>
      <Multiselect
        :options="operationOptions"
        :model-value="local.operation"
        placeholder="Select operation"
        @update:modelValue="(val) => (local.operation = val)"
      />
    </div>

    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Queue override (optional)</label>
      <input
        v-model="local.queue"
        placeholder="high-priority"
        class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-3 py-2 text-sm"
      />
    </div>

    <div>
      <div class="flex items-center justify-between mb-2">
        <label class="text-xs text-stone-600 dark:text-stone-300 block">Attributes</label>
        <button type="button" class="text-xs text-indigo-600" @click="addAttribute">Add attribute</button>
      </div>
      <div class="space-y-2">
        <div v-for="(attribute, idx) in local.attributes" :key="`attr-${idx}`" class="flex gap-2">
          <input
            v-model="attribute.key"
            placeholder="key"
            class="flex-1 border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm"
          />
          <input
            v-model="attribute.value"
            placeholder="value"
            class="flex-1 border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm"
          />
          <button type="button" class="text-xs text-red-600" @click="removeAttribute(idx)">Remove</button>
        </div>
      </div>
    </div>
  </div>
</template>

