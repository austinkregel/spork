<script setup>
import { reactive, watch } from 'vue';
import Multiselect from '@/Components/Multiselect.vue';
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ title: '', message: '', level: 'info' }),
  },
});
const emit = defineEmits(['update:modelValue']);

const local = reactive({
  title: '',
  message: '',
  level: 'info',
  user_ids: [],
});

watch(
  () => props.modelValue,
  (val) => {
    const next = val || {};
    local.title = next.title ?? '';
    local.message = next.message ?? '';
    local.level = next.level ?? 'info';
  },
  { immediate: true, deep: false }
);

watch(
  local,
  (val) => {
    emit('update:modelValue', { ...val });
  },
  { deep: true }
);
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Title</label>
      <input v-model="local.title" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
    </div>
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Level</label>
      <select v-model="local.level" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
        <option value="info">info</option>
        <option value="success">success</option>
        <option value="warning">warning</option>
        <option value="error">error</option>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Recipients (Users)</label>
      <Multiselect
        :model-value="local.user_ids"
        @update:modelValue="v => local.user_ids = v"
        :options="userOptions"
        :multiple="true"
        class="mt-1 block w-full text-sm rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 focus:outline-none"
      />
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Message</label>
      <textarea v-model="local.message" rows="4" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm"></textarea>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      userOptions: [],
    };
  },
  async mounted() {
    const { data } = await axios.get('/api/suggest/models', { params: { type: 'App\\Models\\User', limit: 50 } });
    this.userOptions = (data.data || []).map(u => ({ value: u.id, label: u.label }));
  },
};
</script>

