<script setup>
import { reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ManualTransactionModal from "@/Components/Spork/Finance/ManualTransactionModal.vue";
import TransactionsTable from '@/Components/Spork/Finance/Transactions/TransactionsTable.vue';
import PrevNextPagination from '@/Components/Spork/Molecules/Pagination/PrevNextPagination.vue';

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
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
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

    <TransactionsTable :transactions="transactions()" :tags="transactionsData?.tags ?? []" />

    <PrevNextPagination :paginator="paginator()" />

    <ManualTransactionModal
      ref="manualModal"
      :accounts="transactionsData?.accounts ?? []"
      :tags="transactionsData?.tags ?? []"
    />
  </div>
</template>


