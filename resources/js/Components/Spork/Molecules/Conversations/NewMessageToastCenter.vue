<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { XMarkIcon } from '@heroicons/vue/20/solid';
import { PaperAirplaneIcon } from '@heroicons/vue/24/outline';
import { useMessageToasts } from '@/Conversations/message-toast-store';

const { groups, dismiss } = useMessageToasts();

const canRender = computed(() => Boolean(groups.value.length));

const reply_open_by_group_id = ref({});
const reply_draft_by_group_id = ref({});
const reply_sending_by_group_id = ref({});

const viewThread = (threadId) => {
    router.visit(window.route('communication.chat.show', threadId), {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleReply = (groupId) => {
    reply_open_by_group_id.value = {
        ...reply_open_by_group_id.value,
        [groupId]: !reply_open_by_group_id.value[groupId],
    };
};

const isReplyOpen = (groupId) => Boolean(reply_open_by_group_id.value[groupId]);
const isReplySending = (groupId) => Boolean(reply_sending_by_group_id.value[groupId]);
const replyDraft = (groupId) => String(reply_draft_by_group_id.value[groupId] ?? '');

const setReplyDraft = (groupId, value) => {
    reply_draft_by_group_id.value = {
        ...reply_draft_by_group_id.value,
        [groupId]: value,
    };
};

const sendInlineReply = async (group) => {
    if (!group?.thread_id) {
        return;
    }

    const groupId = group.id;
    const draft = replyDraft(groupId).trim();
    const replyToEventId = group.messages?.[group.messages.length - 1]?.message_event_id ?? null;

    if (!draft) {
        return;
    }

    reply_sending_by_group_id.value = {
        ...reply_sending_by_group_id.value,
        [groupId]: true,
    };

    try {
        await axios.post('/api/message/reply', {
            message: draft,
            thread_id: group.thread_id,
            reply_to_event_id: replyToEventId,
        });

        setReplyDraft(groupId, '');
        reply_open_by_group_id.value = {
            ...reply_open_by_group_id.value,
            [groupId]: false,
        };
        dismiss(groupId);
        window.playSound?.('success');
    } finally {
        reply_sending_by_group_id.value = {
            ...reply_sending_by_group_id.value,
            [groupId]: false,
        };
    }
};

const personName = (person) => person?.name ?? person?.id ?? 'Someone';
</script>

<template>
    <div
        v-if="canRender"
        aria-live="polite"
        class="pointer-events-none fixed inset-0 z-50 flex items-end justify-end p-4 sm:p-6"
    >
        <div class="flex w-full flex-col items-end gap-3">
            <div
                v-for="group in groups"
                :key="group.id"
                class="pointer-events-auto w-full max-w-md overflow-hidden rounded-lg border border-stone-300/80 dark:border-stone-700/80 bg-gradient-to-br from-white/95 to-stone-50/95 dark:from-stone-900/95 dark:to-stone-950/95 shadow-xl ring-1 ring-stone-900/10 dark:ring-white/10 backdrop-blur"
                data-testid="new-message-toast"
            >
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <template v-if="group.messages?.[group.messages.length - 1]?.from_person?.photo_url">
                            <img
                                :src="group.messages[group.messages.length - 1].from_person.photo_url"
                                :alt="personName(group.messages[group.messages.length - 1].from_person)"
                                class="h-10 w-10 rounded-full object-cover ring-2 ring-white dark:ring-stone-900 shadow-sm"
                            />
                        </template>
                        <div v-else class="h-10 w-10 rounded-full bg-stone-100 dark:bg-stone-800 ring-2 ring-white dark:ring-stone-900 shadow-sm" />

                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-stone-900 dark:text-stone-100">
                                {{ group.total_count }} new message{{ group.total_count === 1 ? '' : 's' }}
                                in {{ group.thread_name ? group.thread_name : 'this thread' }}
                            </div>

                            <div class="mt-2 overflow-hidden rounded-md border border-stone-200/80 dark:border-stone-800/80 divide-y divide-stone-200/80 dark:divide-stone-800/80">
                                <div
                                    v-for="message in group.messages"
                                    :key="message.id"
                                    class="bg-white/80 dark:bg-stone-900/60 p-2"
                                >
                                    <div class="text-xs text-stone-500 dark:text-stone-300">
                                        {{ personName(message.from_person) }}
                                    </div>

                                    <div v-if="message.reply_to?.preview" class="mt-1 rounded bg-stone-50/80 dark:bg-stone-800/60 px-2 py-1 ring-1 ring-stone-900/5 dark:ring-white/5">
                                        <div class="text-[11px] text-stone-500 dark:text-stone-300">
                                            Replying to {{ personName(message.reply_to.from_person) }}
                                        </div>
                                        <div class="mt-0.5 text-[11px] text-stone-700 dark:text-stone-200 line-clamp-2">
                                            {{ message.reply_to.preview }}
                                        </div>
                                    </div>

                                    <div v-if="message.preview" class="mt-1 text-sm text-stone-700 dark:text-stone-200 line-clamp-2">
                                        {{ message.preview }}
                                    </div>
                                </div>
                            </div>

                            <div v-if="group.total_count > group.messages.length" class="mt-2 text-xs text-stone-500 dark:text-stone-300">
                                +{{ group.total_count - group.messages.length }} more
                            </div>

                            <div class="mt-3 flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-stone-100 dark:bg-stone-800 px-3 py-2 text-sm text-stone-800 dark:text-stone-100 hover:bg-stone-200 dark:hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    @click="viewThread(group.thread_id)"
                                >
                                    View thread
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-indigo-500 dark:bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-600 dark:hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    @click="toggleReply(group.id)"
                                >
                                    Reply
                                </button>
                            </div>

                            <!-- Android-style inline reply -->
                            <div v-if="isReplyOpen(group.id)" class="mt-3">
                                <div class="flex items-end gap-2 rounded-full border border-stone-200 dark:border-stone-800 bg-white/80 dark:bg-stone-900/60 px-3 py-2 shadow-sm ring-1 ring-stone-900/5 dark:ring-white/5">
                                    <textarea
                                        :value="replyDraft(group.id)"
                                        rows="1"
                                        class="flex-1 resize-none bg-transparent text-sm text-stone-800 dark:text-stone-100 placeholder-stone-400 focus:outline-none"
                                        placeholder="Reply…"
                                        :disabled="isReplySending(group.id)"
                                        @input="setReplyDraft(group.id, /** @type {HTMLTextAreaElement} */ ($event.target).value)"
                                        @keydown.enter.exact.prevent="sendInlineReply(group)"
                                        @keydown.enter.shift.exact.stop
                                    />
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-full bg-indigo-500 dark:bg-indigo-600 text-white p-2 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        :disabled="isReplySending(group.id) || !replyDraft(group.id).trim()"
                                        title="Send reply"
                                        @click="sendInlineReply(group)"
                                    >
                                        <span class="sr-only">Send</span>
                                        <PaperAirplaneIcon class="h-5 w-5 -rotate-45" />
                                    </button>
                                </div>
                                <div class="mt-1 text-[11px] text-stone-500 dark:text-stone-300">
                                    Press Enter to send • Shift+Enter for a new line
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex rounded-md bg-white dark:bg-stone-900 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            @click="dismiss(group.id)"
                        >
                            <span class="sr-only">Close</span>
                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


