<script setup>
import { computed, ref } from 'vue';
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
const editingBudgetId = ref(null);

const form = useForm({
  name: '',
  amount: '',
  frequency: 'MONTHLY',
  interval: 1,
  started_at: new Date().toISOString().substring(0, 10),
  count: null,
  tag_ids: [],
});

const frequencyOptions = [
  { value: 'DAILY', label: 'Daily' },
  { value: 'WEEKLY', label: 'Weekly' },
  { value: 'BIWEEKLY', label: 'Biweekly' },
  { value: 'SEMIMONTHLY', label: 'Semi-monthly' },
  { value: 'MONTHLY', label: 'Monthly' },
  { value: 'BIMONTHLY', label: 'Bi-monthly' },
  { value: 'YEARLY', label: 'Yearly' },
];

const title = computed(() => (editingBudgetId.value ? 'Edit Budget' : 'New Budget'));
const submitLabel = computed(() => (editingBudgetId.value ? 'Save Budget' : 'Create Budget'));

const open = (budget = null) => {
  form.clearErrors();

  if (!budget) {
    editingBudgetId.value = null;
    form.reset();
    form.frequency = 'MONTHLY';
    form.interval = 1;
    form.started_at = new Date().toISOString().substring(0, 10);
    form.tag_ids = [];
    isOpen.value = true;
    return;
  }

  editingBudgetId.value = budget.id;
  form.name = budget.name ?? '';
  form.amount = budget.amount ?? '';
  form.frequency = (budget.frequency ?? 'MONTHLY').toUpperCase();
  form.interval = budget.interval ?? 1;
  form.started_at = (budget.started_at ?? new Date().toISOString().substring(0, 10)).substring(0, 10);
  form.count = budget.count ?? null;
  form.tag_ids = (budget.tags ?? []).map((t) => t.id).filter(Boolean);

  isOpen.value = true;
};

const close = () => {
  isOpen.value = false;
  editingBudgetId.value = null;
  form.reset();
  form.clearErrors();
};

const submit = () => {
  if (editingBudgetId.value) {
    form.put(route('finance.banking.budgets.update', editingBudgetId.value), {
      preserveScroll: true,
      onSuccess: close,
    });
    return;
  }

  form.post(route('finance.banking.budgets.store'), {
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
                  {{ title }}
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
                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                    placeholder="Groceries"
                  />
                  <div v-if="form.errors.name" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-1">
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Amount</label>
                    <input
                      v-model="form.amount"
                      type="number"
                      step="0.01"
                      class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                      placeholder="500.00"
                    />
                    <div v-if="form.errors.amount" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.amount }}</div>
                  </div>

                  <div class="space-y-1">
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Frequency</label>
                    <select
                      v-model="form.frequency"
                      class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                    >
                      <option v-for="opt in frequencyOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>
                    <div
                      v-if="form.errors.frequency"
                      class="text-xs text-red-500 dark:text-red-400"
                    >
                      {{ form.errors.frequency }}
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-1">
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Interval</label>
                    <input
                      v-model="form.interval"
                      type="number"
                      min="1"
                      step="1"
                      class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                      placeholder="1"
                    />
                    <div v-if="form.errors.interval" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.interval }}</div>
                  </div>

                  <div class="space-y-1">
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Start date</label>
                    <input
                      v-model="form.started_at"
                      type="date"
                      class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                    />
                    <div
                      v-if="form.errors.started_at"
                      class="text-xs text-red-500 dark:text-red-400"
                    >
                      {{ form.errors.started_at }}
                    </div>
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
                    {{ submitLabel }}
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





