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

const pinnedOrder = ref([...(props.accountsData?.preferences?.pinned_accounts ?? [])]);
const linkAccountRef = ref(null);

const accounts = computed(() => {
  return props.accountsData?.accounts ?? [];
});

const isPinned = (accountId) => pinnedOrder.value.includes(accountId);

const persistPins = () => {
  router.put(route('finance.banking.preferences.pins'), {
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

const linkNewAccount = async () => {
  await linkAccountRef.value?.linkAccount?.();
};
</script>

<template>
  <div class="space-y-6">
    <!-- logic-only plaid linker; we render the button where we want it -->
    <LinkAccount ref="linkAccountRef" variant="hidden" :accounts="[]" />

    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          v-for="account in accounts"
          :key="`tile-${account.account_id}`"
          class="rounded-xl bg-stone-950/90 dark:bg-stone-950 text-white shadow-sm p-4"
        >
          <div class="text-xl font-semibold truncate">{{ account.name }}</div>
          <div class="mt-1">
            <span class="text-xl font-semibold">
              {{ Number(account.available ?? account.balance).toLocaleString('en-US', { style: 'currency', currency: 'USD' }) }}
            </span>
            <span class="text-sm text-stone-400">
              /
              {{ Number(account.balance ?? account.available ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' }) }}
            </span>
          </div>
          <div class="text-xs text-stone-400">from {{ account.credential?.name ?? '—' }}</div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          type="button"
          class="px-3 py-2 rounded-lg bg-indigo-500 dark:bg-indigo-600 text-white text-sm shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
          @click="linkNewAccount"
        >
          Link new account
        </button>
      </div>
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
            class="text-xs px-3 py-1 rounded-lg border bg-white dark:bg-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
            :class="isPinned(account.account_id) ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300'"
            @click="togglePin(account.account_id)"
          >
            {{ isPinned(account.account_id) ? 'Pinned' : 'Pin' }}
          </button>
        </div>
      </div>
      <p v-if="accounts.length === 0" class="text-sm text-stone-500 dark:text-stone-400">No accounts found.</p>
    </div>
  </div>
</template>


