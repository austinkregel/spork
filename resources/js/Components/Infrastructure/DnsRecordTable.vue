<template>
  <GlassSurface class="overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-[var(--color-glass-border-light)] text-sm dark:divide-[var(--color-glass-border-dark)]">
        <thead class="bg-stone-100/40 dark:bg-stone-800/40">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400"
            >
              {{ column.label }}
            </th>
            <th v-if="showActions" class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
          <tr v-for="record in records" :key="record.id ?? `${record.type}-${record.name}`">
            <td class="px-4 py-3 font-semibold text-stone-800 dark:text-stone-50">{{ record.type }}</td>
            <td class="px-4 py-3 text-stone-700 dark:text-stone-200">{{ record.name }}</td>
            <td class="px-4 py-3 text-stone-700 dark:text-stone-200">{{ record.ttl ?? 'Auto' }}</td>
            <td class="px-4 py-3 whitespace-pre-wrap font-mono text-xs text-stone-800 dark:text-stone-100">
              {{ record.value }}
            </td>
            <td v-if="showActions" class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <GlassButton variant="secondary" size="sm" @click="$emit('edit', record)">Edit</GlassButton>
                <GlassButton variant="destructive" size="sm" @click="$emit('delete', record)">Remove</GlassButton>
              </div>
            </td>
          </tr>
          <tr v-if="!records.length">
            <td
              :colspan="columns.length + (showActions ? 1 : 0)"
              class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400"
            >
              {{ emptyMessage }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </GlassSurface>
</template>

<script setup>
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';

defineProps({
  records: { type: Array, default: () => [] },
  columns: {
    type: Array,
    default: () => [
      { key: 'type', label: 'Type' },
      { key: 'name', label: 'Name' },
      { key: 'ttl', label: 'TTL' },
      { key: 'value', label: 'Value' },
    ],
  },
  showActions: { type: Boolean, default: true },
  emptyMessage: { type: String, default: 'No DNS records available.' },
});

defineEmits(['edit', 'delete']);
</script>
