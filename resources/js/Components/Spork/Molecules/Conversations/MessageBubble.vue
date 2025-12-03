<script setup>
import { computed } from 'vue';

const props = defineProps({
    outbound: {
        type: Boolean,
        default: false,
    },
    fromLabel: {
        type: String,
        required: true,
    },
    timestampLabel: {
        type: String,
        default: '',
    },
    fontClass: {
        type: String,
        default: 'text-sm',
    },
    reply: {
        type: Object,
        default: null,
    },
});

const alignmentClasses = computed(() => (props.outbound ? 'items-end' : 'items-start'));
const bubbleClasses = computed(() =>
    props.outbound ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-stone-800 text-stone-800 dark:text-stone-100'
);
</script>

<template>
    <div class="flex flex-col group" :class="alignmentClasses" data-testid="message-bubble">
        <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400 mb-1">
            <span>{{ fromLabel }}</span>
            <span v-if="timestampLabel">·</span>
            <span v-if="timestampLabel">{{ timestampLabel }}</span>
        </div>
        <div class="flex relative right-0 flex-1">
            <div
                class="max-w-xl flex-grow rounded-2xl px-4 py-2 shadow-sm flex gap-2 relative left-0"
                :class="[bubbleClasses, fontClass]"
            >
                <div
                    v-if="reply"
                    class="mr-3 flex flex-col text-xs text-stone-700 dark:text-stone-200 bg-white/40 dark:bg-stone-900/60 rounded-lg px-3 py-2 border-l-4 border-indigo-500 mb-1 max-w-[250px]"
                >
                    <slot name="reply">
                        <span class="font-semibold truncate">
                            {{ reply.title }}
                        </span>
                        <span class="line-clamp-2 whitespace-pre-wrap">
                            {{ reply.body }}
                        </span>
                    </slot>
                </div>
                <div class="flex-1 min-w-0">
                    <slot />
                </div>
            </div>

            <div class="opacity-0 pl-4 group-hover:opacity-100 transition flex gap-1">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>

