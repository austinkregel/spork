<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Graph from "@/Components/Graph.vue";
import LinkAccount from "@/Components/Spork/Finance/LinkAccount.vue";
import ManualTransactionModal from "@/Components/Spork/Finance/ManualTransactionModal.vue";

const props = defineProps({
  overview: {
    type: Object,
    default: null,
  },
});

const manualModal = ref(null);

const stats = computed(() => props.overview?.stats ?? {});
const shouldShowGraph = computed(() => props.overview?.preferences?.settings?.show_graphs ?? true);
const graphLabels = computed(() => props.overview?.graphs?.labels ?? []);
const graphDatasets = computed(() => props.overview?.graphs?.datasets ?? []);
const pinnedAccounts = computed(() => props.overview?.pinned_accounts ?? []);
const pinnedBudgets = computed(() => props.overview?.pinned_budgets ?? []);

const openManualModal = () => manualModal.value?.open();

const currency = (value) => {
  if (value === undefined || value === null) {
    return '$0';
  }

  return Number(value).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
};
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm">
        <p class="text-xs uppercase text-stone-500">Total Income (current)</p>
        <p class="text-3xl font-semibold text-stone-900 dark:text-white">{{ currency(stats.total_income?.current) }}</p>
        <p class="text-sm text-stone-500 dark:text-stone-400">Prev: {{ currency(stats.total_income?.previous) }}</p>
      </div>
      <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm">
        <p class="text-xs uppercase text-stone-500">Total Expenses</p>
        <p class="text-3xl font-semibold text-stone-900 dark:text-white">{{ currency(stats.total_expenses?.current) }}</p>
        <p class="text-sm text-stone-500 dark:text-stone-400">Prev: {{ currency(stats.total_expenses?.previous) }}</p>
      </div>
      <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm">
        <p class="text-xs uppercase text-stone-500">Food & Lifestyle</p>
        <p class="text-3xl font-semibold text-stone-900 dark:text-white">{{ currency(stats.other?.current) }}</p>
        <p class="text-sm text-stone-500 dark:text-stone-400">Prev: {{ currency(stats.other?.previous) }}</p>
      </div>
    </div>

    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm" v-if="shouldShowGraph && graphDatasets.length">
      <Graph :labels="graphLabels" :datasets="graphDatasets" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 space-y-4 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-stone-500 dark:text-stone-400">Pinned Accounts</p>
            <p class="text-lg text-stone-900 dark:text-white font-semibold">{{ pinnedAccounts.length || overview?.accounts?.length || 0 }}</p>
          </div>
          <Link
            href="/-/banking/accounts"
            class="text-xs text-emerald-600 dark:text-emerald-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900 rounded-md"
          >
            Manage
          </Link>
        </div>
        <div v-if="pinnedAccounts.length" class="space-y-2">
          <div
            v-for="account in pinnedAccounts"
            :key="account.account_id"
            class="rounded-xl border border-stone-200 dark:border-stone-800 px-4 py-3 flex items-center justify-between"
          >
            <div>
              <p class="text-stone-900 dark:text-white font-medium">{{ account.name }}</p>
              <p class="text-xs text-stone-500 dark:text-stone-400">{{ account.type }} ••••{{ account.mask }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ currency(account.available ?? account.balance) }}</p>
              <p class="text-xs text-stone-500 dark:text-stone-400">Updated {{ account.updated_at ? new Date(account.updated_at).toLocaleDateString() : '—' }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-stone-500 dark:text-stone-400">Pin an account to keep it handy.</p>
      </div>

      <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 space-y-4 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-stone-500 dark:text-stone-400">Pinned Budgets</p>
            <p class="text-lg text-stone-900 dark:text-white font-semibold">{{ pinnedBudgets.length || overview?.budgets?.length || 0 }}</p>
          </div>
          <Link
            href="/-/banking/budgets"
            class="text-xs text-emerald-600 dark:text-emerald-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900 rounded-md"
          >
            Manage
          </Link>
        </div>
        <div v-if="pinnedBudgets.length" class="space-y-3">
          <div
            v-for="budget in pinnedBudgets"
            :key="budget.id"
            class="rounded-xl border border-stone-200 dark:border-stone-800 px-4 py-3"
          >
            <div class="flex items-center justify-between mb-2">
              <p class="text-stone-900 dark:text-white font-medium">{{ budget.name }}</p>
              <p class="text-xs text-stone-500 dark:text-stone-400">{{ budget.frequency }}</p>
            </div>
            <div class="h-2 rounded-full bg-stone-100 dark:bg-stone-800 overflow-hidden">
              <div
                class="h-2 rounded-full"
                :class="budget.current.usage_percentage >= 100 ? 'bg-rose-500' : 'bg-emerald-500'"
                :style="{ width: Math.min(budget.current.usage_percentage ?? 0, 150) + '%' }"
              />
            </div>
            <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 mt-2">
              <span>Spent {{ currency(budget.current.total_spend) }}</span>
              <span>Remaining {{ currency(budget.current.remaining) }}</span>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-stone-500 dark:text-stone-400">Pin a budget to monitor it here.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 space-y-3 shadow-sm">
        <p class="text-sm text-stone-900 dark:text-white font-medium">Quick Actions</p>
        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
            @click="openManualModal"
          >
            Add Manual Transaction
          </button>
          <Link
            href="/-/banking/accounts"
            class="px-4 py-2 rounded-lg bg-sky-50 text-sky-700 border border-sky-100 dark:bg-sky-500/10 dark:text-sky-300 dark:border-sky-500/30 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
          >
            Link Accounts
          </Link>
          <Link
            href="/-/banking/budgets"
            class="px-4 py-2 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-300 dark:border-indigo-500/30 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
          >
            View Budgets
          </Link>
        </div>
      </div>

      <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm">
        <p class="text-sm text-stone-900 dark:text-white font-medium mb-3">Link Additional Accounts</p>
        <LinkAccount variant="button" :accounts="overview?.accounts ?? []" />
      </div>
    </div>

    <ManualTransactionModal
      ref="manualModal"
      :accounts="overview?.accounts ?? []"
      :tags="overview?.tags ?? []"
    />
  </div>
</template>


