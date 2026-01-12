<script setup>
import SplitNavigationShell from '@/Layouts/SplitNavigationShell.vue';
import TransactionsTable from '@/Components/Spork/Finance/Transactions/TransactionsTable.vue';
import TagPills from '@/Components/Spork/Molecules/Tags/TagPills.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Budget',
  },
  navigation: {
    type: Array,
    default: () => [],
  },
  budget: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
  transactions: {
    type: Array,
    default: () => [],
  },
  past_periods: {
    type: Array,
    default: () => [],
  },
  tags: {
    type: Array,
    default: () => [],
  },
});

const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
const usage = () => Number(props.stats?.usage_percentage ?? 0);
const percent = (value) => `${Number(value ?? 0).toFixed(1)}%`;
const periodLabel = (period) => {
  const start = period?.period_start ?? null;
  if (!start) return 'Past period';
  return window.dayjs(start).utc().format('MMMM YYYY');
};
</script>

<template>
  <SplitNavigationShell
    :title="title"
    subtitle="Finance cockpit"
    :nav-items="navigation.map((item) => ({ ...item, name: item.label, active: item.tab === 'budgets' }))"
    content-width-class="max-w-none"
    content-padding-class="px-4 lg:px-6 pt-2 pb-4"
  >
    <template #header>
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Banking
          </p>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">
            {{ budget.name }}
          </h1>
          <p class="text-sm text-stone-600 dark:text-stone-300">
            Spend is calculated from tagged Plaid and Privacy transactions within the current budget period (UTC).
          </p>
        </div>
      </div>
    </template>

    <section class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm space-y-2">
          <div class="text-xs uppercase text-stone-500">Limit</div>
          <div class="text-2xl font-semibold text-stone-900 dark:text-white">{{ currency(budget.amount) }}</div>
          <div class="text-xs text-stone-500 dark:text-stone-400">
            {{ budget.frequency }} • interval {{ budget.interval }}
            <span v-if="(budget.net_monthly_income ?? 0) > 0">
              • expected {{ percent(budget.expected_percent_of_income) }}
              • actual {{ percent(budget.actual_percent_of_income) }}
            </span>
          </div>
        </div>

        <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm space-y-2">
          <div class="text-xs uppercase text-stone-500">Current Spend</div>
          <div class="text-2xl font-semibold text-stone-900 dark:text-white">{{ currency(stats.total_spend) }}</div>
          <div class="text-xs text-stone-500 dark:text-stone-400">
            Remaining {{ currency(stats.remaining) }}
          </div>
        </div>

        <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm space-y-3">
          <div class="flex justify-between text-xs uppercase text-stone-500">
            <span>Usage</span>
            <span>{{ usage().toFixed(1) }}%</span>
          </div>
          <div class="h-2 rounded-full bg-stone-100 dark:bg-stone-800 overflow-hidden">
            <div
              class="h-2 rounded-full"
              :class="usage() >= 100 ? 'bg-rose-500' : 'bg-emerald-500'"
              :style="{ width: Math.min(usage(), 150) + '%' }"
            />
          </div>
          <div v-if="(budget.tags ?? []).length" class="pt-1">
            <TagPills :tags="budget.tags ?? []" :item-class="'mr-0'" />
          </div>
        </div>
      </div>

      <TransactionsTable :transactions="transactions" :tags="tags" />

      <div v-if="(past_periods ?? []).length" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-stone-900 dark:text-white">Past periods</h2>
          <div class="text-xs text-stone-500 dark:text-stone-400">
            Showing the last {{ past_periods.length }} periods
          </div>
        </div>

        <div
          v-for="period in past_periods"
          :key="`${period.period_start}-${period.period_end}`"
          class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4 space-y-3"
        >
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
              <div class="text-sm font-semibold text-stone-900 dark:text-white">
                {{ periodLabel(period) }}
              </div>
              <div class="text-xs text-stone-500 dark:text-stone-400">
                {{ period.period_start }} → {{ period.period_end }} (UTC)
              </div>
            </div>
            <div class="text-right">
              <div class="text-xs uppercase text-stone-500">Spend</div>
              <div class="text-sm font-semibold text-stone-900 dark:text-white">
                {{ currency(period?.stats?.total_spend) }}
              </div>
            </div>
          </div>

          <TransactionsTable :transactions="period.transactions ?? []" :tags="tags" />
        </div>
      </div>
    </section>
  </SplitNavigationShell>
</template>


