<template>
  <TransitionRoot as="template" :show="open">
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

      <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-0 sm:items-center sm:p-4">
          <TransitionChild
            as="template"
            enter="motion-safe:ease-out motion-safe:duration-200"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="motion-safe:ease-in motion-safe:duration-150"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              :class="[
                'relative w-full overflow-hidden rounded-t-xl sm:rounded-xl shadow-2xl',
                'border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]',
                'bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] backdrop-blur-glass',
                sizeClass,
              ]"
            >
              <div class="flex items-start gap-3 px-5 pt-5">
                <div class="min-w-0 flex-1 space-y-1">
                  <DialogTitle
                    as="h2"
                    class="text-base font-semibold text-stone-900 dark:text-stone-50"
                  >
                    {{ title }}
                  </DialogTitle>
                  <DialogDescription
                    v-if="description"
                    class="text-sm text-stone-600 dark:text-stone-300"
                  >
                    {{ description }}
                  </DialogDescription>
                </div>
                <button
                  v-if="dismissible"
                  type="button"
                  class="-m-1 rounded-md p-1 text-stone-500 transition-colors motion-reduce:transition-none hover:bg-stone-200/60 hover:text-stone-700 dark:text-stone-400 dark:hover:bg-stone-700/60 dark:hover:text-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                  @click="onClose"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </div>

              <div class="px-5 py-4">
                <slot />
              </div>

              <div
                v-if="$slots.footer"
                class="flex items-center justify-end gap-2 border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-5 py-3 bg-stone-50/40 dark:bg-stone-900/40"
              >
                <slot name="footer" />
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue';
import {
  Dialog,
  DialogDescription,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
  description: { type: String, default: null },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
  dismissible: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'update:open']);

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'sm:max-w-sm';
    case 'lg':
      return 'sm:max-w-2xl';
    case 'xl':
      return 'sm:max-w-4xl';
    default:
      return 'sm:max-w-lg';
  }
});

function onClose() {
  if (!props.dismissible) return;
  emit('close');
  emit('update:open', false);
}
</script>
