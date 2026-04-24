<template>
    <AppLayout :title="labels.title ?? 'Unified Chat'">
        <div class="flex h-[calc(100vh-65px)] divide-x divide-stone-200 dark:divide-stone-800 bg-white dark:bg-stone-900">
            <aside class="w-80 lg:w-96 flex flex-col bg-stone-50 dark:bg-stone-950">
                <div class="border-b border-stone-200 dark:border-stone-800 p-4 space-y-3">
                    <div class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Channels</div>
                    <h1 class="text-2xl font-semibold text-stone-800 dark:text-white">Unified Chat</h1>
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-stone-400" />
                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded-md border border-stone-200 dark:border-stone-800 bg-white/80 dark:bg-stone-900 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-stone-800 dark:text-stone-100 placeholder-stone-400"
                            placeholder="Search people or threads"
                        />
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scroll">
                    <ul class="divide-y divide-stone-200 dark:divide-stone-800">
                        <SidebarThreadItem
                            v-for="thread in filteredThreads"
                            :key="thread.id"
                            :thread="thread"
                            :active="thread.id === activeThreadId"
                            :format-relative="formatRelative"
                            @select="() => openThread(thread.id)"
                        />
                    </ul>
                </div>
                <div class="border-t border-stone-200 dark:border-stone-800 p-4 text-xs text-stone-500 dark:text-stone-400">
                    Showing {{ filteredThreads.length }} of {{ paginator.total ?? filteredThreads.length }} threads
                </div>
            </aside>

            <section class="flex-1 flex flex-col relative">
                <template v-if="activeThread">
                    <header class="border-b border-stone-200 dark:border-stone-800 px-6 py-4 flex items-center justify-between bg-white/80 dark:bg-stone-900/80 backdrop-blur">
                        <div class="flex items-center gap-3">
                            <AvatarStack
                                :avatars="activeThreadAvatars"
                                size="md"
                                :max-visible="3"
                            />
                            <div>
                                <div class="text-base font-semibold text-stone-900 dark:text-white">
                                    {{ activeThread.name ?? participantSummary }}
                                </div>
                                <div class="text-xs text-stone-500 dark:text-stone-400">
                                    {{ activeThread.participants.length }} participants • Always-on sync
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <IconCircleButton
                                sr-text="Archive thread"
                                title="Archive thread"
                                variant="ghost"
                                size="sm"
                                @click="runThreadAction('archive')"
                            >
                                <ArchiveBoxIcon class="h-4 w-4" />
                            </IconCircleButton>
                            <IconCircleButton
                                :sr-text="isMuted ? 'Unmute thread' : 'Mute thread'"
                                :title="isMuted ? 'Unmute thread' : 'Mute thread'"
                                variant="ghost"
                                size="sm"
                                @click="toggleMute"
                            >
                                <BellSlashIcon class="h-4 w-4" />
                            </IconCircleButton>
                            <IconCircleButton
                                sr-text="Delete thread"
                                title="Delete thread"
                                variant="danger"
                                size="sm"
                                @click="runThreadAction('delete')"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </IconCircleButton>
                        </div>
                    </header>

                    <main
                        ref="messagePane"
                        class="flex-1 overflow-y-auto px-3 lg:px-6 py-4 space-y-3 bg-stone-100/70 dark:bg-stone-900"
                        @scroll.passive="handleScroll"
                    >
                        <div
                            v-for="message in orderedMessages"
                            :key="message.id"
                            :class="messageRowClasses(message)"
                            data-testid="message-row"
                        >
                            <img
                                v-if="isDiscordDensity"
                                v-bind="messageAvatarProps(message)"
                                class="h-10 w-10 rounded-full object-cover ring-2 ring-white dark:ring-stone-900 shadow-sm"
                                data-testid="message-avatar"
                            />

                            <MessageBubble
                                :outbound="isOutbound(message)"
                                :align-override="isDiscordDensity ? 'start' : null"
                                :from-label="message.from_person?.name ?? message.from_person?.id ?? 'Unknown'"
                                :timestamp-label="formatRelative(message.originated_at)"
                                :font-class="messageFontClass"
                                :reply="buildReplyPreview(message.reply_to)"
                            >
                                <MessageContentRenderer
                                    :message="message"
                                    :font-class="messageFontClass"
                                    @media-loaded="handleInlineMediaLoaded"
                                />


                                <template #actions>
                                    <MessageReactionBar
                                        :summary="message.reaction_summary ?? { items: [] }"
                                        :emoji-options="composerMeta.emoji ?? []"
                                        :busy="isReactionBusy(message.id)"
                                        @react="(reaction) => handleReaction(message, reaction)"
                                    />
                                    <IconCircleButton
                                        sr-text="Reply to message"
                                        variant="ghost"
                                        size="sm"
                                        @click="startReply(message)"
                                    >
                                        <ArrowUturnLeftIcon class="h-4 w-4" />
                                    </IconCircleButton>
                                    <IconCircleButton
                                        sr-text="Copy message text"
                                        variant="ghost"
                                        size="sm"
                                        @click="copyMessage(message)"
                                    >
                                        <ClipboardIcon class="h-4 w-4" />
                                    </IconCircleButton>
                                    <IconCircleButton
                                        sr-text="Delete message"
                                        variant="danger"
                                        size="sm"
                                        @click="deleteMessage(message.id)"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </IconCircleButton>
                                </template>
                            </MessageBubble>
                        </div>
                    </main>

                    <button
                        v-if="autoScrollLocked"
                        class="absolute bottom-28 right-6 inline-flex items-center gap-1 bg-indigo-600 text-white text-xs px-3 py-1 rounded-full shadow cursor-pointer"
                        @click="snapToBottom"
                        title="Jump to latest message"
                    >
                        <ChevronDownIcon class="h-4 w-4" />
                        <span class="sr-only">Jump to latest</span>
                    </button>

                    <footer class="border-t border-stone-200 dark:border-stone-800 p-4 space-y-3 bg-white dark:bg-stone-900">
                        <div
                            v-if="replyingTo"
                            class="flex items-start gap-3 rounded-xl  bg-stone-50 dark:bg-stone-800 px-3 py-2 text-xs text-stone-600 dark:text-stone-200 max-h-24 overflow-y-auto"
                        >
                            <div class="border-l-4 border-indigo-500 pl-3 flex-1">
                                <div class="font-semibold text-stone-700 dark:text-stone-100 mb-1">
                                    Replying to {{ replyingTo.author }}
                                </div>
                                <div class="line-clamp-3 whitespace-pre-wrap">
                                    {{ replyingTo.preview }}
                                </div>
                            </div>
                            <button class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 cursor-pointer" @click="replyingTo = null" title="Cancel reply">
                                <span class="sr-only">Cancel reply</span>
                                <span aria-hidden="true">✕</span>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 mx-4 text-stone-500 dark:text-stone-300">
                            <button class="inline-flex items-center gap-1 rounded-full text-xs text-stone-600 dark:text-stone-200 cursor-pointer" @click="toggleEmoji" title="Toggle emoji picker">
                                <FaceSmileIcon class="h-5 w-5" />
                                <span class="sr-only">Emoji</span>
                            </button>
                            <button class="inline-flex items-center gap-1 rounded-full text-xs text-stone-600 dark:text-stone-200 font-bold cursor-pointer" @click="applyFormatting('bold')" title="Apply bold formatting">
                                <span class="sr-only">Bold</span>
                                <span aria-hidden="true" class="text-base">B</span>
                            </button>
                            <button class="inline-flex items-center gap-1 rounded-full text-xs text-stone-600 dark:text-stone-200 italic cursor-pointer" @click="applyFormatting('italic')" title="Apply italic formatting">
                                <span class="sr-only">Italic</span>
                                <span aria-hidden="true" class="text-base">I</span>
                            </button>
                            <button class="inline-flex items-center gap-1 rounded-full tracking-tightest text-xs text-stone-600 dark:text-stone-200 font-mono cursor-pointer" @click="applyFormatting('code')" title="Apply code formatting">
                                <span class="sr-only">Code</span>
                                <CodeBracketIcon class="h-5 w-5" />
                            </button>
                            <label class="text-xs flex items-center gap-1">
                                Density
                                <select v-model="messageFontSize" class="rounded-md  bg-transparent text-xs">
                                    <option value="text-sm">Comfortable</option>
                                    <option value="text-base">Standard</option>
                                    <option value="text-lg">Relaxed</option>
                                </select>
                            </label>
                        </div>
                        <div v-if="emojiOpen" class="flex flex-wrap gap-2 rounded-lg  bg-white dark:bg-stone-800 p-2 text-2xl">
                            <button
                                v-for="emoji in composerMeta.emoji ?? []"
                                :key="emoji"
                                class="cursor-pointer"
                                @click="appendEmoji(emoji)"
                                :title="`Insert ${emoji} emoji`"
                            >
                                <span class="sr-only">Insert {{ emoji }} emoji</span>
                                <span aria-hidden="true">{{ emoji }}</span>
                            </button>
                        </div>
                        <div class="flex items-end gap-3">
                            <textarea
                                ref="composerRef"
                                v-model="draft"
                                class="flex-1 resize-none rounded-2xl border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-3 text-sm text-stone-800 dark:text-stone-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                rows="3"
                                placeholder="Type a message, use Shift + Enter for a new line"
                                @keydown.enter.exact.prevent="sendMessage"
                                @keydown.enter.shift.exact.stop
                            />
                            <button
                                class="inline-flex items-center justify-center rounded-full bg-indigo-600 text-white p-3 disabled:opacity-50 cursor-pointer"
                                :disabled="sending || !draft.trim() || !activeThread"
                                @click="sendMessage"
                                title="Send message"
                            >
                                <PaperAirplaneIcon class="h-5 w-5 -rotate-45" />
                                <span class="sr-only">Send message</span>
                            </button>
                        </div>
                    </footer>
                </template>

                <div v-else class="flex flex-1 items-center justify-center text-center text-stone-500 dark:text-stone-400" data-testid="conversation-empty">
                    <div>
                        <h2 class="text-3xl font-semibold mb-2 text-stone-800 dark:text-white">No conversation selected</h2>
                        <p>Select a thread on the left to get started.</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, nextTick, ref, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import relativeTime from 'dayjs/plugin/relativeTime';
import AvatarStack from '@/Components/Spork/Atoms/AvatarStack.vue';
import IconCircleButton from '@/Components/Spork/Atoms/IconCircleButton.vue';
import MessageBubble from '@/Components/Spork/Molecules/Conversations/MessageBubble.vue';
import MessageContentRenderer from '@/Components/Spork/Molecules/Conversations/MessageContentRenderer.vue';
import MessageReactionBar from '@/Components/Spork/Molecules/Conversations/MessageReactionBar.vue';
import SidebarThreadItem from '@/Components/Spork/Molecules/Conversations/SidebarThreadItem.vue';

import { PaperAirplaneIcon, CodeBracketIcon, FaceSmileIcon, ArrowUturnLeftIcon, ClipboardIcon, TrashIcon, BellSlashIcon, ArchiveBoxIcon, ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

dayjs.extend(utc);
dayjs.extend(relativeTime);

const page = usePage();
const search = ref('');
const draft = ref('');
const sending = ref(false);
const emojiOpen = ref(false);
const messagePane = ref(null);
const composerRef = ref(null);
const autoScrollLocked = ref(false);
const replyingTo = ref(null);
const reactionBusy = ref({});
const startReply = (message) => {
    replyingTo.value = {
        id: message.id,
        event_id: message.event_id,
        preview: message.message,
        author: message.from_person?.name ?? 'Unknown',
    };
    focusComposer();
};
const messageFontSize = ref('text-sm');
const isDiscordDensity = computed(() => messageFontSize.value === 'text-sm');

const placeholderAvatar = (person) => {
    return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(person.name ?? 'Chat')}`;
};

const threads = computed(() => page.props.threads?.data ?? []);
const paginator = computed(() => page.props.threads ?? {});
const activeThread = computed(() => page.props.activeThread ?? null);
const activeThreadId = computed(() => activeThread.value?.id ?? null);
const labels = computed(() => page.props.labels ?? {});
const composerMeta = computed(() => page.props.composer ?? {});
const participantsById = computed(() => {
    if (!activeThread.value?.participants) {
        return {};
    }

    return activeThread.value.participants.reduce((carry, participant) => {
        if (participant?.id === undefined || participant?.id === null) {
            return carry;
        }

        carry[participant.id] = participant;
        return carry;
    }, {});
});
const activeThreadAvatars = computed(() => {
    if (!activeThread.value?.participants) {
        return [];
    }

    return activeThread.value.participants.map((participant, index) => ({
        id: participant.id ?? index,
        src: participant.photo_url ?? placeholderAvatar(participant),
        alt: participant.name ?? 'Participant',
    }));
});

const normalizeTimestamp = (value) => {
    if (value === null || value === undefined) {
        return dayjs.invalid();
    }

    if (typeof value === 'number') {
        const seconds = value > 1e12 ? value / 1000 : value;
        return dayjs.unix(seconds).utc();
    }

    if (typeof value === 'string') {
        const numeric = Number(value);

        if (!Number.isNaN(numeric)) {
            const seconds = numeric > 1e12 ? numeric / 1000 : numeric;
            return dayjs.unix(seconds).utc();
        }

        return dayjs.utc(value);
    }

    if (value?.date) {
        return dayjs.utc(value.date);
    }

    return dayjs.utc(value);
};

const filteredThreads = computed(() => {
    if (!search.value.trim()) {
        return threads.value;
    }

    return threads.value.filter((thread) => {
        const haystack = `${thread.name ?? ''} ${thread.latest_message_preview ?? ''} ${thread.participants?.map((p) => p.name).join(' ')}`;
        return haystack.toLowerCase().includes(search.value.toLowerCase());
    });
});

const orderedMessages = computed(() => {
    if (!activeThread.value?.messages) {
        return [];
    }

    return [...activeThread.value.messages].sort((a, b) => normalizeTimestamp(a.originated_at).valueOf() - normalizeTimestamp(b.originated_at).valueOf());
});

const participantSummary = computed(() => {
    if (!activeThread.value) {
        return 'Conversation';
    }

    return activeThread.value.participants.map((person) => person.name).join(', ');
});

const isMuted = computed(() => Boolean(activeThread.value?.settings?.muted));

const openThread = (threadId) => {
    router.visit(route('communication.chat.show', threadId), {
        preserveScroll: true,
        preserveState: true,
        only: ['threads', 'activeThread'],
        onSuccess: () => nextTick(() => snapToBottom()),
    });
};

const toggleMute = () => {
    runThreadAction(isMuted.value ? 'unmute' : 'mute');
};

const formatRelative = (timestamp) => {
    const instance = normalizeTimestamp(timestamp);

    if (!instance.isValid()) {
        return '';
    }

    return instance.fromNow();
};

const messageFontClass = computed(() => {
    return messageFontSize.value;
});

const buildReplyPreview = (reply) => {
    if (!reply) {
        return null;
    }

    return {
        title: reply.from_person?.name ?? 'Reply',
        body: reply.preview ?? reply.message ?? '',
    };
};

const isOutbound = (message) => {
    const userPersonId = page.props.auth?.user?.person?.id;
    return message.from_person === userPersonId || message.is_user === true;
};

const messageRowClasses = (message) => [
    'flex items-start gap-3',
    {
        'flex-row-reverse': !isDiscordDensity.value && isOutbound(message),
    },
];

const resolveMessageAuthor = (message) => {
    if (!message?.from_person) {
        return null;
    }

    if (typeof message.from_person === 'object') {
        return message.from_person;
    }

    return participantsById.value[message.from_person] ?? null;
};

const messageAvatarProps = (message) => {
    const author = resolveMessageAuthor(message);
    const fallbackName = author?.name ?? 'Participant';
    return {
        src: author?.photo_url ?? placeholderAvatar({ name: fallbackName }),
        alt: fallbackName,
    };
};

const handleScroll = () => {
    if (!messagePane.value) {
        return;
    }

    const el = messagePane.value;
    const threshold = 80;
    autoScrollLocked.value = el.scrollHeight - el.scrollTop - el.clientHeight > threshold;
};

const handleInlineMediaLoaded = () => {
    if (autoScrollLocked.value) {
        return;
    }

    nextTick(() => snapToBottom());
};

const snapToBottom = () => {
    if (!messagePane.value) {
        return;
    }
    autoScrollLocked.value = false;
    messagePane.value.scrollTop = messagePane.value.scrollHeight;
};

watch(
    () => orderedMessages.value.length,
    () => {
        if (autoScrollLocked.value) {
            return;
        }

        nextTick(() => snapToBottom());
    }
);

function consumeReplyIntentFromSession() {
    const threadId = sessionStorage.getItem('spork.chat.reply_to_thread_id');
    const eventId = sessionStorage.getItem('spork.chat.reply_to_event_id');

    if (!threadId || !eventId) {
        return;
    }

    if (String(threadId) !== String(activeThreadId.value ?? '')) {
        return;
    }

    const message = (activeThread.value?.messages ?? []).find((item) => String(item?.event_id ?? '') === String(eventId));

    if (!message) {
        return;
    }

    sessionStorage.removeItem('spork.chat.reply_to_thread_id');
    sessionStorage.removeItem('spork.chat.reply_to_event_id');

    startReply(message);
}

onMounted(() => {
    nextTick(() => {
        snapToBottom();
        consumeReplyIntentFromSession();
    });
});

watch(
    () => activeThreadId.value,
    () => nextTick(() => consumeReplyIntentFromSession())
);

const toggleEmoji = () => {
    emojiOpen.value = !emojiOpen.value;
};

const appendEmoji = (emoji) => {
    draft.value += emoji;
    emojiOpen.value = false;
    focusComposer();
};

const applyFormatting = (preset) => {
    const tokens = {
        bold: '**bold**',
        italic: '_italic_',
        code: '`code`',
    };

    draft.value += draft.value.endsWith(' ') || !draft.value ? tokens[preset] : ` ${tokens[preset]}`;
    focusComposer();
};

const focusComposer = () => {
    if (!composerRef.value) {
        return;
    }

    composerRef.value.focus();
};

const refreshConversation = () => {
    router.reload({
        preserveScroll: true,
        only: ['threads', 'activeThread'],
    });
};

const setReactionBusy = (messageId, state) => {
    reactionBusy.value = {
        ...reactionBusy.value,
        [messageId]: state,
    };
};

const isReactionBusy = (messageId) => Boolean(reactionBusy.value[messageId]);

const handleReaction = async (message, reaction) => {
    if (!message?.id || !reaction?.emoji) {
        return;
    }

    if (reactionBusy.value[message.id]) {
        return;
    }

    setReactionBusy(message.id, true);

    try {
        if (reaction.reacted) {
            await axios.delete(`/api/chat/messages/${message.id}/reactions`, {
                data: { emoji: reaction.emoji },
            });
        } else {
            await axios.post(`/api/chat/messages/${message.id}/reactions`, {
                emoji: reaction.emoji,
            });
        }

        refreshConversation();
    } finally {
        setReactionBusy(message.id, false);
    }
};

const sendMessage = async () => {
    if (!draft.value.trim() || !activeThreadId.value) {
        return;
    }

    sending.value = true;

    try {
        await axios.post('/api/message/reply', {
            message: draft.value,
            thread_id: activeThreadId.value,
            reply_to_event_id: replyingTo.value?.event_id ?? null,
        });

        draft.value = '';
        replyingTo.value = null;
        emojiOpen.value = false;
        refreshConversation();
    } finally {
        sending.value = false;
        nextTick(() => snapToBottom());
    }
};

const runThreadAction = async (action) => {
    if (!activeThreadId.value) {
        return;
    }

    const urlMap = {
        archive: `/api/chat/threads/${activeThreadId.value}/archive`,
        unarchive: `/api/chat/threads/${activeThreadId.value}/unarchive`,
        mute: `/api/chat/threads/${activeThreadId.value}/mute`,
        unmute: `/api/chat/threads/${activeThreadId.value}/unmute`,
    };

    try {
        if (action === 'delete') {
            await axios.delete(`/api/chat/threads/${activeThreadId.value}`);
        } else if (urlMap[action]) {
            await axios.post(urlMap[action]);
        }
    } finally {
        refreshConversation();
    }
};

const deleteMessage = async (messageId) => {
    try {
        await axios.delete(`/api/chat/messages/${messageId}`);
    } finally {
        refreshConversation();
    }
};

const copyMessage = async (message) => {
    try {
        await navigator.clipboard.writeText(message.message ?? '');
    } catch (_) {
        // ignore clipboard issues
    }
};
</script>


