<script setup>
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Make feed public' },
    message: {
        type: String,
        default: 'Making a feed public shares it with all users. This can expose tags/conditions you may consider private. Continue?',
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <GlassModal :open="show" :title="title" size="sm" @close="emit('close')">
        <p class="text-sm text-stone-700 dark:text-stone-200">{{ message }}</p>
        <template #footer>
            <GlassButton variant="secondary" @click="emit('close')">Cancel</GlassButton>
            <GlassButton variant="destructive" @click="emit('confirm')">Make Public</GlassButton>
        </template>
    </GlassModal>
</template>
