<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import OverviewPanel from '@/Pages/Banking/Partials/OverviewPanel.vue';
import AccountsPanel from '@/Pages/Banking/Partials/AccountsPanel.vue';
import BudgetsPanel from '@/Pages/Banking/Partials/BudgetsPanel.vue';
import TransactionsPanel from '@/Pages/Banking/Partials/TransactionsPanel.vue';
import PrivacyPanel from '@/Pages/Banking/Partials/PrivacyPanel.vue';
import SettingsPanel from '@/Pages/Banking/Partials/SettingsPanel.vue';

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

const page = usePage();

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
    label: item.label,
    active: item.active ?? item.tab === props.tab ?? page.url.startsWith(item.href ?? ''),
  })),
);

const activeNav = computed(() => navigationItems.value.find((item) => item.active));
</script>

<template>
  <AppLayout title="Banking">
    <div class="flex h-[calc(100vh-4rem)] divide-x divide-stone-200/70 dark:divide-stone-700/70">
      <aside class="hidden lg:flex w-64 xl:w-72 shrink-0 flex-col gap-3 p-4 backdrop-blur-glass bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] overflow-y-auto custom-scroll">
        <div class="space-y-1 px-1">
          <p class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">Finance cockpit</p>
          <h2 class="text-xl font-semibold text-stone-900 dark:text-stone-50">Banking</h2>
        </div>

        <nav class="mt-2 flex flex-col gap-1" aria-label="Banking navigation">
          <Link
            v-for="item in navigationItems"
            :key="item.href ?? item.label"
            :href="item.href ?? '#'"
            :aria-current="item.active ? 'page' : undefined"
            class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
            :class="item.active
              ? 'bg-stone-200/70 text-stone-900 dark:bg-stone-700/70 dark:text-stone-50 font-semibold'
              : 'text-stone-700 hover:bg-stone-200/60 dark:text-stone-200 dark:hover:bg-stone-700/60'"
          >
            <span class="flex items-center gap-2">
              <DynamicIcon
                v-if="item.icon"
                :icon-name="item.icon"
                class="h-4 w-4 shrink-0"
                :class="item.active ? 'text-indigo-500 dark:text-indigo-300' : 'text-stone-500 dark:text-stone-400'"
                :active="Boolean(item.active)"
                aria-hidden="true"
              />
              <span>{{ item.label }}</span>
            </span>
            <span
              v-if="item.badge !== undefined"
              class="rounded-full border border-stone-300/70 px-2 py-0.5 text-xs font-semibold text-stone-500 dark:border-stone-600/70 dark:text-stone-300"
            >
              {{ item.badge }}
            </span>
          </Link>
        </nav>
      </aside>

      <section class="flex-1 min-w-0 flex flex-col overflow-hidden">
        <header class="border-b border-stone-200/70 dark:border-stone-700/70 px-4 lg:px-6 py-4 backdrop-blur-glass bg-[var(--color-glass-surface-light)]/60 dark:bg-[var(--color-glass-surface-dark)]/60">
          <p class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">Banking</p>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
            {{ activeNav?.label ?? 'Overview' }}
          </h1>
        </header>

        <main class="flex-1 overflow-y-auto custom-scroll px-4 lg:px-6 pt-4 pb-6">
          <GlassSurface class="min-h-[60vh] p-4 lg:p-6">
            <component
              :is="CurrentPanel"
              :overview="overview"
              :accounts-data="accountsData"
              :budgets-data="budgetsData"
              :transactions-data="transactionsData"
              :privacy-data="privacyData"
              :settings-data="settingsData"
            />
          </GlassSurface>
        </main>
      </section>
    </div>
  </AppLayout>
</template>
