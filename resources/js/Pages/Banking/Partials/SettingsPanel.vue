<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  settingsData: {
    type: Object,
    default: null,
  },
});

const settings = reactive({
  default_tab: props.settingsData?.preferences?.settings?.default_tab ?? 'overview',
  show_graphs: props.settingsData?.preferences?.settings?.show_graphs ?? true,
});

const save = () => {
  router.put(route('banking.preferences.settings'), {
    settings,
  }, {
    preserveScroll: true,
  });
};
</script>

<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 space-y-6 shadow-sm">
      <div class="space-y-2">
        <label class="text-sm text-stone-700 dark:text-stone-300 font-medium">Default Tab</label>
        <select
          v-model="settings.default_tab"
          class="w-full rounded-xl bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
        >
          <option value="overview">Overview</option>
          <option value="accounts">Accounts</option>
          <option value="budgets">Budgets</option>
          <option value="transactions">Transactions</option>
          <option value="settings">Settings</option>
        </select>
      </div>

      <div class="flex items-center justify-between border border-stone-200 dark:border-stone-800 rounded-xl px-4 py-3 bg-white dark:bg-stone-900/60">
        <div>
          <p class="text-stone-900 dark:text-white font-medium text-sm">Show graphs on overview</p>
          <p class="text-xs text-stone-500 dark:text-stone-400">Disable to hide spend trend charts.</p>
        </div>
        <label class="inline-flex items-center cursor-pointer">
          <input
            v-model="settings.show_graphs"
            type="checkbox"
            class="sr-only peer"
          >
          <div class="w-10 h-5 bg-stone-700 rounded-full peer peer-checked:bg-emerald-500 relative transition-colors">
            <div
              class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform duration-200 transform peer-checked:translate-x-5"
            />
          </div>
        </label>
      </div>

      <button
        type="button"
        class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
        @click="save"
      >
        Save Preferences
      </button>
    </div>
  </div>
</template>


