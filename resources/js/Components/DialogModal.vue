<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-4">
            <div class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                <slot name="title" />
            </div>

            <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end px-6 py-4 bg-zinc-100 dark:bg-zinc-800 text-right">
            <slot name="footer" />
        </div>
    </Modal>
</template>
