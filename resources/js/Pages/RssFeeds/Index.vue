<template>
  <AppLayout title="RSS Feeds">
    <div class="p-4">
      <div class="flex flex-col gap-4 lg:flex-row">
        <!-- Left rail -->
        <div class="w-full lg:w-80 shrink-0">
          <SocialFeedSelector :social-feeds="social_feeds" :active-id="null" />
        </div>

        <!-- Main -->
        <div class="flex-1">
          <div class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-stone-200 dark:border-stone-800">
              <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Feed</div>
                <div class="text-lg font-semibold text-stone-900 dark:text-white">Latest articles</div>
              </div>

              <div class="flex items-center gap-2">
                <Link
                  href="/-/manage/external-rss-feeds"
                  class="px-3 py-2 text-sm rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800"
                >
                  Manage feeds
                </Link>
                <button
                  type="button"
                  class="px-3 py-2 text-sm rounded-lg bg-indigo-500 dark:bg-indigo-600 text-white hover:bg-indigo-600 dark:hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-stone-900"
                  @click="createOpen = true"
                >
                  Create social feed
                </button>
              </div>
            </div>

            <div class="divide-y divide-stone-200 dark:divide-stone-800">
              <ArticleCard v-for="topic in feeds ?? []" :key="topic.id" :article="topic" />
            </div>

            <div class="flex items-center justify-between gap-4 px-4 py-3">
              <Link
                :href="pagination?.prev_page_url ?? '#'"
                :disabled="!pagination?.prev_page_url"
                :class="[!pagination?.prev_page_url ? 'opacity-50 cursor-not-allowed' : '']"
                class="px-3 py-2 text-sm rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800"
              >
                Previous
              </Link>

              <div class="text-xs text-stone-500 dark:text-stone-400">
                Page {{ pagination?.current_page ?? 1 }} of {{ pagination?.last_page ?? 1 }}
              </div>

              <Link
                :href="pagination?.next_page_url ?? '#'"
                :disabled="!pagination?.next_page_url"
                :class="[!pagination?.next_page_url ? 'opacity-50 cursor-not-allowed' : '']"
                class="px-3 py-2 text-sm rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800"
              >
                Next
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <SocialFeedModal
      :show="createOpen"
      :available-tags="available_tags"
      :parameter-groups="parameter_groups"
      @close="createOpen = false"
      @created="onCreated"
    />
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ArticleCard from '@/Components/RssFeeds/ArticleCard.vue';
import SocialFeedModal from '@/Components/RssFeeds/SocialFeedModal.vue';
import SocialFeedSelector from '@/Components/RssFeeds/SocialFeedSelector.vue';

const createOpen = ref(false);

const { feeds, pagination, social_feeds, available_tags, parameter_groups } = defineProps({
  feeds: Array,
  pagination: Object,
  social_feeds: Array,
  available_tags: Array,
  parameter_groups: Array,
});

const onCreated = () => {
  createOpen.value = false;
  router.reload({ only: ['social_feeds'] });
};
</script>
