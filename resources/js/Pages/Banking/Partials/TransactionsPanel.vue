<script setup>
import { reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ManualTransactionModal from "@/Components/Spork/Finance/ManualTransactionModal.vue";

const props = defineProps({
  transactionsData: {
    type: Object,
    default: null,
  },
});

const manualModal = ref(null);
const openManualModal = () => manualModal.value?.open();

const filters = reactive({
  name: props.transactionsData?.filters?.filter?.name ?? '',
  tag: props.transactionsData?.filters?.filter?.tag ?? '',
});

const applyFilters = () => {
  const params = {};

  if (filters.name) {
    params['filter[name]'] = filters.name;
  }

  if (filters.tag) {
    params['filter[tag]'] = filters.tag;
  }

  router.get(route('banking.transactions'), params, {
    preserveScroll: true,
    preserveState: true,
  });
};

watch(() => filters.tag, () => applyFilters());

const transactions = () => props.transactionsData?.transactions?.data ?? [];
const paginator = () => props.transactionsData?.transactions ?? {};

const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl text-stone-900 dark:text-white font-semibold">Transactions</h1>
        <p class="text-sm text-stone-500 dark:text-stone-400">Filter activity and add manual entries.</p>
      </div>
      <div class="flex items-center gap-3">
        <input
          v-model="filters.name"
          @keyup.enter="applyFilters"
          type="text"
          placeholder="Search name"
          class="rounded-xl bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
        />
        <select
          v-model="filters.tag"
          class="rounded-xl bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
        >
          <option value="">All tags</option>
          <option
            v-for="tag in transactionsData?.tags ?? []"
            :key="tag.id"
            :value="tag.name?.en ?? tag.name"
          >
            {{ tag.name?.en ?? tag.name }}
          </option>
        </select>
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
          @click="openManualModal"
        >
          Add Manual Transaction
        </button>
      </div>
    </div>

    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 overflow-x-auto shadow-sm">
      <table class="min-w-full divide-y divide-stone-100 dark:divide-stone-800 text-sm">
        <thead class="bg-stone-50 dark:bg-stone-900 text-stone-500 dark:text-stone-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Account</th>
            <th class="px-4 py-3 text-right">Amount</th>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-left">Tags</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
          <tr v-for="transaction in transactions()" :key="transaction.id">
            <td class="px-4 py-3 text-stone-900 dark:text-white">{{ transaction.name }}</td>
            <td class="px-4 py-3 text-stone-500 dark:text-stone-400">{{ transaction.account?.name }}</td>
            <td class="px-4 py-3 text-right font-semibold" :class="transaction.amount < 0 ? 'text-rose-500' : 'text-emerald-600 dark:text-emerald-400'">
              {{ currency(transaction.amount) }}
            </td>
            <td class="px-4 py-3 text-stone-500 dark:text-stone-400">{{ transaction.date }}</td>
            <td class="px-4 py-3 text-stone-500 dark:text-stone-400">
              <span v-for="tag in transaction.tags" :key="tag.id" class="inline-flex text-xs px-2 py-1 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-200 mr-2">
                {{ tag.name?.en ?? tag.name }}
              </span>
            </td>
          </tr>
          <tr v-if="transactions().length === 0">
            <td colspan="5" class="px-4 py-6 text-center text-stone-500 dark:text-stone-400">No transactions found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between text-sm text-stone-500 dark:text-stone-400">
      <button
        v-if="paginator().prev_page_url"
        @click="router.visit(paginator().prev_page_url, { preserveScroll: true, preserveState: true })"
        class="px-4 py-2 rounded-lg border border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-900 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
      >
        Previous
      </button>
      <span v-else />
      <button
        v-if="paginator().next_page_url"
        @click="router.visit(paginator().next_page_url, { preserveScroll: true, preserveState: true })"
        class="px-4 py-2 rounded-lg border border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-900 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
      >
        Next
      </button>
    </div>

    <ManualTransactionModal
      ref="manualModal"
      :accounts="transactionsData?.accounts ?? []"
      :tags="transactionsData?.tags ?? []"
    />
  </div>
</template>


