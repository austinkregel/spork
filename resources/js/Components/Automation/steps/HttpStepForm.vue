<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import axios from 'axios';
import Multiselect from '@/Components/Multiselect.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      method: 'GET',
      url: '',
      headers: [],
      query: [],
      body: '',
      timeout_ms: 10000,
      auth: {
        credential_id: null,
        bearer: '',
      },
    }),
  },
});

const emit = defineEmits(['update:modelValue']);

const local = reactive({
  method: 'GET',
  url: '',
  headers: [],
  query: [],
  body: '',
  timeout_seconds: 10,
  auth: {
    credential_id: null,
    bearer: '',
  },
});

const credentialOptions = ref([]);

watch(
  () => props.modelValue,
  (val) => {
    const cfg = val ?? {};
    local.method = cfg.method ?? 'GET';
    local.url = cfg.url ?? '';
    local.headers = Array.isArray(cfg.headers) ? cfg.headers : [];
    local.query = Array.isArray(cfg.query) ? cfg.query : [];
    if (typeof cfg.body === 'string') {
      local.body = cfg.body;
    } else if (cfg.body) {
      local.body = JSON.stringify(cfg.body, null, 2);
    } else {
      local.body = '';
    }
    local.timeout_seconds = Math.max(1, Math.round((cfg.timeout_ms ?? 10000) / 1000));
    local.auth = {
      credential_id: cfg.auth?.credential_id ?? null,
      bearer: cfg.auth?.bearer ?? '',
    };
  },
  { immediate: true }
);

watch(
  () => ({ ...local }),
  (val) => {
    emit('update:modelValue', {
      method: val.method,
      url: val.url,
      headers: val.headers,
      query: val.query,
      body: parseBody(val.body),
      timeout_ms: Math.max(1, val.timeout_seconds) * 1000,
      auth: {
        credential_id: val.auth.credential_id,
        bearer: val.auth.bearer,
      },
    });
  },
  { deep: true }
);

const parseBody = (text) => {
  if (text === '') {
    return '';
  }
  try {
    return JSON.parse(text);
  } catch {
    return text;
  }
};

const addHeader = () => local.headers.push({ key: '', value: '' });
const removeHeader = (index) => local.headers.splice(index, 1);
const addQuery = () => local.query.push({ key: '', value: '' });
const removeQuery = (index) => local.query.splice(index, 1);

const fetchCredentials = async () => {
  const { data } = await axios.get('/api/suggest/models', { params: { type: 'App\\Models\\Credential', limit: 50 } });
  credentialOptions.value = (data.data || []).map((c) => ({ label: c.label, value: c.id }));
};

onMounted(fetchCredentials);
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div>
        <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Method</label>
        <select v-model="local.method" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
          <option v-for="method in ['GET','POST','PUT','PATCH','DELETE']" :key="method" :value="method">{{ method }}</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">URL</label>
        <input v-model="local.url" placeholder="https://example.com/api" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-3 py-2 text-sm" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200 mb-2">Headers</label>
        <div class="space-y-2">
          <div v-for="(header, idx) in local.headers" :key="`header-${idx}`" class="flex gap-2">
            <input v-model="header.key" placeholder="Header" class="flex-1 border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
            <input v-model="header.value" placeholder="Value" class="flex-1 border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
            <button type="button" class="text-xs text-red-600" @click="removeHeader(idx)">Remove</button>
          </div>
          <button type="button" class="text-xs text-indigo-600" @click="addHeader">Add header</button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200 mb-2">Query params</label>
        <div class="space-y-2">
          <div v-for="(param, idx) in local.query" :key="`param-${idx}`" class="flex gap-2">
            <input v-model="param.key" placeholder="Param" class="flex-1 border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
            <input v-model="param.value" placeholder="Value" class="flex-1 border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
            <button type="button" class="text-xs text-red-600" @click="removeQuery(idx)">Remove</button>
          </div>
          <button type="button" class="text-xs text-indigo-600" @click="addQuery">Add param</button>
        </div>
      </div>
    </div>

    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Body (JSON or raw)</label>
      <textarea v-model="local.body" rows="4" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-3 py-2 text-sm font-mono"></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Timeout (seconds)</label>
        <input type="number" min="1" v-model.number="local.timeout_seconds" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Credential (optional)</label>
        <Multiselect :options="credentialOptions" :model-value="local.auth.credential_id" @update:modelValue="(val) => local.auth.credential_id = val" />
      </div>
    </div>

    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Bearer token override</label>
      <input v-model="local.auth.bearer" placeholder="Optional bearer token" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-3 py-2 text-sm" />
    </div>
  </div>
</template>

