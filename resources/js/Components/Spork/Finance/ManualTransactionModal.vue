<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
  accounts: {
    type: Array,
    default: () => [],
  },
  tags: {
    type: Array,
    default: () => [],
  },
});

const isOpen = ref(false);

const form = useForm({
  account_id: '',
  name: '',
  amount: '',
  date: new Date().toISOString().substring(0, 10),
  tags: [],
  notes: '',
});

const open = () => {
  if (!form.account_id && props.accounts.length) {
    form.account_id = props.accounts[0].account_id || props.accounts[0].id;
  }

  isOpen.value = true;
};

const close = () => {
  isOpen.value = false;
  form.reset();
  form.clearErrors();
};

const submit = () => {
  form.post(route('banking.manual-transactions.store'), {
    preserveScroll: true,
    onSuccess: close,
  });
};

defineExpose({
  open,
});
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
            <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-stone-900 text-left align-middle shadow-xl transition-all border border-stone-200 dark:border-stone-800">
              <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-800 flex items-center justify-between">
                <DialogTitle class="text-lg font-medium text-stone-900 dark:text-white">
                  Add Manual Transaction
                </DialogTitle>
                <button @click="close" class="text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white text-sm">Close</button>
              </div>
              <form class="px-6 py-6 space-y-4" @submit.prevent="submit">
                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Account</label>
                  <select
                    v-model="form.account_id"
                class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                  >
                    <option v-for="account in accounts" :key="account.account_id || account.id" :value="account.account_id || account.id">
                      {{ account.name }} ••••{{ account.mask }}
                    </option>
                  </select>
                  <div v-if="form.errors.account_id" class="text-xs text-red-400">{{ form.errors.account_id }}</div>
                </div>

                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                    placeholder="Netflix Subscription"
                  />
                  <div v-if="form.errors.name" class="text-xs text-red-400">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-1">
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Amount</label>
                    <input
                      v-model="form.amount"
                      type="number"
                      step="0.01"
                  class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                      placeholder="-45.00"
                    />
                    <div v-if="form.errors.amount" class="text-xs text-red-400">{{ form.errors.amount }}</div>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Date</label>
                    <input
                      v-model="form.date"
                      type="date"
                  class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                    />
                    <div v-if="form.errors.date" class="text-xs text-red-400">{{ form.errors.date }}</div>
                  </div>
                </div>

                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Tags</label>
                  <select
                    v-model="form.tags"
                    multiple
                class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white h-28 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                  >
                    <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                      {{ tag.name?.en ?? tag.name }}
                    </option>
                  </select>
                  <div v-if="form.errors.tags" class="text-xs text-red-400">{{ form.errors.tags }}</div>
                </div>

                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Notes</label>
                  <textarea
                    v-model="form.notes"
                class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                    rows="3"
                    placeholder="Any additional context..."
                  />
                  <div v-if="form.errors.notes" class="text-xs text-red-400">{{ form.errors.notes }}</div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                  <button type="button" @click="close" class="px-4 py-2 text-sm rounded-lg border border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-900">
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-4 py-2 text-sm rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white disabled:opacity-50"
                    :disabled="form.processing"
                  >
                    Save Transaction
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


