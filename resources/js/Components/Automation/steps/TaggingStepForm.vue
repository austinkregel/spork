<script setup>
import { reactive, watch } from 'vue';
import Multiselect from '@/Components/Multiselect.vue';
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ target: { type: '', id: null }, action: 'attach', tag_ids: [] }),
  },
});
const emit = defineEmits(['update:modelValue']);

const local = reactive({
  target: { type: '', id: null },
  action: 'attach',
  tag_ids: [],
});

watch(
  () => props.modelValue,
  (val) => {
    const next = val || {};
    local.target = {
      type: next.target?.type ?? '',
      id: next.target?.id ?? null,
    };
    local.action = next.action ?? 'attach';
    local.tag_ids = Array.isArray(next.tag_ids) ? next.tag_ids.slice() : [];
  },
  { immediate: true, deep: false }
);

watch(
  local,
  (val) => {
    emit('update:modelValue', {
      target: { type: val.target?.type ?? '', id: val.target?.id ?? null },
      action: val.action ?? 'attach',
      tag_ids: Array.isArray(val.tag_ids) ? val.tag_ids.slice() : [],
    });
  },
  { deep: true }
);
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Target type</label>
      <Multiselect
        :model-value="local.target.type"
        @update:modelValue="v => { local.target.type = v?.fqcn ?? ''; local.target.id = null; }"
        :options="typeOptions"
        :searchable="true"
        track-by="fqcn"
        label="label"
        placeholder="Select model type"
        class="mt-1 block w-full text-sm rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 focus:outline-none"
      />
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Target</label>
      <Multiselect
        :model-value="local.target.id"
        @update:modelValue="v => local.target.id = v?.id ?? null"
        :options="targetOptions"
        :searchable="true"
        :internal-search="false"
        :loading="targetLoading"
        track-by="id"
        label="label"
        placeholder="Type to search"
        @search-change="searchTargets"
        :disabled="!local.target.type"
        class="mt-1 block w-full text-sm rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 focus:outline-none"
      />
    </div>
    <div>
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Action</label>
      <select v-model="local.action" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
        <option value="attach">attach</option>
        <option value="detach">detach</option>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Tags</label>
      <Multiselect
        :model-value="local.tag_ids"
        @update:modelValue="v => local.tag_ids = (v || []).map(opt => opt.id)"
        :options="tagOptions"
        :multiple="true"
        :searchable="true"
        :internal-search="false"
        :loading="tagLoading"
        track-by="id"
        label="name"
        placeholder="Type to search"
        @search-change="searchTags"
        class="mt-1 block w-full text-sm rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 focus:outline-none"
      >
        <template #option="{ option }">
          <span>{{ option.name?.en ?? option.name }}</span>
        </template>
        <template #tag="{ option }">
          <span>{{ option.name?.en ?? option.name }}</span>
        </template>
      </Multiselect>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      typeOptions: [],
      targetOptions: [],
      targetLoading: false,
      tagOptions: [],
      tagLoading: false,
    };
  },
  mounted() {
    this.fetchTypes();
  },
  methods: {
    async fetchTypes() {
      const { data } = await axios.get('/api/suggest/taggable-types');
      this.typeOptions = data.data.map(t => ({ ...t, value: t.fqcn }));
    },
    async searchTargets() {
      if (!this.$props?.modelValue?.target?.type && !this.local?.target?.type) return;
      const type = (this.local?.target?.type) || (this.$props.modelValue?.target?.type);
      const { data } = await axios.get('/api/suggest/models', { params: { type, limit: 50 } });
      this.targetOptions = data.data.map(i => ({ id: i.id, label: i.label, value: i.id }));
    },
    async searchTags() {
      const { data } = await axios.get(`/api/crud/tags`, { params: { 'filter[q]': '' } });
      this.tagOptions = (data.data || []).map(tag => ({ id: tag.id, name: tag.name, label: tag.name?.en ?? tag.name, value: tag.id }));
    },
  },
};
</script>


