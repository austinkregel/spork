<script setup>
import { Link } from '@inertiajs/vue3';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';

defineProps({
    socialFeeds: { type: Array, default: () => [] },
    activeId: { type: Number, default: null },
    manageHref: { type: String, default: '/-/manage/social-feeds' },
});
</script>

<template>
    <GlassSurface class="overflow-hidden">
        <div class="flex items-center justify-between border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Social Feeds</div>
                <div class="text-sm text-stone-700 dark:text-stone-200">Your feeds + public feeds</div>
            </div>
            <Link
                :href="manageHref"
                class="text-xs font-semibold text-indigo-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm dark:text-indigo-300"
            >
                Manage
            </Link>
        </div>

        <div class="space-y-1 p-2">
            <Link
                href="/-/feeds/rss-feeds"
                class="block rounded-md px-3 py-2 text-sm transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500"
                :class="activeId === null
                    ? 'bg-stone-100/80 text-stone-900 dark:bg-stone-700/60 dark:text-stone-50'
                    : 'text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/40'"
            >
                All articles
            </Link>

            <Link
                v-for="feed in socialFeeds ?? []"
                :key="feed.id"
                :href="`/-/feeds/rss-feeds/${feed.id}`"
                class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500"
                :class="feed.id === activeId
                    ? 'bg-stone-100/80 text-stone-900 dark:bg-stone-700/60 dark:text-stone-50'
                    : 'text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/40'"
            >
                <div class="min-w-0">
                    <div class="truncate font-medium">{{ feed.name }}</div>
                    <div v-if="feed.description" class="truncate text-xs text-stone-500 dark:text-stone-400">
                        {{ feed.description }}
                    </div>
                </div>
                <GlassPill v-if="feed.is_public" tone="success" size="sm">Public</GlassPill>
            </Link>
        </div>
    </GlassSurface>
</template>
