<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({ items: [], total: 0 }),
    },
    emojiOptions: {
        type: Array,
        default: () => [],
    },
    busy: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['react']);

const items = computed(() => props.summary?.items ?? []);
const emojiOptions = computed(() => props.emojiOptions ?? []);
const pickerOpen = ref(false);

watch(
    () => props.busy,
    (next) => {
        if (next) {
            pickerOpen.value = false;
        }
    }
);

const togglePicker = () => {
    pickerOpen.value = !pickerOpen.value;
};

const handleExistingReaction = (item) => {
    if (props.busy) {
        return;
    }

    emit('react', item);
};

const handleEmojiSelect = (emoji) => {
    if (props.busy) {
        return;
    }

    emit('react', { emoji, reacted: false });
    pickerOpen.value = false;
};
</script>

<template>
    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
        <button
            v-for="item in items"
            :key="item.emoji"
            type="button"
            class="inline-flex items-center gap-1 rounded-full border border-stone-200 dark:border-stone-700 px-2 py-1 transition-colors"
            :class="[
                busy ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                item.reacted
                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-200 border-indigo-200 dark:border-indigo-400'
                    : 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-100',
            ]"
            @click="handleExistingReaction(item)"
        >
            <span class="text-base leading-none">{{ item.emoji }}</span>
            <span class="font-semibold">{{ item.count }}</span>
        </button>

        <div v-if="emojiOptions.length" class="relative">
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-full border border-dashed border-stone-300 dark:border-stone-600 px-2 py-1 text-stone-600 dark:text-stone-200 hover:border-indigo-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
                :class="[busy ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer']"
                :disabled="busy"
                @click="togglePicker"
            >
                <span class="text-base leading-none">+</span>
                <span>Add</span>
            </button>

            <div
                v-if="pickerOpen"
                class="absolute z-20 mt-2 flex max-w-xs flex-wrap gap-2 rounded-lg border border-stone-200 bg-white p-3 shadow-lg dark:border-stone-700 dark:bg-stone-900"
            >
                <button
                    v-for="emoji in emojiOptions"
                    :key="emoji"
                    type="button"
                    class="text-2xl cursor-pointer hover:scale-110 transition-transform"
                    @click="handleEmojiSelect(emoji)"
                >
                    <span class="sr-only">React with {{ emoji }}</span>
                    <span aria-hidden="true">{{ emoji }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

