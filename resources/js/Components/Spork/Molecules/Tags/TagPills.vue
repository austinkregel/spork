<script setup>
import TagPill from '@/Components/Spork/Molecules/Tags/TagPill.vue';

const props = defineProps({
    tags: {
        type: Array,
        default: () => [],
    },
    tone: {
        type: String,
        default: 'neutral',
    },
    size: {
        type: String,
        default: 'xs',
    },
    itemClass: {
        type: String,
        default: 'mr-2',
    },
});
</script>

<template>
    <TagPill
        v-for="tag in props.tags"
        :key="tag?.id ?? tag?.name?.en ?? tag?.name ?? String(tag)"
        :tag="tag"
        :tone="props.tone"
        :size="props.size"
        :class="props.itemClass"
    />
</template>





