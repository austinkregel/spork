<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-50 lg:hidden" @close="onClose">
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

      <div class="fixed inset-x-0 bottom-0 z-50">
        <TransitionChild
          as="template"
          enter="motion-safe:ease-out motion-safe:duration-200 motion-safe:transform"
          enter-from="translate-y-full"
          enter-to="translate-y-0"
          leave="motion-safe:ease-in motion-safe:duration-150 motion-safe:transform"
          leave-from="translate-y-0"
          leave-to="translate-y-full"
        >
          <DialogPanel
            class="relative mx-auto flex w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border-t border-x border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] backdrop-blur-glass shadow-2xl"
            :style="`max-height: ${maxHeight}`"
          >
            <div class="flex items-center justify-center pt-2">
              <span aria-hidden="true" class="h-1.5 w-12 rounded-full bg-stone-300 dark:bg-stone-600" />
            </div>

            <header v-if="title || $slots.header" class="flex items-start justify-between gap-3 px-5 pb-2 pt-3">
              <div class="min-w-0">
                <DialogTitle v-if="title" class="text-base font-semibold text-stone-900 dark:text-stone-50">
                  {{ title }}
                </DialogTitle>
                <p v-if="description" class="mt-0.5 text-sm text-stone-600 dark:text-stone-300">
                  {{ description }}
                </p>
                <slot name="header" />
              </div>
              <button
                v-if="dismissible"
                type="button"
                class="-m-2 rounded-md p-2 text-stone-500 transition-colors motion-reduce:transition-none hover:bg-stone-200/60 hover:text-stone-700 dark:hover:bg-stone-700/60 dark:text-stone-400 dark:hover:text-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
                @click="onClose"
              >
                <span class="sr-only">Close</span>
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </header>

            <div class="overflow-y-auto px-5 pb-5 pt-1">
              <slot />
            </div>

            <footer
              v-if="$slots.footer"
              class="border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-5 py-3"
            >
              <slot name="footer" />
            </footer>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: null },
  description: { type: String, default: null },
  dismissible: { type: Boolean, default: true },
  maxHeight: { type: String, default: '85vh' },
});

const emit = defineEmits(['close']);

function onClose() {
  if (!props.dismissible) return;
  emit('close');
}
</script>
