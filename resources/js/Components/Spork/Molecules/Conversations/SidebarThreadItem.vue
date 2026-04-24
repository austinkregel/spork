<script setup>
import { computed } from 'vue';
import PillTag from '@/Components/Spork/Atoms/PillTag.vue';
import MarkdownPreview from '@/Components/Spork/Molecules/MarkdownPreview.vue';

const props = defineProps({
    thread: {
        type: Object,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    formatRelative: {
        type: Function,
        required: true,
    },
    maxParticipants: {
        type: Number,
        default: 3,
    },
    fallbackTitle: {
        type: String,
        default: 'Untitled Thread',
    },
});

const emit = defineEmits(['select']);

const threadName = computed(() => props.thread.name ?? props.fallbackTitle);
const messagePreview = computed(() => props.thread.latest_message_preview ?? 'No messages yet');

const participantNames = computed(() => {
    if (!props.thread.participants) {
        return [];
    }

    return props.thread.participants.slice(0, props.maxParticipants);
});

const rootClasses = computed(() => {
    const base = 'w-full text-left px-4 py-3 hover:bg-stone-100 dark:hover:bg-stone-900 transition flex flex-col gap-1 cursor-pointer';

    if (props.active) {
        return `${base} bg-indigo-50 dark:bg-stone-900/70 border-l-4 border-indigo-500`;
    }

    return base;
});
</script>

<template>
    <li>
        <button
            class="w-full"
            :class="rootClasses"
            @click="emit('select', thread)"
        >
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-stone-800 dark:text-stone-100 truncate">
                    {{ threadName }}
                </p>
                <span class="text-[11px] uppercase tracking-wide text-stone-400">
                    {{ formatRelative(thread.latest_message_at) }}
                </span>
            </div>
            <MarkdownPreview
                :source="messagePreview"
                class="text-xs text-stone-500 dark:text-stone-400 line-clamp-1"
            />
            <div class="flex items-center gap-1">
                <PillTag
                    v-for="person in participantNames"
                    :key="person.id"
                    :text="person.name"
                    size="xs"
                    tone="neutral"
                />
            </div>
        </button>
    </li>
</template>

