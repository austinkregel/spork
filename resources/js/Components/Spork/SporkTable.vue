<template>
  <GlassSurface class="overflow-hidden">
    <div
      v-if="$slots['table-top']"
      class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-4 py-2"
    >
      <slot name="table-top" />
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-left">
        <thead class="bg-stone-100/40 dark:bg-stone-800/40">
          <tr>
            <th
              v-for="(header, idx) in headers"
              :key="'crud-header-' + idx"
              scope="col"
              class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-stone-600 dark:text-stone-300"
            >
              {{ header }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
          <tr
            v-for="(datum, rowIdx) in data"
            :key="rowIdx"
            class="text-sm text-stone-700 dark:text-stone-200"
          >
            <td class="px-4 py-2 align-middle" :colspan="headers.length || 1">
              <slot name="datum" :datum="datum" />
            </td>
          </tr>
          <tr v-if="!data || data.length === 0">
            <td
              :colspan="headers.length || 1"
              class="px-4 py-6 text-center text-sm italic text-stone-500 dark:text-stone-400"
            >
              <slot name="no-data">No data present in table</slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="$slots['table-bottom']"
      class="border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-4 py-2"
    >
      <slot name="table-bottom" />
    </div>
  </GlassSurface>
</template>

<script setup>
import GlassSurface from '@/Components/Glass/GlassSurface.vue';

defineProps({
  headers: { type: Array, required: true, default: () => [] },
  data: { type: Array, required: true, default: () => [] },
});
</script>
