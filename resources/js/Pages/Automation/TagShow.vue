<script setup>
import Manage from "@/Layouts/Manage.vue";
import { TagIcon, ServerIcon, BoltIcon, WalletIcon } from "@heroicons/vue/24/outline";
import ConditionsEditor from "@/Components/Spork/Molecules/ConditionsEditor.vue";
import SporkTable from "@/Components/Spork/Atoms/SporkTable.vue";
import dayjs from "dayjs";
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const { title, tag, type, condition_parameter_groups } = defineProps({
  title: String,
  tag: Object,
  type: String,
  condition_parameter_groups: {
    type: Array,
    default: () => [],
  },
});

const typeIcon = (type) => {
  switch (type) {
    case 'finance':
      return WalletIcon;
    case 'server':
      return ServerIcon;
    case 'automatic':
      return BoltIcon;
    default:
      return TagIcon;
  }
};

const typeLabel = (type) => {
  if (!type) {
    return 'general';
  }

  return type;
};

const date = (d) => dayjs(d).format('YYYY-MM-DD');
const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });

const form = useForm({
  name: tag?.name?.en ?? '',
  type: tag?.type ?? '',
  must_all_conditions_pass: !!tag?.must_all_conditions_pass,
});

watch(
  () => form.type,
  (type) => {
    if (type !== 'automatic') {
      form.must_all_conditions_pass = false;
    }
  }
);

const typeRequiresMatchMode = computed(() => form.type === 'automatic');

const saveTag = () => {
  form.patch(route('automation.tags.update', tag?.id), {
    preserveScroll: true,
  });
};
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation">
    <div class="space-y-6">
      <header class="flex flex-col gap-3">
        <div class="flex items-start gap-3">
          <component :is="typeIcon(tag.type)" class="w-9 h-9 text-emerald-500" />
          <div>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">{{ tag.name?.en }}</h1>
            <p class="text-sm text-stone-600 dark:text-stone-300">Type: {{ typeLabel(tag.type) }}</p>
            <p class="text-sm text-stone-600 dark:text-stone-300">Attached items: {{ tag.taggables_count ?? 0 }}</p>
          </div>
        </div>
        <p class="text-stone-700 dark:text-stone-200 max-w-3xl">
          Tune how this tag routes automation output or throttles access. Conditions describe what should be tagged; downstream
          playbooks can use the same tag to pick credentials, pacing, and notification rules.
        </p>
      </header>

      <section class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 border border-stone-200 dark:border-stone-800 rounded-xl overflow-hidden bg-white dark:bg-stone-900">
          <ConditionsEditor :conditions="tag.conditions" :type="type" :id="tag?.id" :parameter-groups="condition_parameter_groups" />
        </div>
        <div class="border border-stone-200 dark:border-stone-800 rounded-xl p-4 bg-white dark:bg-stone-900 flex flex-col gap-4">
          <div>
            <div class="text-sm font-semibold text-stone-900 dark:text-white">Tag settings</div>
            <div class="text-xs text-stone-500 dark:text-stone-400">Name, type, and match behavior for conditional tagging.</div>
          </div>

          <form class="space-y-3" @submit.prevent="saveTag">
            <div class="space-y-1">
              <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Name</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
              />
              <div v-if="form.errors.name" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.name }}</div>
            </div>

            <div class="space-y-1">
              <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Type</label>
              <select
                v-model="form.type"
                class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
              >
                <option value="automatic">automatic</option>
                <option value="finance">finance</option>
                <option value="server">server</option>
                <option value="">general</option>
              </select>
              <div v-if="form.errors.type" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.type }}</div>
            </div>

            <div class="space-y-1" :class="typeRequiresMatchMode ? '' : 'opacity-60 pointer-events-none'">
              <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Match mode</label>
              <div class="grid grid-cols-1 gap-2">
                <label class="flex items-start gap-2 rounded-lg border border-stone-200 dark:border-stone-800 p-3 cursor-pointer bg-white dark:bg-stone-900">
                  <input
                    v-model="form.must_all_conditions_pass"
                    :value="false"
                    type="radio"
                    class="mt-1"
                  />
                  <div>
                    <div class="text-sm font-medium text-stone-900 dark:text-white">Any condition</div>
                    <div class="text-xs text-stone-600 dark:text-stone-300">Apply when at least one condition matches.</div>
                  </div>
                </label>
                <label class="flex items-start gap-2 rounded-lg border border-stone-200 dark:border-stone-800 p-3 cursor-pointer bg-white dark:bg-stone-900">
                  <input
                    v-model="form.must_all_conditions_pass"
                    :value="true"
                    type="radio"
                    class="mt-1"
                  />
                  <div>
                    <div class="text-sm font-medium text-stone-900 dark:text-white">All conditions</div>
                    <div class="text-xs text-stone-600 dark:text-stone-300">Apply only when every condition matches.</div>
                  </div>
                </label>
              </div>
              <div v-if="form.errors.must_all_conditions_pass" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.must_all_conditions_pass }}</div>
            </div>

            <div class="flex items-center justify-end">
              <button
                type="submit"
                class="px-3 py-2 text-sm rounded-lg bg-indigo-500 dark:bg-indigo-600 text-white shadow-sm disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
                :disabled="form.processing"
              >
                Save
              </button>
            </div>
          </form>

          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-300">
            <span class="font-semibold text-stone-900 dark:text-white">Transaction total</span>
            <span class="text-stone-900 dark:text-white">${{ (Math.round((tag.transactions_sum_amount ?? 0) * 100) / 100).toFixed(2) }}</span>
          </div>
          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-300">
            <span class="font-semibold text-stone-900 dark:text-white">Linked feeds</span>
            <span class="text-stone-900 dark:text-white">{{ tag.feeds?.length ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-300">
            <span class="font-semibold text-stone-900 dark:text-white">Servers</span>
            <span class="text-stone-900 dark:text-white">{{ tag.servers?.length ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-300">
            <span class="font-semibold text-stone-900 dark:text-white">Projects</span>
            <span class="text-stone-900 dark:text-white">{{ tag.projects?.length ?? 0 }}</span>
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <SporkTable
          class="-m-2"
          :items="tag.transactions"
          :headers="[
            { name: 'Name', accessor: (item) => item.name },
            { name: 'Amount', accessor: (item) => item.amount },
            { name: 'Pending', accessor: (item) => item.pending ? 'pending' : 'posted' },
            { name: 'Date', accessor: (item) => date(item.date) },
            { name: 'Category', accessor: (item) => item.personal_finance_category },
          ]"
          header="Transactions"
          description="Recent transactions carrying this tag"
        />

        <SporkTable
          class="-m-2"
          :items="tag.articles"
          :headers="[
            { name: 'Headline', accessor: (item) => item.headline },
            { name: 'Updated', accessor: (item) => date(item.last_modified) },
          ]"
          header="Articles"
          description="Recent articles matched by this tag"
        />
      </section>

      <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <SporkTable
          class="-m-2"
          :items="tag.servers"
          :headers="[
            { name: 'Name', accessor: (item) => item.name },
          ]"
          header="Servers"
          description="Servers carrying this tag"
        />

        <SporkTable
          class="-m-2"
          :items="tag.budgets"
          :headers="[
            { name: 'Budget', accessor: (item) => item.name },
            { name: 'Limit', accessor: (item) => currency(item.amount) },
          ]"
          header="Budgets"
          description="Budget rules linked to this tag"
        />
      </section>
    </div>
  </Manage>
</template>
