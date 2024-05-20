<template>
    <div class="fixed transition-all bottom-0 right-0 bg-blue-200 m-8 overflow-hidden z-0 shadow rounded dark:bg-stone-900 dark:text-white" :style="{ width, height }" :class="[open ? 'rounded' : 'rounded-full']">
        <div class="py-2 px-4 bg-amber-200 dark:bg-stone-950 flex items-center justify-between" v-if="open">
            <div class="flex items-center justify-center gap-4">
                <button @click="() => {active.chat = null; active.messages = []; title = 'Chat' }">
                    <ChevronLeftIcon  class="w-6 h-6 text-stone-100" />
                </button>
                {{ title }}
            </div>

            <div class="flex items-center">
                <button @click="open = !open">
                    <XMarkIcon  class="h-5 w-5 text-stone-800 dark:text-stone-50" />
                </button>
            </div>
        </div>

        <div v-else class="flex items-center justify-center h-full bg-stone-950">
            <button @click="open = !open"><ChatBubbleLeftRightIcon class="w-6 h-6 text-current" /></button>
        </div>

        <div class="overflow-hidden transition-all" :class="[active.chat != null ? 'h-full' : 'h-0']">
            <div class="flex justify-between items-center gap-x-6 py-2 px-4" v-if="active.chat">
                <div class="flex items-center min-w-0 gap-x-2">
                    <img class="h-8 w-8 flex-none rounded-full bg-stone-50 dark:bg-stone-600" :src="active.chat.imageUrl" alt="" />
                    <div class="min-w-0 flex-auto">
                        <div class="text-sm text-left font-semibold leading-1 text-stone-900 dark:text-stone-50 w-full text-wrap">
                            {{ active.chat.name }}
                        </div>
                        <p class="mt-1 truncate text-xs leading-1 text-stone-500 dark:text-stone-300">{{ active.chat.email }}</p>
                    </div>
                </div>
                <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                    <p class="text-sm leading-2 text-stone-900 dark:text-stone-50">{{ active.chat.role }}</p>
                    <p v-if="active.chat.lastSeen" class="mt-1 text-xs leading-5 text-stone-500 dark:text-stone-400">
                        Last seen <time :datetime="active.chat.lastSeenDateTime">{{ active.chat.lastSeen }}</time>
                    </p>
                    <div v-else class="mt-1 flex items-center gap-x-1.5">
                        <div class="flex-none rounded-full bg-emerald-500/20 p-1">
                            <div class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        </div>
                        <p class="text-xs leading-1 text-stone-500 dark:text-stone-400">Online</p>
                    </div>
                </div>
            </div>
            <div class="bg-stone-100 dark:bg-stone-800 relative flex flex-col justify-between">
                <div style="height: calc(283px)" class="overflow-y-scroll">
                    <div v-for="message in (active?.chat?.messages ?? [])" :key="message.id" class="flex flex-col gap-2">
                        <Message :message="message" :self="page.props.auth.user?.person" />
                    </div>
                </div>
                <div class=" flex ">
                    <SporkInput v-model="input"/>
                    <button @click="sendMessage">Submit</button>
                </div>
            </div>
        </div>
        <div class="bg-stone-800 h-full rounded-b divide-y divide-stone-400 dark:divide-stone-700 overflow-y-scroll">
            <div v-for="person in page.props.conversations.data" :key="person" class="flex justify-between items-center gap-x-6 py-2 px-4">
                <div class="flex items-center min-w-0 gap-x-2">
                    <img class="h-8 w-8 flex-none rounded-full bg-stone-50 dark:bg-stone-600" :src="person.imageUrl" alt="" />
                    <div class="min-w-0 flex-auto">
                        <button  @click="() => { showChat = true; active.chat = person; title = 'Chatting with ' + person.name}" class="text-sm text-left font-semibold leading-1 text-stone-900 dark:text-stone-50 w-full text-wrap">
                            {{ formatMatrixServer(person.name).name }}
                        </button>
                        <p class="truncate text-xs leading-1 text-stone-500 dark:text-stone-300">{{ formatMatrixServer(person.name).server }}</p>
                    </div>
                </div>
                <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                    <p class="text-sm leading-2 text-stone-900 dark:text-stone-50">{{ person.role }}</p>
                    <p v-if="person.lastSeen" class="mt-1 text-xs leading-5 text-stone-500 dark:text-stone-400">
                        Last seen <time :datetime="person.lastSeenDateTime">{{ person.lastSeen }}</time>
                    </p>
                    <div v-else class="mt-1 flex items-center gap-x-1.5">
                        <div class="flex-none rounded-full bg-emerald-500/20 p-1">
                            <div class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        </div>
                        <p class="text-xs leading-1 text-stone-500 dark:text-stone-400">Online</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import { XMarkIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/solid'
import { ChevronLeftIcon  } from '@heroicons/vue/20/solid'
import SporkInput from "@/Components/Spork/SporkInput.vue";
import Message from "@/Components/Messages/Message.vue";

const open = ref(false);

const page = usePage();

const formatMatrixServer = (name) => {
  const [username, server] = name.split(':')

  return {
    name: username,
    server
  };
}
const width = computed(() => {
  if (open.value) {
    return '325px'
  }

  return '40px';
});

const height = computed(() => {
  if (open.value) {
    return '450px'
  }

  return '40px';
})
const active = ref({
  chat: null,
  messages: []
});

const showChat = ref(false);
const title = ref('Chat');
const input = ref('');

const sendMessage =  async () => {
    const response = await router.post('/api/message/reply', {
        message: input.value,
        thread_id: active.value.chat.thread_id
    });

    setTimeout(() => {
        router.reload({
            only: ['conversations.threads'],
        })
    }, 250)
}
</script>
