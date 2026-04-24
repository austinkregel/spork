<script setup>
import { computed } from 'vue';
import Pill from '@/Components/Spork/Atoms/Pill.vue';

const props = defineProps({
    tag: {
        type: [String, Object],
        required: true,
    },
    tone: {
        type: String,
        default: 'neutral',
    },
    size: {
        type: String,
        default: 'xs',
    },
});

const label = computed(() => {
    if (typeof props.tag === 'string') {
        return props.tag;
    }

    const name = props.tag?.name?.en ?? props.tag?.name;

    return name ?? String(props.tag?.id ?? '');
});
</script>

<template>
    <Pill :tone="props.tone" :size="props.size">
        {{ label }}
    </Pill>
</template>


