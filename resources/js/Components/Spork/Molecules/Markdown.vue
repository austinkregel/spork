<template>
<div class="prose dark:prose-invert" v-html="renderedMarkdown"></div>
</template>

<script setup>
import markdownit from 'markdown-it'
import { full as emoji } from 'markdown-it-emoji'
import hljs from 'highlight.js' // https://highlightjs.org
import { spoiler } from '@/Support/markdown-it-spoiler'
import { defineProps, computed } from 'vue';

// Actual default values
const props = defineProps({
    source: {
        type: String,
        required: true,
    },
});

const renderedMarkdown = computed(() => markdownit({
  highlight: function (str, lang) {
    if (lang && hljs.getLanguage(lang)) {
      try {
        return hljs.highlight(str, { language: lang }).value;
      } catch (__) {}
    }

    return ''; // use external default escaping
  },
  html: true,
}).use(emoji).use(spoiler).render(props.source ?? ''));
</script>

<style scoped lang="css">
.prose :deep(*) {
    img { display: inline-block;}
    video { display: inline-block;}
    audio { display: inline-block;}
    iframe { display: inline-block;}
    object { display: inline-block;}
    embed { display: inline-block;}
    applet { display: inline-block;}
    param { display: inline-block;}
    object { display: inline-block;}
    embed { display: inline-block;}
    applet { display: inline-block;}
}

.prose :deep(blockquote) {
    border-left: 4px solid #e2e8f0;
    padding-left: 1rem;
    margin-left: 4px;
    margin-right: 0;
    font-style: italic;
    color: #64748b;
    background-color: #f1f5f9;
    border-radius: 0.25rem;
    padding: 0.5rem 1rem;
}

.prose {
    min-height: 0 !important;
    
}

.dark\:prose-invert :deep(blockquote) {
    border-left: 4px solid #475569;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding-left: 1rem !important;
    padding-right: 1rem !important;
    padding-top: 0rem !important;
    padding-bottom: 0rem !important;
    border-radius: 0.25rem !important;
    color: #94a3b8;
    background-color: #0f172a;
}
</style>