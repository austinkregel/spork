<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import BudgetModal from '@/Components/Spork/Finance/Budgets/BudgetModal.vue';
import TagPills from '@/Components/Spork/Molecules/Tags/TagPills.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  budgetsData: {
    type: Object,
    default: null,
  },
});

const pinnedOrder = ref([...(props.budgetsData?.preferences?.pinned_budgets ?? [])]);
const budgetModal = ref(null);

const isPinned = (budgetId) => pinnedOrder.value.includes(budgetId);

const persistPins = () => {
  router.put(route('finance.banking.preferences.pins'), {
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
  return props.budgetsData?.budgets ?? [];
});

const tags = computed(() => props.budgetsData?.tags ?? []);

const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
const percent = (value) => `${Number(value ?? 0).toFixed(1)}%`;

const openNewBudget = () => budgetModal.value?.open();
const openEditBudget = (budget) => budgetModal.value?.open(budget);

const deleteBudget = (budget) => {
  if (!budget?.id) {
    return;
  }

  if (!confirm(`Delete budget "${budget.name}"?`)) {
    return;
  }

  router.delete(route('finance.banking.budgets.destroy', budget.id), {
    preserveScroll: true,
  });
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-sm text-stone-600 dark:text-stone-300">
          Budgets roll up spend for any transactions that carry the budget’s tags.
        </p>
      </div>
      <button
        type="button"
        class="px-3 py-2 text-sm rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
        @click="openNewBudget"
      >
        New Budget
      </button>
    </div>

    <div class="grid grid-cols-1 gap-4">
      <div
        v-for="budget in budgets"
        :key="budget.id"
        class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 px-4 py-4 space-y-3 shadow-sm"
      >
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <Link
                :href="route('finance.banking.budgets.show', budget.id)"
                class="text-stone-900 dark:text-white font-semibold hover:underline"
              >
                {{ budget.name }}
              </Link>
              <p class="text-xs text-stone-500 dark:text-stone-400">
                {{ budget.frequency }}
                <span v-if="(budget.net_monthly_income ?? 0) > 0">
                  • expected {{ percent(budget.expected_percent_of_income) }}
                  • actual {{ percent(budget.actual_percent_of_income) }}
                </span>
              </p>
              <div v-if="(budget.tags ?? []).length" class="mt-2 flex flex-wrap gap-2 -mx-2">
                <TagPills :tags="budget.tags ?? []" :item-class="'mr-0'" />
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="text-right">
                <p class="text-xs uppercase text-stone-500">Current Spend</p>
                <p class="text-lg text-stone-900 dark:text-white font-semibold">{{ currency(budget.current.total_spend) }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs uppercase text-stone-500">Last Period</p>
                <p class="text-lg font-semibold text-stone-700 dark:text-stone-200">
                  {{ currency(budget.previous?.total_spend) }}
                </p>
              </div>
              <button
                type="button"
                class="text-xs px-3 py-1 rounded-lg border"
                :class="isPinned(budget.id) ? 'border-emerald-500 text-emerald-400' : 'border-stone-700 text-stone-400'"
                @click.stop="togglePin(budget.id)"
              >
                {{ isPinned(budget.id) ? 'Pinned' : 'Pin' }}
              </button>
              <button
                type="button"
                class="text-xs px-3 py-1 rounded-lg border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800"
                @click.stop="openEditBudget(budget)"
              >
                Edit
              </button>
              <button
                type="button"
                class="text-xs px-3 py-1 rounded-lg border border-red-200 dark:border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10"
                @click.stop="deleteBudget(budget)"
              >
                Delete
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

    <BudgetModal ref="budgetModal" :tags="tags" />
  </div>
</template>


