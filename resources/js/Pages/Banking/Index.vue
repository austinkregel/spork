<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import OverviewPanel from "@/Pages/Banking/Partials/OverviewPanel.vue";
import AccountsPanel from "@/Pages/Banking/Partials/AccountsPanel.vue";
import BudgetsPanel from "@/Pages/Banking/Partials/BudgetsPanel.vue";
import TransactionsPanel from "@/Pages/Banking/Partials/TransactionsPanel.vue";
import SettingsPanel from "@/Pages/Banking/Partials/SettingsPanel.vue";

const props = defineProps({
  tab: {
    type: String,
    default: 'overview',
  },
  navigation: {
    type: Array,
    default: () => [],
  },
  overview: {
    type: Object,
    default: null,
  },
  accountsData: {
    type: Object,
    default: null,
  },
  budgetsData: {
    type: Object,
    default: null,
  },
  transactionsData: {
    type: Object,
    default: null,
  },
  settingsData: {
    type: Object,
    default: null,
  },
});

const panels = {
  overview: OverviewPanel,
  accounts: AccountsPanel,
  budgets: BudgetsPanel,
  transactions: TransactionsPanel,
  settings: SettingsPanel,
};

const CurrentPanel = computed(() => panels[props.tab] ?? OverviewPanel);
</script>

<template>
  <AppLayout title="Banking">
    <div class="max-w-7xl mx-auto px-4 py-6 flex flex-col lg:flex-row gap-6">
      <aside class="w-full lg:w-64">
        <nav class="rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-sm">
          <ul class="divide-y divide-stone-100 dark:divide-stone-800">
            <li v-for="item in navigation" :key="item.tab">
              <Link
                :href="item.href"
                class="flex items-center justify-between px-4 py-3 text-sm font-medium rounded-2xl lg:rounded-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
                :class="item.active
                  ? 'text-stone-900 dark:text-white bg-stone-100 dark:bg-stone-800'
                  : 'text-stone-500 dark:text-stone-300 hover:text-stone-900 hover:bg-stone-50 dark:hover:bg-stone-800/60'"
                :aria-current="item.active ? 'page' : undefined"
              >
                <span>{{ item.label }}</span>
                <span
                  class="w-2 h-2 rounded-full"
                  :class="item.active ? 'bg-emerald-500' : 'bg-stone-300 dark:bg-stone-700'"
                />
              </Link>
            </li>
          </ul>
        </nav>
      </aside>

      <section class="flex-1 min-h-[60vh]">
        <component
          :is="CurrentPanel"
          :overview="overview"
          :accounts-data="accountsData"
          :budgets-data="budgetsData"
          :transactions-data="transactionsData"
          :settings-data="settingsData"
        />
      </section>
    </div>
  </AppLayout>
</template>

