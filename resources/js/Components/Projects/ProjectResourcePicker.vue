<template>
  <GlassSurface class="p-4">
    <div class="flex flex-col gap-3">
      <div class="flex flex-col gap-3 sm:flex-row">
        <GlassField label="Resource type" class="w-full sm:w-72">
          <GlassSelect v-model="resourceType">
            <option v-for="r in allowedTypes" :key="r.type" :value="r.type">
              {{ groupLabel(r.group) }} — {{ r.label }}
            </option>
          </GlassSelect>
        </GlassField>

        <GlassField label="Search" class="flex-1">
          <GlassInput v-model="query" type="search" placeholder="Search…" />
        </GlassField>
      </div>

      <p v-if="loading" class="text-sm text-stone-500 dark:text-stone-400">Searching…</p>

      <ul v-else-if="results.length" class="flex flex-col gap-2">
        <li
          v-for="result in results"
          :key="result.id"
          class="flex items-center justify-between gap-3 rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-3 py-2"
        >
          <div class="min-w-0">
            <p class="truncate text-sm text-stone-800 dark:text-stone-100">{{ result.label }}</p>
            <p class="text-xs text-stone-500 dark:text-stone-400">
              {{ simpleTypeLabel(resourceType) }} #{{ result.id }}
            </p>
          </div>

          <GlassButton
            size="sm"
            :disabled="isSelected(resourceType, result.id)"
            @click="add(result)"
          >
            {{ isSelected(resourceType, result.id) ? 'Added' : 'Add' }}
          </GlassButton>
        </li>
      </ul>

      <p v-else-if="resourceType" class="text-sm text-stone-500 dark:text-stone-400">No matches.</p>

      <div v-if="selected.length" class="mt-2">
        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Selected</p>
        <ul class="mt-2 flex flex-col gap-2">
          <li
            v-for="item in selected"
            :key="item.resource_type + ':' + item.resource_id"
            class="flex items-center justify-between gap-3 rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-3 py-2"
          >
            <div class="min-w-0">
              <p class="truncate text-sm text-stone-800 dark:text-stone-100">
                {{ item.label ?? item.resource_id }}
              </p>
              <p class="text-xs text-stone-500 dark:text-stone-400">
                {{ simpleTypeLabel(item.resource_type) }} #{{ item.resource_id }}
              </p>
            </div>
            <GlassButton variant="secondary" size="sm" @click="$emit('remove', item)">Remove</GlassButton>
          </li>
        </ul>
      </div>
    </div>
  </GlassSurface>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';

const props = defineProps({
  registry: { type: Object, required: true },
  allowedTypes: { type: Array, default: () => [] },
  allowedGroups: { type: Array, default: () => [] },
  selected: { type: Array, default: () => [] },
});

const emit = defineEmits(['add', 'remove']);

const resourceType = ref(null);
const query = ref('');
const loading = ref(false);
const results = ref([]);

const allowedTypes = computed(() => {
  const resources = props.registry?.resources ?? [];
  const types = props.allowedTypes ?? [];
  const groups = props.allowedGroups ?? [];

  if (types.length) {
    return resources.filter((r) => types.includes(r.type));
  }
  if (!groups.length) {
    return resources;
  }
  return resources.filter((r) => groups.includes(r.group));
});

watch(
  allowedTypes,
  (types) => {
    if (!types.length) {
      resourceType.value = null;
      return;
    }
    if (!resourceType.value || !types.some((t) => t.type === resourceType.value)) {
      resourceType.value = types[0].type;
    }
  },
  { immediate: true },
);

let debounceTimer = null;
watch(
  [resourceType, query],
  async () => {
    results.value = [];
    if (!resourceType.value) return;

    if (debounceTimer) clearTimeout(debounceTimer);

    debounceTimer = setTimeout(async () => {
      loading.value = true;
      try {
        const { data } = await axios.get('/api/suggest/models', {
          params: {
            type: resourceType.value,
            q: query.value || undefined,
            limit: 20,
          },
        });
        results.value = data?.data ?? [];
      } finally {
        loading.value = false;
      }
    }, 200);
  },
  { immediate: true },
);

function isSelected(type, id) {
  return props.selected.some((i) => i.resource_type === type && i.resource_id === id);
}

function add(result) {
  if (!resourceType.value) return;
  if (isSelected(resourceType.value, result.id)) return;

  emit('add', {
    resource_type: resourceType.value,
    resource_id: result.id,
    label: result.label,
  });
}

function groupLabel(groupKey) {
  const groups = props.registry?.groups ?? {};
  return groups[groupKey] ?? groupKey;
}

function simpleTypeLabel(type) {
  const resources = props.registry?.resources ?? [];
  const meta = resources.find((r) => r.type === type);
  return meta?.label ?? type;
}
</script>
