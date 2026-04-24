<template>
  <TransitionRoot as="template" :show="open" appear>
    <Dialog as="div" class="relative z-50" @close="onClose">
      <TransitionChild
        as="template"
        enter="motion-safe:ease-out motion-safe:duration-200"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="motion-safe:ease-in motion-safe:duration-150"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm" aria-hidden="true" />
      </TransitionChild>

      <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20">
        <TransitionChild
          as="template"
          enter="motion-safe:ease-out motion-safe:duration-200"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="motion-safe:ease-in motion-safe:duration-150"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <Combobox
            as="div"
            class="mx-auto max-w-2xl overflow-hidden rounded-xl border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] backdrop-blur-glass shadow-2xl"
            @update:model-value="onSelect"
          >
            <div class="relative">
              <MagnifyingGlassIcon
                class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-stone-400 dark:text-stone-500"
                aria-hidden="true"
              />
              <ComboboxInput
                class="h-12 w-full bg-transparent border-0 pl-11 pr-4 text-stone-900 placeholder:text-stone-400 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder:text-stone-500 sm:text-sm"
                :placeholder="placeholder"
                @change="onQueryChange"
              />
            </div>

            <ComboboxOptions
              v-if="results.length > 0"
              class="max-h-80 scroll-py-2 divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] overflow-y-auto"
              static
            >
              <li v-for="group in groupedResults" :key="group.label">
                <h3 v-if="group.label" class="bg-stone-100/40 dark:bg-stone-800/40 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                  {{ group.label }}
                </h3>
                <ul class="mt-1 text-sm text-stone-700 dark:text-stone-200">
                  <ComboboxOption
                    v-for="item in group.items"
                    :key="item.id ?? item.label"
                    v-slot="{ active }"
                    :value="item"
                    as="template"
                  >
                    <li
                      :class="[
                        'flex cursor-default select-none items-center gap-3 px-4 py-2',
                        active ? 'bg-indigo-500 text-white' : '',
                      ]"
                    >
                      <component
                        v-if="item.icon"
                        :is="iconFor(item.icon)"
                        :class="['h-5 w-5 shrink-0', active ? 'text-white' : 'text-stone-400 dark:text-stone-500']"
                        aria-hidden="true"
                      />
                      <span class="flex-1 truncate">{{ item.label }}</span>
                      <span v-if="item.hint" :class="['ml-3 truncate text-xs', active ? 'text-indigo-100' : 'text-stone-400 dark:text-stone-500']">
                        {{ item.hint }}
                      </span>
                    </li>
                  </ComboboxOption>
                </ul>
              </li>
            </ComboboxOptions>

            <div
              v-else-if="query !== ''"
              class="px-6 py-10 text-center text-sm text-stone-500 dark:text-stone-400"
            >
              No results found for "{{ query }}".
            </div>

            <div v-if="$slots.footer" class="border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-2 text-xs text-stone-500 dark:text-stone-400">
              <slot name="footer" />
            </div>
          </Combobox>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import {
  Combobox,
  ComboboxInput,
  ComboboxOption,
  ComboboxOptions,
  Dialog,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import * as outline from '@heroicons/vue/24/outline';

const props = defineProps({
  open: { type: Boolean, default: false },
  results: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Search…' },
});

const emit = defineEmits(['close', 'query', 'select']);

const query = ref('');

watch(
  () => props.open,
  (next) => {
    if (!next) query.value = '';
  },
);

function onQueryChange(event) {
  query.value = event.target.value;
  emit('query', query.value);
}

function onSelect(item) {
  if (!item) return;
  emit('select', item);
  emit('close');
}

function onClose() {
  emit('close');
}

function iconFor(name) {
  return outline[name] ?? null;
}

const groupedResults = computed(() => {
  const groups = new Map();
  for (const item of props.results) {
    const label = item.group ?? '';
    if (!groups.has(label)) groups.set(label, { label, items: [] });
    groups.get(label).items.push(item);
  }
  return Array.from(groups.values());
});
</script>
