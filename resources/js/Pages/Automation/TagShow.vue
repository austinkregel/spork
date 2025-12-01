<script setup>
import Manage from "@/Layouts/Manage.vue";
import { TagIcon, ServerIcon, BoltIcon, WalletIcon } from "@heroicons/vue/24/outline";
import ConditionsEditor from "@/Components/Spork/Molecules/ConditionsEditor.vue";
import SporkTable from "@/Components/Spork/Atoms/SporkTable.vue";
import dayjs from "dayjs";

const { title, tag, type } = defineProps({
  title: String,
  tag: Object,
  type: String,
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

const date = (d) => dayjs(d).format('YYYY-MM-DD');
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation">
    <div class="space-y-6">
      <header class="flex flex-col gap-3">
        <div class="flex items-start gap-3">
          <component :is="typeIcon(tag.type)" class="w-9 h-9 text-emerald-500" />
          <div>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">{{ tag.name?.en }}</h1>
            <p class="text-sm text-stone-600 dark:text-stone-300">Type: {{ tag.type ?? 'automatic' }}</p>
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
          <ConditionsEditor :conditions="tag.conditions" :type="type" :id="tag?.id" />
        </div>
        <div class="border border-stone-200 dark:border-stone-800 rounded-xl p-4 bg-white dark:bg-stone-900 flex flex-col gap-3">
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
            { name: 'Limit', accessor: (item) => item.limit },
          ]"
          header="Budgets"
          description="Budget rules linked to this tag"
        />
      </section>
    </div>
  </Manage>
</template>
