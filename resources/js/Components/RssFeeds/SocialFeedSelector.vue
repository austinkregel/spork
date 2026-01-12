<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  socialFeeds: {
    type: Array,
    default: () => [],
  },
  activeId: {
    type: Number,
    default: null,
  },
  manageHref: {
    type: String,
    default: '/-/manage/social-feeds',
  },
});
</script>

<template>
  <div class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm">
    <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200 dark:border-stone-800">
      <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Social Feeds</div>
        <div class="text-sm text-stone-700 dark:text-stone-200">Your feeds + public feeds</div>
      </div>
      <Link
        :href="manageHref"
        class="text-xs font-semibold text-indigo-600 dark:text-indigo-300 hover:underline"
      >
        Manage
      </Link>
    </div>

    <div class="p-2 space-y-1">
      <Link
        href="/-/rss-feeds"
        class="block rounded-lg px-3 py-2 text-sm hover:bg-stone-100 dark:hover:bg-stone-800"
        :class="activeId === null ? 'bg-stone-100 dark:bg-stone-800 text-stone-900 dark:text-white' : 'text-stone-700 dark:text-stone-200'"
      >
        All articles
      </Link>

      <Link
        v-for="feed in socialFeeds ?? []"
        :key="feed.id"
        :href="`/-/rss-feeds/${feed.id}`"
        class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm hover:bg-stone-100 dark:hover:bg-stone-800"
        :class="feed.id === activeId ? 'bg-stone-100 dark:bg-stone-800 text-stone-900 dark:text-white' : 'text-stone-700 dark:text-stone-200'"
      >
        <div class="min-w-0">
          <div class="truncate font-medium">{{ feed.name }}</div>
          <div v-if="feed.description" class="truncate text-xs text-stone-500 dark:text-stone-400">
            {{ feed.description }}
          </div>
        </div>
        <div
          v-if="feed.is_public"
          class="shrink-0 rounded-full bg-emerald-100 dark:bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300"
        >
          Public
        </div>
      </Link>
    </div>
  </div>
</template>

