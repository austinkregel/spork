<script setup>
import Manage from "@/Layouts/Manage.vue";
import { Link } from "@inertiajs/vue3";
import { TagIcon, ServerIcon, BoltIcon, WalletIcon } from "@heroicons/vue/24/outline";

const { title, tags } = defineProps({
  title: String,
  tags: Object,
});

const { data } = tags;

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
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation">
    <div class="space-y-6">
      <header class="flex flex-col gap-2">
        <p class="text-sm uppercase tracking-widest text-stone-500 dark:text-stone-400">Routing logic</p>
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">Automation tags</h1>
        <p class="text-stone-700 dark:text-stone-200 max-w-3xl">
          Tags control where crawls send data, which credentials they may use, and how often they run. Consolidating them here
          keeps automation behavior transparent and repeatable.
        </p>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <article
          v-for="tag in data"
          :key="tag.id"
          class="border border-stone-200 dark:border-stone-800 rounded-xl p-4 bg-white dark:bg-stone-900 shadow-sm flex flex-col gap-3"
        >
          <div class="flex items-start gap-3">
            <component :is="typeIcon(tag.type)" class="w-8 h-8 text-emerald-500" />
            <div class="flex flex-col">
              <Link :href="'/-/automation/tags/' + tag.id" class="text-lg font-semibold text-stone-900 dark:text-white">
                {{ tag.name?.en }}
              </Link>
              <p class="text-sm text-stone-600 dark:text-stone-300">{{ tag.type ?? 'automatic' }}</p>
            </div>
          </div>
          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-200">
            <span class="font-medium">Attached items</span>
            <span class="text-stone-900 dark:text-white">{{ tag.taggables_count }}</span>
          </div>
          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-200">
            <span class="font-medium">Transaction total</span>
            <span class="text-stone-900 dark:text-white">${{ (Math.round((tag.transactions_sum_amount ?? 0) * 100) / 100).toFixed(2) }}</span>
          </div>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="condition in tag.conditions"
              :key="condition.id"
              class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-200"
            >
              {{ condition.parameter }} {{ condition.comparator.toLowerCase() }} {{ condition.value }}
            </span>
            <span v-if="!tag.conditions?.length" class="text-xs italic text-stone-500 dark:text-stone-400">No conditions yet</span>
          </div>
        </article>
      </div>
    </div>
  </Manage>
</template>
