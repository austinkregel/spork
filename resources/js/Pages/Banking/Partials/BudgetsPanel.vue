<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  budgetsData: {
    type: Object,
    default: null,
  },
});

const search = ref('');
const pinnedOrder = ref([...(props.budgetsData?.preferences?.pinned_budgets ?? [])]);

const isPinned = (budgetId) => pinnedOrder.value.includes(budgetId);

const persistPins = () => {
  router.put(route('banking.preferences.pins'), {
    type: 'budgets',
    order: pinnedOrder.value,
  }, {
    preserveScroll: true,
  });
};

const togglePin = (budgetId) => {
  if (isPinned(budgetId)) {
    pinnedOrder.value = pinnedOrder.value.filter((id) => id !== budgetId);
  } else {
    pinnedOrder.value = [...pinnedOrder.value, budgetId];
  }

  persistPins();
};

const budgets = computed(() => {
  const list = props.budgetsData?.budgets ?? [];

  if (!search.value) {
    return list;
  }

  return list.filter((budget) => budget.name.toLowerCase().includes(search.value.toLowerCase()));
});

const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl text-stone-900 dark:text-white font-semibold">Budgets</h1>
        <p class="text-sm text-stone-500 dark:text-stone-400">Monitor spend, month-over-month deltas, and pin budgets.</p>
      </div>
      <input
        v-model="search"
        type="text"
        placeholder="Search budgets"
        class="rounded-xl bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
      />
    </div>

    <div class="space-y-4">
      <div
        v-for="budget in budgets"
        :key="budget.id"
        class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 px-4 py-4 space-y-3 shadow-sm"
      >
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <p class="text-stone-900 dark:text-white font-semibold">{{ budget.name }}</p>
            <p class="text-xs text-stone-500 dark:text-stone-400">{{ budget.frequency }}</p>
          </div>
          <div class="flex items-center gap-4">
            <div class="text-right">
              <p class="text-xs uppercase text-stone-500">Current Spend</p>
              <p class="text-lg text-stone-900 dark:text-white font-semibold">{{ currency(budget.current.total_spend) }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs uppercase text-stone-500">MoM Change</p>
              <p
                class="text-lg font-semibold"
                :class="budget.delta >= 0 ? 'text-rose-400' : 'text-emerald-400'"
              >
                {{ currency(budget.delta) }}
              </p>
            </div>
            <button
              type="button"
              class="text-xs px-3 py-1 rounded-lg border"
              :class="isPinned(budget.id) ? 'border-emerald-500 text-emerald-400' : 'border-stone-700 text-stone-400'"
              @click="togglePin(budget.id)"
            >
              {{ isPinned(budget.id) ? 'Pinned' : 'Pin' }}
            </button>
          </div>
        </div>
        <div class="h-2 rounded-full bg-stone-100 dark:bg-stone-800 overflow-hidden">
          <div
            class="h-2 rounded-full"
            :class="budget.current.usage_percentage >= 100 ? 'bg-rose-500' : 'bg-emerald-500'"
            :style="{ width: Math.min(budget.current.usage_percentage ?? 0, 150) + '%' }"
          />
        </div>
        <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400">
          <span>Spent {{ currency(budget.current.total_spend) }}</span>
          <span>Remaining {{ currency(budget.current.remaining) }}</span>
        </div>
      </div>
      <p v-if="budgets.length === 0" class="text-sm text-stone-500 dark:text-stone-400">No budgets found.</p>
    </div>
  </div>
</template>


