<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import LinkPreviewCard from '@/Components/Spork/Atoms/LinkPreviewCard.vue';
import {
    buildPreviewFromUrl,
    derivePreviewFromSettings,
    extractLinks,
    isLikelyImageUrl,
    type LinkPreviewData,
    resolveMediaFromLink,
    type InlineMediaResult,
    hydrateMediaViaProxy,
} from '@/Support/link-utils';
import Markdown from '@/Components/Spork/Molecules/Markdown.vue';

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    fontClass: {
        type: String,
        default: 'text-sm',
    },
    markdownClass: {
        type: String,
        default: 'prose max-w-none dark:prose-invert',
    },
});

const emit = defineEmits<{
    (e: 'media-loaded'): void;
}>();

const notifyMediaReady = () => emit('media-loaded');

const textBody = computed(() => props.message.html_message ?? props.message.message ?? '');
const links = computed(() => extractLinks(textBody.value));

const inlineMediaCandidate = computed<InlineMediaResult | null>(() => {
    if (props.message.thumbnail_url) {
        return {
            url: props.message.thumbnail_url,
            kind: 'image',
            provider: 'attachment',
            source: props.message.thumbnail_url,
        };
    }

    for (const link of links.value) {
        const resolved = resolveMediaFromLink(link);

        if (resolved) {
            return resolved;
        }

        if (isLikelyImageUrl(link)) {
            return {
                url: link,
                kind: 'image',
                provider: 'direct',
                source: link,
            };
        }
    }

    return null;
});

const hydratedMedia = ref<InlineMediaResult | null>(null);

watch(
    () => inlineMediaCandidate.value,
    (candidate, _, onCleanup) => {
        let cancelled = false;
        onCleanup(() => {
            cancelled = true;
        });

        if (!candidate) {
            hydratedMedia.value = null;
            return;
        }

        if (candidate.needsHydration && candidate.source) {
            hydrateMediaViaProxy(candidate.source).then((result) => {
                if (cancelled) {
                    return;
                }

                hydratedMedia.value = result ?? null;
            });

            return;
        }

        hydratedMedia.value = candidate;
    },
    { immediate: true }
);

const inlineMedia = computed<InlineMediaResult | null>(() => hydratedMedia.value ?? inlineMediaCandidate.value);

const previewLink = computed(() => {
    const nonImageLinks = links.value.filter((link) => !isLikelyImageUrl(link));
    if (nonImageLinks.length > 0) {
        return nonImageLinks[0];
    }

    if (!inlineMedia.value && links.value.length > 0) {
        return links.value[0];
    }

    return null;
});

const previewFromSettings = computed<LinkPreviewData | null>(() => derivePreviewFromSettings(props.message.settings));
const generatedPreview = computed<LinkPreviewData | null>(() => (previewLink.value ? buildPreviewFromUrl(previewLink.value) : null));

const preview = computed(() => previewFromSettings.value ?? generatedPreview.value);

const bodyIsOnlyLink = computed(() => {
    const trimmed = textBody.value.trim();
    if (!trimmed) {
        return false;
    }

    if (inlineMediaCandidate.value?.source && trimmed === inlineMediaCandidate.value.source) {
        return true;
    }

    if (previewLink.value && trimmed === previewLink.value) {
        return true;
    }

    return false;
});

const shouldRenderMarkdown = computed(() => {
    if (!textBody.value) {
        return false;
    }

    return !bodyIsOnlyLink.value;
});
</script>

<template>
    <div class="flex flex-col gap-3 w-full">
        <div v-if="inlineMedia" class="rounded-2xl overflow-hidden -my-3 -mx-4">
            <template v-if="inlineMedia.kind === 'video'">
                <video
                    class="w-full h-auto object-contain max-h-96 bg-stone-900/5 dark:bg-stone-100/5"
                    :src="inlineMedia.mp4Url ?? inlineMedia.url"
                    autoplay
                    loop
                    muted
                    playsinline
                    controls
                    @loadedmetadata="notifyMediaReady"
                    @loadeddata="notifyMediaReady"
                />
            </template>
            <template v-else>
                <img
                    :src="inlineMedia.url"
                    :alt="`Shared media from ${message.from_person?.name ?? 'conversation'}`"
                    class="w-full h-auto object-contain max-h-96 bg-stone-900/5 dark:bg-stone-100/5"
                    loading="lazy"
                    @load="notifyMediaReady"
                />
            </template>
            <div
                v-if="inlineMedia.provider"
                class="px-3 py-2 text-[11px] uppercase tracking-wide text-stone-500 dark:text-stone-400 border-t border-stone-200 dark:border-stone-700 bg-white/60 dark:bg-stone-900/60"
            >
                {{ inlineMedia.provider }} gif
            </div>
        </div>

        <Markdown
            v-if="shouldRenderMarkdown"
            :class="[markdownClass, fontClass, 'whitespace-pre-wrap']"
            :source="textBody.trim()"
        />

        <LinkPreviewCard v-if="preview" :preview="preview" class="-my-3 -mx-4" />
    </div>
</template>

