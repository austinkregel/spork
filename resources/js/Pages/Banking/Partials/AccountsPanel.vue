<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import LinkAccount from "@/Components/Spork/Finance/LinkAccount.vue";

const props = defineProps({
  accountsData: {
    type: Object,
    default: null,
  },
});

const search = ref('');
const pinnedOrder = ref([...(props.accountsData?.preferences?.pinned_accounts ?? [])]);

const accounts = computed(() => {
  const list = props.accountsData?.accounts ?? [];

  if (!search.value) {
    return list;
  }

  return list.filter((account) => account.name?.toLowerCase().includes(search.value.toLowerCase()));
});

const isPinned = (accountId) => pinnedOrder.value.includes(accountId);

const persistPins = () => {
  router.put(route('banking.preferences.pins'), {
    type: 'accounts',
    order: pinnedOrder.value,
  }, {
    preserveScroll: true,
  });
};

const togglePin = (accountId) => {
  if (isPinned(accountId)) {
    pinnedOrder.value = pinnedOrder.value.filter((id) => id !== accountId);
  } else {
    pinnedOrder.value = [...pinnedOrder.value, accountId];
  }

  persistPins();
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl text-stone-900 dark:text-white font-semibold">Accounts</h1>
        <p class="text-sm text-stone-500 dark:text-stone-400">View balances, pin favorites, and manage connections.</p>
      </div>
      <div class="flex items-center gap-3">
        <input
          v-model="search"
          type="text"
          placeholder="Search accounts"
          class="rounded-xl bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
        />
      </div>
    </div>

    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4 shadow-sm">
      <LinkAccount :accounts="accountsData?.accounts ?? []" />
    </div>

    <div class="space-y-3">
      <div
        v-for="account in accounts"
        :key="account.account_id"
        class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3 shadow-sm"
      >
        <div>
          <p class="text-stone-900 dark:text-white font-medium">{{ account.name }}</p>
          <p class="text-xs text-stone-500 dark:text-stone-400">{{ account.type }} ••••{{ account.mask }}</p>
        </div>
        <div class="flex items-center gap-6">
          <div class="text-right">
            <p class="text-sm text-stone-500 dark:text-stone-400">Available</p>
            <p class="text-lg text-emerald-600 dark:text-emerald-400 font-semibold">
              {{ Number(account.available ?? account.balance).toLocaleString('en-US', { style: 'currency', currency: 'USD' }) }}
            </p>
          </div>
          <button
            type="button"
            class="text-xs px-3 py-1 rounded-lg border bg-white dark:bg-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
            :class="isPinned(account.account_id) ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300'"
            @click="togglePin(account.account_id)"
          >
            {{ isPinned(account.account_id) ? 'Pinned' : 'Pin' }}
          </button>
        </div>
      </div>
      <p v-if="accounts.length === 0" class="text-sm text-stone-500 dark:text-stone-400">No accounts match your search.</p>
    </div>
  </div>
</template>


