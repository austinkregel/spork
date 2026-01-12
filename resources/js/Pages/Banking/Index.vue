<script setup>
import { computed } from 'vue';
import SplitNavigationShell from '@/Layouts/SplitNavigationShell.vue';
import OverviewPanel from "@/Pages/Banking/Partials/OverviewPanel.vue";
import AccountsPanel from "@/Pages/Banking/Partials/AccountsPanel.vue";
import BudgetsPanel from "@/Pages/Banking/Partials/BudgetsPanel.vue";
import TransactionsPanel from "@/Pages/Banking/Partials/TransactionsPanel.vue";
import PrivacyPanel from "@/Pages/Banking/Partials/PrivacyPanel.vue";
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
  privacyData: {
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
  privacy: PrivacyPanel,
  settings: SettingsPanel,
};

const CurrentPanel = computed(() => panels[props.tab] ?? OverviewPanel);
const navigationItems = computed(() =>
  props.navigation.map((item) => ({
    ...item,
    name: item.label,
    label: item.label,
    active: item.active ?? item.tab === props.tab,
  }))
);
const activeNav = computed(() => navigationItems.value.find((item) => item.active));
</script>

<template>
  <SplitNavigationShell
    title="Banking"
    subtitle="Finance cockpit"
    :nav-items="navigationItems"
    content-width-class="max-w-none"
    content-padding-class="px-4 lg:px-6 pt-2 pb-4"
  >
    <template #header>
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Banking
          </p>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">
            {{ activeNav?.label ?? 'Overview' }}
          </h1>
        </div>
      </div>
    </template>

    <section class="rounded-2xl bg-white dark:bg-stone-900 shadow-sm min-h-[60vh]">
      <component
        :is="CurrentPanel"
        :overview="overview"
        :accounts-data="accountsData"
        :budgets-data="budgetsData"
        :transactions-data="transactionsData"
        :privacy-data="privacyData"
        :settings-data="settingsData"
      />
    </section>
  </SplitNavigationShell>
</template>

