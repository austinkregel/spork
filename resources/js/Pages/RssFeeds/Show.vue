<template>
    <AppLayout title="Social Feed">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row">
                <div class="w-full shrink-0 lg:w-80">
                    <SocialFeedSelector :social-feeds="social_feeds" :active-id="social_feed?.id ?? null" />
                </div>

                <div class="flex-1">
                    <GlassCard>
                        <template #header>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <div class="truncate text-lg font-semibold text-stone-900 dark:text-stone-50">
                                            {{ social_feed?.name }}
                                        </div>
                                        <GlassPill v-if="social_feed?.is_public" tone="success">Public</GlassPill>
                                        <GlassPill v-else>Private</GlassPill>
                                    </div>
                                    <div v-if="social_feed?.description" class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                                        {{ social_feed.description }}
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                        <GlassPill
                                            v-for="tag in social_feed?.tags ?? []"
                                            :key="tag.id ?? tag.name?.en ?? tag.name"
                                        >
                                            {{ tag?.name?.en ?? tag?.name }}
                                        </GlassPill>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <GlassButton variant="outline" size="sm" href="/-/manage/social-feeds">Edit in Manage</GlassButton>
                                    <GlassButton
                                        v-if="isOwner && !social_feed?.is_public"
                                        variant="destructive"
                                        size="sm"
                                        @click="confirmingMakePublic = true"
                                    >
                                        Make public
                                    </GlassButton>
                                    <GlassButton
                                        v-if="isOwner && social_feed?.is_public"
                                        variant="outline"
                                        size="sm"
                                        @click="makePrivate"
                                    >
                                        Make private
                                    </GlassButton>
                                </div>
                            </div>
                        </template>

                        <div class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                            <ArticleCard v-for="topic in feeds ?? []" :key="topic.id" :article="topic" />
                        </div>

                        <template #footer>
                            <div class="flex items-center justify-between gap-4">
                                <GlassButton
                                    variant="outline"
                                    size="sm"
                                    :href="pagination?.prev_page_url ?? undefined"
                                    :disabled="!pagination?.prev_page_url"
                                >
                                    Previous
                                </GlassButton>
                                <div class="text-xs text-stone-500 dark:text-stone-400">
                                    Page {{ pagination?.current_page ?? 1 }} of {{ pagination?.last_page ?? 1 }}
                                </div>
                                <GlassButton
                                    variant="outline"
                                    size="sm"
                                    :href="pagination?.next_page_url ?? undefined"
                                    :disabled="!pagination?.next_page_url"
                                >
                                    Next
                                </GlassButton>
                            </div>
                        </template>
                    </GlassCard>
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
import { router, usePage } from "@inertiajs/vue3";

import ArticleCard from '@/Components/RssFeeds/ArticleCard.vue';
import MakePublicConfirmationModal from '@/Components/RssFeeds/MakePublicConfirmationModal.vue';
import SocialFeedSelector from '@/Components/RssFeeds/SocialFeedSelector.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';

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
    await axios.post(`/-/feeds/rss-feeds/${social_feed.id}/make-public`);
    router.reload({ only: ['social_feed', 'social_feeds'] });
};

const makePrivate = async () => {
    await axios.post(`/-/feeds/rss-feeds/${social_feed.id}/make-private`);
    router.reload({ only: ['social_feed', 'social_feeds'] });
};
</script>
