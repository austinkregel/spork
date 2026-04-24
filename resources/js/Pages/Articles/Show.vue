<template>
    <AppLayout :title="article.headline">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row">
                <aside class="w-full shrink-0 lg:w-80">
                    <SocialFeedSelector :social-feeds="social_feeds" :active-id="null" />
                </aside>

                <div class="flex-1">
                    <GlassCard>
                        <template #header>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <Link
                                        href="/-/feeds/rss-feeds"
                                        class="text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm dark:text-indigo-300"
                                    >
                                        &larr; Back to feed
                                    </Link>
                                    <h1 class="mt-2 text-2xl font-semibold leading-tight text-stone-900 dark:text-stone-50">
                                        {{ article.headline }}
                                    </h1>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                        <GlassPill v-if="article?.author?.name" tone="info">
                                            {{ article.author.name }}
                                        </GlassPill>
                                        <GlassPill tone="indigo">{{ formattedDate }}</GlassPill>
                                        <GlassPill
                                            v-for="tag in article?.tags ?? []"
                                            :key="tag.id ?? tag.name?.en ?? tag.name"
                                        >
                                            {{ tag?.name?.en ?? tag?.name }}
                                        </GlassPill>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <GlassButton
                                        v-if="article.url"
                                        variant="outline"
                                        size="sm"
                                        :href="article.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        :icon-left="ArrowTopRightOnSquareIcon"
                                    >
                                        Open original
                                    </GlassButton>
                                </div>
                            </div>
                        </template>

                        <article
                            class="prose prose-stone max-w-none text-stone-800 dark:prose-invert dark:text-stone-100"
                            v-html="article.content"
                        />
                    </GlassCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import dayjs from 'dayjs';
import { Link } from '@inertiajs/vue3';
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';
import AppLayout from "@/Layouts/AppLayout.vue";
import SocialFeedSelector from '@/Components/RssFeeds/SocialFeedSelector.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';

const props = defineProps({
    article: { type: Object, required: true },
    social_feeds: { type: Array, default: () => [] },
});

const formattedDate = computed(() => dayjs(props.article.created_at).format('MMM D, YYYY h:mma'));
</script>

<style scoped>
:deep(a) {
    color: rgb(79 70 229);
    text-decoration: underline;
}
:deep(.dark) :deep(a) {
    color: rgb(165 180 252);
}
:deep(img) {
    border-radius: 0.5rem;
    max-width: 100%;
    height: auto;
}
:deep(blockquote) {
    border-left: 4px solid var(--color-glass-border-light);
    padding-left: 1rem;
    color: rgb(87 83 78);
}
</style>
