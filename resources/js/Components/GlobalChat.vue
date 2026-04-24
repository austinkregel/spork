<template>
    <div
        class="fixed bottom-0 right-0 z-30 m-8 overflow-hidden border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] backdrop-blur-glass text-stone-900 dark:text-stone-50 shadow-lg transition-[width,height,border-radius] motion-reduce:transition-none"
        :class="[open ? 'rounded-lg' : 'rounded-full']"
        :style="{ width, height }"
    >
        <div v-if="open" class="flex items-center justify-between border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-2">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    aria-label="Back to conversations"
                    class="rounded-md p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    @click="active.chat = null; active.messages = []; title = 'Chat';"
                >
                    <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                </button>
                <span class="text-sm font-semibold">{{ title }}</span>
            </div>
            <button
                type="button"
                aria-label="Close chat"
                class="rounded-md p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                @click="open = !open"
            >
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>

        <div v-else class="flex h-full items-center justify-center">
            <button
                type="button"
                aria-label="Open chat"
                class="rounded-full p-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                @click="open = !open"
            >
                <ChatBubbleLeftRightIcon class="h-6 w-6 text-stone-700 dark:text-stone-100" aria-hidden="true" />
            </button>
        </div>

        <div class="overflow-hidden transition-all motion-reduce:transition-none" :class="[active.chat != null ? 'h-full' : 'h-0']">
            <div v-if="active.chat" class="flex items-center justify-between gap-x-4 border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-2">
                <div class="flex min-w-0 items-center gap-x-2">
                    <img class="h-8 w-8 flex-none rounded-full bg-stone-100 dark:bg-stone-700" :src="active.chat.imageUrl" alt="">
                    <div class="min-w-0 flex-auto">
                        <div class="truncate text-sm font-semibold text-stone-900 dark:text-stone-50">{{ active.chat.name }}</div>
                        <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ active.chat.email }}</p>
                    </div>
                </div>
            </div>
            <div class="flex h-[283px] flex-col">
                <div class="flex-1 overflow-y-auto bg-stone-50/40 dark:bg-stone-900/40 p-2">
                    <div v-for="message in (active?.chat?.messages ?? [])" :key="message.id" class="flex flex-col gap-2">
                        <Message :message="message" :self="page.props.auth.user?.person" />
                    </div>
                </div>
                <div class="flex gap-2 border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] p-2">
                    <GlassInput v-model="input" class="flex-1" @enter="sendMessage" />
                    <GlassButton size="sm" @click="sendMessage">Send</GlassButton>
                </div>
            </div>
        </div>

        <div v-if="open && !active.chat" class="h-full overflow-y-auto divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
            <div v-for="person in page.props.conversations.data" :key="person.thread_id" class="flex items-center justify-between gap-x-4 px-4 py-2">
                <div class="flex min-w-0 items-center gap-x-2">
                    <img class="h-8 w-8 flex-none rounded-full bg-stone-100 dark:bg-stone-700" :src="person.imageUrl" alt="">
                    <div class="min-w-0 flex-auto">
                        <button
                            type="button"
                            class="block w-full truncate text-left text-sm font-semibold text-stone-900 dark:text-stone-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm"
                            @click="showChat = true; active.chat = person; title = 'Chatting with ' + person.name"
                        >
                            {{ formatMatrixServer(person.name).name }}
                        </button>
                        <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ formatMatrixServer(person.name).server }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { XMarkIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/solid';
import { ChevronLeftIcon } from '@heroicons/vue/20/solid';
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import Message from "@/Components/Messages/Message.vue";

const open = ref(false);
const page = usePage();

const formatMatrixServer = (name) => {
    const [username, server] = name.split(':');
    return { name: username, server };
};

const width = computed(() => (open.value ? '325px' : '40px'));
const height = computed(() => (open.value ? '450px' : '40px'));

const active = ref({ chat: null, messages: [] });
const showChat = ref(false);
const title = ref('Chat');
const input = ref('');

const sendMessage = async () => {
    await axios.post('/api/message/reply', {
        message: input.value,
        thread_id: active.value.chat.thread_id,
    });
    input.value = '';
    setTimeout(() => router.reload({ only: ['conversations.threads'] }), 250);
};
</script>
