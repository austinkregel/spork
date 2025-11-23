<script setup>
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc.js';
import AppLayout from "@/Layouts/AppLayout.vue";

dayjs.extend(utc);

const props = defineProps({
  title: {
    type: String,
    default: 'Budget Management',
  },
  budget: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
});

const formatCurrency = (value) => {
  if (value === null || value === undefined) {
    return '';
  }

  return Number(value).toLocaleString('en-US', {
    style: 'currency',
    currency: 'USD',
  });
};

const formatDate = (value) => {
  if (!value) {
    return '';
  }

  return dayjs.utc(value).format('YYYY-MM-DD');
};

const usagePercentage = () => {
  if (!props.stats) {
    return 0;
  }

  const value = Number(props.stats.usage_percentage ?? 0);

  if (Number.isNaN(value)) {
    return 0;
  }

  return Math.max(0, value);
};
</script>

<template>
  <AppLayout :title="title">
    <div class="px-4 py-6 space-y-6">
      <div>
        <h1 class="text-2xl font-semibold">
          {{ budget.name }}
        </h1>
        <p class="text-sm text-stone-400">
          Budget for tagged transactions, evaluated in UTC.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="border border-stone-700 rounded-lg p-4 space-y-2">
          <div class="text-xs uppercase text-stone-400">
            Amount
          </div>
          <div class="text-xl">
            {{ formatCurrency(budget.amount) }}
          </div>
          <div class="text-xs text-stone-500">
            Frequency: {{ budget.frequency }}
          </div>
        </div>

        <div class="border border-stone-700 rounded-lg p-4 space-y-2">
          <div class="text-xs uppercase text-stone-400">
            Period (UTC)
          </div>
          <div class="text-sm">
            {{ formatDate(stats.period_start) }} – {{ formatDate(stats.period_end) }}
          </div>
        </div>

        <div class="border border-stone-700 rounded-lg p-4 space-y-3">
          <div class="flex justify-between text-xs uppercase text-stone-400">
            <span>Usage</span>
            <span>{{ usagePercentage().toFixed(1) }}%</span>
          </div>
          <div class="h-2 w-full rounded-full bg-stone-800 overflow-hidden">
            <div
              class="h-2 rounded-full"
              :class="usagePercentage() >= 100 ? 'bg-red-500' : 'bg-emerald-500'"
              :style="{ width: Math.min(usagePercentage(), 150) + '%' }"
            />
          </div>
          <div class="flex justify-between text-xs text-stone-400">
            <span>Spent: {{ formatCurrency(stats.total_spend) }}</span>
            <span>Remaining: {{ formatCurrency(stats.remaining) }}</span>
          </div>
        </div>
      </div>

      <div class="border border-stone-700 rounded-lg p-4">
        <div class="text-sm text-stone-400">
          Tags attached to this budget:
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
          <span
            v-for="tag in budget.tags"
            :key="tag.id"
            class="px-2 py-1 text-xs rounded-full border border-stone-600"
          >
            {{ tag.name?.en ?? tag.name }}
          </span>
        </div>
      </div>
    </div>
  </AppLayout>
</template>




