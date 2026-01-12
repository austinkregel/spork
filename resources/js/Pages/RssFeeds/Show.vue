<template>
  <AppLayout title="Social Feed">
    <div class="p-4">
      <div class="flex flex-col gap-4 lg:flex-row">
        <!-- Left rail -->
        <div class="w-full lg:w-80 shrink-0">
          <SocialFeedSelector :social-feeds="social_feeds" :active-id="social_feed?.id ?? null" />
        </div>

        <!-- Main -->
        <div class="flex-1">
          <div class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm">
            <div class="px-4 py-3 border-b border-stone-200 dark:border-stone-800">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <div class="text-lg font-semibold text-stone-900 dark:text-white truncate">
                      {{ social_feed?.name }}
                    </div>
                    <div
                      v-if="social_feed?.is_public"
                      class="rounded-full bg-emerald-100 dark:bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300"
                    >
                      Public
                    </div>
                    <div
                      v-else
                      class="rounded-full bg-stone-100 dark:bg-stone-800 px-2 py-0.5 text-[11px] font-semibold text-stone-700 dark:text-stone-200"
                    >
                      Private
                    </div>
                  </div>
                  <div v-if="social_feed?.description" class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                    {{ social_feed.description }}
                  </div>
                  <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span
                      v-for="tag in social_feed?.tags ?? []"
                      :key="tag.id ?? tag.name?.en ?? tag.name"
                      class="py-1 px-2 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200"
                    >
                      {{ tag?.name?.en ?? tag?.name }}
                    </span>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <Link
                    href="/-/manage/social-feeds"
                    class="px-3 py-2 text-sm rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800"
                  >
                    Edit in Manage
                  </Link>

                  <button
                    v-if="isOwner && !social_feed?.is_public"
                    type="button"
                    class="px-3 py-2 text-sm rounded-lg text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20"
                    @click="confirmingMakePublic = true"
                  >
                    Make public
                  </button>

                  <button
                    v-if="isOwner && social_feed?.is_public"
                    type="button"
                    class="px-3 py-2 text-sm rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800"
                    @click="makePrivate"
                  >
                    Make private
                  </button>
                </div>
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

    <MakePublicConfirmationModal
      :show="confirmingMakePublic"
      @close="confirmingMakePublic = false"
      @confirm="makePublic"
    />
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from 'axios';
import { computed, ref } from 'vue';
import { Link, router, usePage } from "@inertiajs/vue3";

import ArticleCard from '@/Components/RssFeeds/ArticleCard.vue';
import MakePublicConfirmationModal from '@/Components/RssFeeds/MakePublicConfirmationModal.vue';
import SocialFeedSelector from '@/Components/RssFeeds/SocialFeedSelector.vue';

const { social_feed, social_feeds, feeds, pagination } = defineProps({
  social_feed: Object,
  social_feeds: Array,
  feeds: Array,
  pagination: Object,
  available_tags: Array,
  parameter_groups: Array,
});

const $page = usePage();
const currentUserId = computed(() => $page.props?.auth?.user?.id ?? null);
const isOwner = computed(() => currentUserId.value !== null && social_feed?.user_id === currentUserId.value);

const confirmingMakePublic = ref(false);

const makePublic = async () => {
  confirmingMakePublic.value = false;
  await axios.post(`/-/rss-feeds/${social_feed.id}/make-public`);
  router.reload({ only: ['social_feed', 'social_feeds'] });
};

const makePrivate = async () => {
  await axios.post(`/-/rss-feeds/${social_feed.id}/make-private`);
  router.reload({ only: ['social_feed', 'social_feeds'] });
};
</script>

