<template>
    <AppLayout title="RSS Feeds">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row">
                <div class="w-full shrink-0 lg:w-80">
                    <SocialFeedSelector :social-feeds="social_feeds" :active-id="null" />
                </div>

                <div class="flex-1">
                    <GlassCard>
                        <template #header>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Feed</div>
                                    <div class="text-lg font-semibold text-stone-900 dark:text-stone-50">Latest articles</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <GlassButton variant="outline" size="sm" href="/-/manage/external-rss-feeds">Manage feeds</GlassButton>
                                    <GlassButton size="sm" @click="createOpen = true">Create social feed</GlassButton>
                                </div>
                            </div>
                        </template>

                        <div class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                            <ArticleCard v-for="topic in feeds ?? []" :key="topic.id" :article="topic" />
                            <GlassEmptyState
                                v-if="(feeds ?? []).length === 0"
                                title="No articles yet"
                                description="Articles will appear here once your feeds publish new content."
                            />
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
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ArticleCard from '@/Components/RssFeeds/ArticleCard.vue';
import SocialFeedModal from '@/Components/RssFeeds/SocialFeedModal.vue';
import SocialFeedSelector from '@/Components/RssFeeds/SocialFeedSelector.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';

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
