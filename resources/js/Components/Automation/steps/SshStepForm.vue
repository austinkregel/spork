<script setup>
import { reactive, watch } from 'vue';
import Multiselect from '@/Components/Multiselect.vue';
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ server_id: null, credential_id: null, command: '', timeout_ms: 10000 }),
  },
});
const emit = defineEmits(['update:modelValue']);

const local = reactive({
  server_id: null,
  credential_id: null,
  command: '',
  timeout_ms: 10000,
});

watch(
  () => props.modelValue,
  (val) => {
    const next = val || {};
    local.server_id = next.server_id ?? null;
    local.credential_id = next.credential_id ?? null;
    local.command = next.command ?? '';
    local.timeout_ms = next.timeout_ms ?? 10000;
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
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Server</label>
      <Multiselect
        :model-value="local.server_id"
        @update:modelValue="v => local.server_id = v?.id ?? null"
        :options="serverOptions"
        :searchable="true"
        :loading="serverLoading"
        :internal-search="false"
        track-by="id"
        label="label"
        placeholder="Type to search servers"
        @search-change="searchServers"
        class="mt-1 block w-full text-sm rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 focus:outline-none"
      />
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Credential (optional)</label>
      <Multiselect
        :model-value="local.credential_id"
        @update:modelValue="v => local.credential_id = v?.id ?? null"
        :options="credOptions"
        :searchable="true"
        :loading="credLoading"
        :internal-search="false"
        track-by="id"
        label="label"
        placeholder="Type to search credentials"
        @search-change="searchCreds"
        class="mt-1 block w-full text-sm rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 focus:outline-none"
      />
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Command</label>
      <input v-model="local.command" placeholder="uptime" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
    </div>
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Timeout (ms)</label>
      <input v-model.number="local.timeout_ms" type="number" min="100" step="100" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      serverOptions: [],
      serverLoading: false,
      credOptions: [],
      credLoading: false,
    };
  },
  methods: {
    async searchServers() {
      // Preload first page
      const { data } = await axios.get(`/api/suggest/models`, { params: { type: 'App\\\\Models\\\\Server', limit: 50 } });
      this.serverOptions = data.data.map(i => ({ id: i.id, label: i.label, value: i.id }));
    },
    async searchCreds() {
      const { data } = await axios.get(`/api/suggest/models`, { params: { type: 'App\\\\Models\\\\Credential', limit: 50 } });
      this.credOptions = data.data.map(i => ({ id: i.id, label: i.label, value: i.id }));
    },
  },
  mounted() {
    this.searchServers();
    this.searchCreds();
  },
};
</script>



