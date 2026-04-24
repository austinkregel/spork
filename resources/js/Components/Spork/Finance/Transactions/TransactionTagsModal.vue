<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import TagMultiSelect from '@/Components/Spork/Molecules/Tags/TagMultiSelect.vue';

const props = defineProps({
  tags: {
    type: Array,
    default: () => [],
  },
});

const isOpen = ref(false);
const transaction = ref(null);

const form = useForm({
  tag_ids: [],
});

const open = (t) => {
  transaction.value = t;
  form.clearErrors();
  form.tag_ids = (t?.tags ?? []).map((tag) => tag.id).filter(Boolean);
  isOpen.value = true;
};

const close = () => {
  isOpen.value = false;
  transaction.value = null;
  form.reset();
  form.clearErrors();
};

const submit = () => {
  const transactionId = Number(transaction.value?.id);

  if (!Number.isFinite(transactionId) || transactionId <= 0) {
    return;
  }

  form.put(route('finance.banking.transactions.tags.update', transactionId), {
    preserveScroll: true,
    onSuccess: close,
  });
};

defineExpose({ open });
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" class="relative z-50" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-stone-900 text-left align-middle shadow-xl transition-all border border-stone-200 dark:border-stone-800"
            >
              <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-800 flex items-center justify-between">
                <DialogTitle class="text-lg font-medium text-stone-900 dark:text-white">
                  Edit transaction tags
                </DialogTitle>
                <button
                  type="button"
                  @click="close"
                  class="text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white text-sm"
                >
                  Close
                </button>
              </div>

              <form class="px-6 py-6 space-y-4" @submit.prevent="submit">
                <div class="text-sm text-stone-600 dark:text-stone-300">
                  <div class="font-medium text-stone-900 dark:text-white truncate">
                    {{ transaction?.name ?? 'Transaction' }}
                  </div>
                </div>

                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Tags</label>
                  <TagMultiSelect v-model="form.tag_ids" :tags="tags" :error="form.errors.tag_ids" />
                </div>

                <div class="flex justify-end gap-3 pt-4">
                  <button
                    type="button"
                    @click="close"
                    class="px-4 py-2 text-sm rounded-lg border border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-900"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-4 py-2 text-sm rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
                    :disabled="form.processing"
                  >
                    Save Tags
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>


