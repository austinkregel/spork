<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
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
const usage = computed(() => Number(props.stats?.usage_percentage ?? 0));
const percent = (value) => `${Number(value ?? 0).toFixed(1)}%`;
const periodLabel = (period) => {
  const start = period?.period_start ?? null;
  if (!start) return 'Past period';
  return window.dayjs(start).utc().format('MMMM YYYY');
};

const navigationItems = computed(() =>
  props.navigation.map((item) => ({
    ...item,
    active: item.tab === 'budgets',
  })),
);
</script>

<template>
  <AppLayout :title="title">
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
          </Link>
        </nav>
      </aside>

      <section class="flex-1 min-w-0 flex flex-col overflow-hidden">
        <header class="border-b border-stone-200/70 dark:border-stone-700/70 px-4 lg:px-6 py-4 backdrop-blur-glass bg-[var(--color-glass-surface-light)]/60 dark:bg-[var(--color-glass-surface-dark)]/60">
          <p class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">Banking</p>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
            {{ budget.name }}
          </h1>
          <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
            Spend is calculated from tagged Plaid and Privacy transactions within the current budget period (UTC).
          </p>
        </header>

        <main class="flex-1 overflow-y-auto custom-scroll px-4 lg:px-6 pt-4 pb-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <GlassSurface class="p-4 space-y-2">
              <div class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Limit</div>
              <div class="text-2xl font-semibold text-stone-900 dark:text-stone-50">{{ currency(budget.amount) }}</div>
              <div class="text-xs text-stone-500 dark:text-stone-400">
                {{ budget.frequency }} • interval {{ budget.interval }}
                <span v-if="(budget.net_monthly_income ?? 0) > 0">
                  • expected {{ percent(budget.expected_percent_of_income) }}
                  • actual {{ percent(budget.actual_percent_of_income) }}
                </span>
              </div>
            </GlassSurface>

            <GlassSurface class="p-4 space-y-2">
              <div class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Current Spend</div>
              <div class="text-2xl font-semibold text-stone-900 dark:text-stone-50">{{ currency(stats.total_spend) }}</div>
              <div class="text-xs text-stone-500 dark:text-stone-400">
                Remaining {{ currency(stats.remaining) }}
              </div>
            </GlassSurface>

            <GlassSurface class="p-4 space-y-3">
              <div class="flex justify-between text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                <span>Usage</span>
                <span>{{ usage.toFixed(1) }}%</span>
              </div>
              <div class="h-2 rounded-full bg-stone-200/70 dark:bg-stone-700/60 overflow-hidden">
                <div
                  class="h-2 rounded-full"
                  :class="usage >= 100 ? 'bg-red-500' : 'bg-emerald-500'"
                  :style="{ width: Math.min(usage, 150) + '%' }"
                />
              </div>
              <div v-if="(budget.tags ?? []).length" class="pt-1">
                <TagPills :tags="budget.tags ?? []" :item-class="'mr-0'" />
              </div>
            </GlassSurface>
          </div>

          <TransactionsTable :transactions="transactions" :tags="tags" />

          <div v-if="(past_periods ?? []).length" class="space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold text-stone-900 dark:text-stone-50">Past periods</h2>
              <div class="text-xs text-stone-500 dark:text-stone-400">
                Showing the last {{ past_periods.length }} periods
              </div>
            </div>

            <GlassCard
              v-for="period in past_periods"
              :key="`${period.period_start}-${period.period_end}`"
              class="space-y-3"
            >
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                  <div class="text-sm font-semibold text-stone-900 dark:text-stone-50">
                    {{ periodLabel(period) }}
                  </div>
                  <div class="text-xs text-stone-500 dark:text-stone-400">
                    {{ period.period_start }} → {{ period.period_end }} (UTC)
                  </div>
                </div>
                <div class="text-right">
                  <GlassPill tone="info" size="sm">Spend {{ currency(period?.stats?.total_spend) }}</GlassPill>
                </div>
              </div>

              <TransactionsTable :transactions="period.transactions ?? []" :tags="tags" />
            </GlassCard>
          </div>
        </main>
      </section>
    </div>
  </AppLayout>
</template>
