<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-40 lg:hidden" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="motion-safe:transition-opacity motion-safe:ease-linear motion-safe:duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="motion-safe:transition-opacity motion-safe:ease-linear motion-safe:duration-300"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-stone-900/80 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 z-40 flex">
        <TransitionChild
          as="template"
          enter="motion-safe:transition motion-safe:ease-in-out motion-safe:duration-300 motion-safe:transform"
          enter-from="-translate-x-full"
          enter-to="translate-x-0"
          leave="motion-safe:transition motion-safe:ease-in-out motion-safe:duration-300 motion-safe:transform"
          leave-from="translate-x-0"
          leave-to="-translate-x-full"
        >
          <DialogPanel
            class="relative flex w-full max-w-xs flex-1 flex-col backdrop-blur-glass border-r border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-rail-light)] dark:bg-[var(--color-glass-rail-dark)]"
          >
            <div class="flex h-16 items-center justify-between px-4 border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]">
              <span class="text-sm font-semibold text-stone-700 dark:text-stone-100">Navigate</span>
              <button
                type="button"
                class="rounded-md p-2 -m-2 text-stone-500 transition-colors motion-reduce:transition-none hover:bg-stone-200/60 hover:text-stone-700 dark:text-stone-400 dark:hover:bg-stone-700/60 dark:hover:text-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
                @click="$emit('close')"
              >
                <span class="sr-only">Close</span>
                <XMarkIcon class="h-6 w-6" aria-hidden="true" />
              </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-1" role="navigation" aria-label="Mobile primary">
              <Link
                v-for="p in pillars"
                :key="p.slug"
                :href="p.href"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-stone-700 transition-colors motion-reduce:transition-none hover:bg-stone-200/70 hover:text-stone-900 dark:text-stone-200 dark:hover:bg-stone-700/70 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                @click="$emit('close')"
              >
                <DynamicIcon :icon-name="p.icon" class="h-6 w-6" aria-hidden="true" />
                <span>{{ p.label }}</span>
              </Link>
            </nav>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import DynamicIcon from '@/Components/DynamicIcon.vue';

defineEmits(['close']);

defineProps({
  open: { type: Boolean, default: false },
  pillars: { type: Array, default: () => [] },
});
</script>
