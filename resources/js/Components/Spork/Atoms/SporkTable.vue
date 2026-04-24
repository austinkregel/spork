<template>
  <div class="p-4 sm:p-6 lg:p-8">
    <div v-if="header || description" class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-50">
          {{ header }}
        </h1>
        <p v-if="description" class="mt-1 text-sm text-stone-600 dark:text-stone-300">
          {{ description }}
        </p>
      </div>
    </div>

    <GlassSurface class="mt-6 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
          <thead class="bg-stone-100/40 dark:bg-stone-800/40">
            <tr>
              <th
                v-for="(column, idx) in headers"
                :key="column.name + idx"
                scope="col"
                class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-stone-600 dark:text-stone-300"
              >
                {{ column.name }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
            <ContextMenu
              v-for="(item, rowIdx) in items"
              :key="rowIdx"
              as="tr"
              class="transition-colors motion-reduce:transition-none hover:bg-stone-100/50 dark:hover:bg-stone-700/40"
            >
              <td
                v-for="(column, colIdx) in headers"
                :key="column.name + colIdx + '-' + rowIdx"
                class="whitespace-nowrap px-4 py-2 text-sm text-stone-700 dark:text-stone-200"
              >
                {{ parseTheAccessor(column, item) }}
              </td>
              <template #items>
                <slot name="context-items" :item="item" />
              </template>
            </ContextMenu>
            <tr v-if="!items || items.length === 0">
              <td
                :colspan="headers.length"
                class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400"
              >
                No data available
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </GlassSurface>
  </div>
</template>

<script setup>
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import ContextMenu from '@/Components/ContextMenus/ContextMenu.vue';

defineProps({
  headers: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  header: { type: String, default: null },
  description: { type: String, default: null },
});

const parseTheAccessor = (column, value) => {
  if (typeof column.accessor === 'function') {
    try {
      return column.accessor(value);
    } catch (e) {
      console.error('Unable to execute the column accessor:', column, e);
      return 'an error occurred, check console for logs';
    }
  }

  if (!value) return column.name;
  return value[column?.accessor] ?? column.name;
};
</script>
