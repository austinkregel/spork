<template>
  <GlassSurface :elevation="elevation" :class="['overflow-hidden flex flex-col', surfaceClass]">
    <header
      v-if="header || description || $slots.actions"
      class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-start sm:justify-between sm:px-5"
    >
      <div class="min-w-0">
        <h2 v-if="header" class="text-base font-semibold text-stone-900 dark:text-stone-50">
          {{ header }}
        </h2>
        <p v-if="description" class="mt-0.5 text-sm text-stone-600 dark:text-stone-300">
          {{ description }}
        </p>
      </div>
      <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
        <slot name="actions" />
      </div>
    </header>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
        <thead class="bg-stone-100/40 dark:bg-stone-800/40">
          <tr>
            <th
              v-for="(column, idx) in headers"
              :key="column.key ?? column.name ?? idx"
              scope="col"
              :class="[
                'px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-stone-600 dark:text-stone-300',
                column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : '',
                column.className ?? '',
              ]"
            >
              {{ column.name }}
            </th>
            <th v-if="$slots['row-actions']" scope="col" class="w-px px-4 py-2">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
          <tr v-if="loading" class="text-center">
            <td :colspan="totalColumns" class="px-4 py-6 text-sm text-stone-500 dark:text-stone-400">
              Loading…
            </td>
          </tr>
          <tr v-else-if="!items || items.length === 0" class="text-center">
            <td :colspan="totalColumns" class="px-4 py-6 text-sm text-stone-500 dark:text-stone-400">
              <slot name="empty">{{ emptyMessage }}</slot>
            </td>
          </tr>
          <template v-else>
            <tr
              v-for="(item, rowIdx) in items"
              :key="rowKey ? item[rowKey] ?? rowIdx : rowIdx"
              :class="[
                'transition-colors motion-reduce:transition-none',
                hoverable ? 'hover:bg-stone-100/50 dark:hover:bg-stone-700/40' : '',
                striped && rowIdx % 2 === 1 ? 'bg-stone-50/40 dark:bg-stone-800/30' : '',
              ]"
              @click="$emit('row-click', item)"
            >
              <td
                v-for="(column, colIdx) in headers"
                :key="(column.key ?? column.name ?? colIdx) + '-' + rowIdx"
                :class="[
                  'px-4 py-2 text-sm text-stone-700 dark:text-stone-200',
                  column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : '',
                  column.cellClass ?? '',
                ]"
              >
                <slot
                  :name="`cell:${column.key ?? column.accessor}`"
                  :item="item"
                  :value="resolveValue(column, item)"
                >
                  {{ resolveValue(column, item) }}
                </slot>
              </td>
              <td v-if="$slots['row-actions']" class="w-px whitespace-nowrap px-4 py-2 text-right">
                <slot name="row-actions" :item="item" />
              </td>
            </tr>
          </template>
        </tbody>
        <tfoot v-if="$slots.footer">
          <slot name="footer" />
        </tfoot>
      </table>
    </div>

    <footer v-if="$slots.pagination" class="border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-3">
      <slot name="pagination" />
    </footer>
  </GlassSurface>
</template>

<script setup>
import GlassSurface from './GlassSurface.vue';

const props = defineProps({
  headers: { type: Array, required: true },
  items: { type: Array, default: () => [] },
  header: { type: String, default: null },
  description: { type: String, default: null },
  rowKey: { type: String, default: 'id' },
  emptyMessage: { type: String, default: 'No data available' },
  loading: { type: Boolean, default: false },
  hoverable: { type: Boolean, default: true },
  striped: { type: Boolean, default: false },
  elevation: { type: String, default: 'sm' },
  surfaceClass: { type: String, default: '' },
});

defineEmits(['row-click']);

function resolveValue(column, item) {
  if (typeof column.accessor === 'function') {
    try {
      return column.accessor(item);
    } catch (e) {
      console.error('GlassTable accessor failed', column, e);
      return '';
    }
  }
  if (typeof column.accessor === 'string') {
    return item?.[column.accessor] ?? '';
  }
  if (column.key) {
    return item?.[column.key] ?? '';
  }
  return '';
}

const totalColumns = (props.headers?.length ?? 0) + 1;
</script>
