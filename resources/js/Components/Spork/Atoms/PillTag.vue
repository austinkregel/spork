<script setup>
import Pill from '@/Components/Spork/Atoms/Pill.vue';

const props = defineProps({
    text: {
        type: [String, Number],
        required: false,
        default: '',
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
</script>

<template>
    <Pill :tone="props.tone" :size="props.size">
        <slot>
            {{ text }}
        </slot>
    </Pill>
</template>

