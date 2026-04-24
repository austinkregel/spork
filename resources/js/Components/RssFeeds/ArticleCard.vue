<script setup>
import dayjs from 'dayjs';
import { Link } from '@inertiajs/vue3';
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';
import GlassPill from '@/Components/Glass/GlassPill.vue';

defineProps({
    article: { type: Object, required: true },
});

const date = (d) => dayjs(d).format('YYYY-MM-DD HH:mm');
</script>

<template>
    <article class="px-4 py-3 transition-colors motion-reduce:transition-none hover:bg-stone-50/60 dark:hover:bg-stone-800/40">
        <div class="min-w-0">
            <div class="flex items-start justify-between gap-3">
                <Link
                    :href="`/-/feeds/articles/${article.id}`"
                    class="break-words text-base font-semibold text-stone-900 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm dark:text-stone-50"
                >
                    {{ article.headline }}
                </Link>
                <a
                    v-if="article.url"
                    :href="article.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Open original article in a new tab"
                    class="shrink-0 rounded-md p-1 text-stone-500 transition-colors motion-reduce:transition-none hover:bg-stone-200/50 hover:text-stone-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-stone-400 dark:hover:bg-stone-700/50 dark:hover:text-stone-100"
                >
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
                </a>
            </div>

            <div class="mt-2 line-clamp-3 text-sm text-stone-600 dark:text-stone-300" v-html="article.content" />

            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <GlassPill
                    v-for="tag in article.author?.tags ?? []"
                    :key="tag.id ?? tag.name?.en ?? tag.name"
                >
                    {{ tag?.name?.en ?? tag?.name }}
                </GlassPill>
                <GlassPill tone="indigo">{{ date(article.created_at) }}</GlassPill>
            </div>
        </div>
    </article>
</template>
