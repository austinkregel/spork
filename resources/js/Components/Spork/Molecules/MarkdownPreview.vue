<template>
    <div class="markdown-preview" v-html="previewHtml"></div>
</template>

<script setup>
import { computed } from 'vue';
import markdownit from 'markdown-it';
import { full as emoji } from 'markdown-it-emoji';
import hljs from 'highlight.js';
import { spoiler } from '@/Support/markdown-it-spoiler';

const props = defineProps({
    source: {
        type: String,
        default: '',
    },
});

const md = markdownit({
    highlight(str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (error) {
                console.warn('Highlight error', error);
            }
        }

        return '';
    },
})
    .use(emoji)
    .use(spoiler);

const firstBlockTokens = computed(() => {
    const tokens = md.parse(props.source ?? '', {});

    if (!tokens.length) {
        return [];
    }

    const collected = [];
    let depth = 0;
    let capturing = false;

    for (const token of tokens) {
        if (!capturing) {
            if (token.type.endsWith('_open')) {
                capturing = true;
                depth = 1;
                collected.push(token);
                continue;
            }

            if (token.type === 'inline' || token.type === 'text') {
                collected.push(token);
                break;
            }

            continue;
        }

        collected.push(token);

        if (token.type.endsWith('_open')) {
            depth += 1;
        } else if (token.type.endsWith('_close')) {
            depth -= 1;

            if (depth === 0) {
                break;
            }
        }
    }

    return collected;
});

const previewHtml = computed(() => {
    if (!firstBlockTokens.value.length) {
        return '';
    }

    return md.renderer.render(firstBlockTokens.value, md.options, {});
});
</script>

<style scoped>
.markdown-preview :deep(*) {
    margin: 0;
}
</style>

