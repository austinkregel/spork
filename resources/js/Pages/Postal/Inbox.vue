<template>
    <AppLayout title="Postal/Inbox">
        <!-- component -->
        <main class="flex flex-wrap w-full h-full overflow-hidden justify text-white" style="max-height: calc(100vh - 65px);">
          <div class="flex flex-col rounded divide-stone-600 divide-y w-1/2 xl:w-1/4 overflow-y-scroll" style="max-height: calc(100vh - 65px);">
            <div v-if="loading" class=" px-4 py-2">
              Loading
            </div>

            <button
                @click="() => changeIframe(item)" v-for="item in page.props.messages"
                :key="item"
                @contextmenu.prevent="(e) => openContextMenu(e, item)"
                class="flex flex-col w-full px-4 py-2"
                :class="item.seen ? 'dark:bg-stone-900' : 'dark:bg-stone-700'"
            >
                <div class="flex w-full justify-between text-left relative max-w-full overflow-hidden">
                    <div class="">{{ item.from.name ?? item.from.email }}</div>
                    <div class="text-sm">{{ item.human_date }}</div>
                </div>
                <div class="w-full text-left text-sm">{{ item.subject }}</div>
                 <div class="flex gap-2 items-center">
                      <FireIcon v-if="item.spam > 1" class="w-5 h-5" />
                      <ArrowUturnLeftIcon v-if="item.answered" class="w-5 h-5"/>
                      <TrashIcon v-if="item.deleted" class="w-5 h-5"/>
                      <PencilSquareIcon v-if="item.draft" class="w-5 h-5"/>
                      <EyeSlashIcon v-if="!item.seen" class="w-5 h-5"/>
               </div>
            </button>
          </div>
          <div class="w-1/2 xl:w-3/4 overflow-y-scroll border-l border-stone-600" v-if="openMail">
            <div class="p-4 border-b-4 border-blue-400" :class="spamTarget">
                <div class="text-2xl max-w-full">{{ openMail.subject }}</div>
                <div class="flex flex-wrap justify-between w-full pr-4 overflow-hidden">
                    <div class="flex flex-col text-sm gap-0.5">
                      <div class="truncate max-w-full overflow-ellipsis">From: {{ openMail.from.name }} <span class="bg-black/30 p-1 rounded-lg">{{ openMail.from.email }}</span></div>
                      <div class="truncate max-w-full overflow-ellipsis">To: {{ openMail.to.name }} <span class="bg-black/30 p-1 rounded-lg">{{ openMail.to?.original }}</span></div>
                      <div class="truncate max-w-full overflow-ellipsis" v-if="openMail['reply-to']">Reply-To: {{ openMail['reply-to'].name }} {{ openMail['reply-to'].email }}</div>
                    </div>
                    <div class="text-green-50 pt-2 text-xs ">{{ openMail.human_date }}</div>
                </div>
                <div class="flex gap-2 items-center">
                  <FireIcon v-if="openMail.spam > 1" class="w-5 h-5" />
                  <ArrowUturnLeftIcon v-if="openMail.answered" class="w-5 h-5"/>
                  <TrashIcon v-if="openMail.deleted" class="w-5 h-5"/>
                  <PencilSquareIcon v-if="openMail.draft" class="w-5 h-5"/>
                  <EyeSlashIcon v-if="!openMail.seen" class="w-5 h-5"/>
               </div>
            </div>
            <iframe :src="'/-/inbox/' +openMail?.id " class="w-full  overflow-hidden" style="height: calc(100vh - 192px);" />

          </div>
          <div v-show="openContext && openForMail" >
            <div @click="openContext = false" class="absolute inset-0 z-0 bg-gray-900/20 cusor-pointer"></div>

            <div class="absolute transition-all z-10 mt-2 w-56 overflow-hidden rounded-md shadow-lg bg-white dark:bg-slate-600 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1"
                 :style="styleForContext"
                 id="contextRef"
            >
              <div class="flex flex-col" role="none" >
                <div class="flex flex-col">
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => mark_as_read()">Mark As Read</button>
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => mark_move_spam()">Mark & Move Spam</button>
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => mark_as_unread()">Mark As Unread</button>
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => reply()">Reply</button>
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => reply_all()">Reply All</button>
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => forward()">Forward</button>
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => apply_tag()">Apply Tag</button>
                  <button class="hover:bg-slate-500 text-left px-4 py-2" @click="() => delete_mail()">Delete</button>
                </div>
              </div>
            </div>
          </div>

        </main>
    </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    FireIcon,
    EyeSlashIcon,
    ArrowUturnLeftIcon,
    TrashIcon,
    PencilSquareIcon,
} from '@heroicons/vue/24/outline';

import AppLayout from '@/Layouts/AppLayout.vue';
import CrudView from "@/Components/Spork/CrudView.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import {buildUrl} from "@kbco/query-builder";

const page = usePage();

const base64decode = (dir) => atob(dir);
const styleForContext = ref('')

const openMail = ref(null);
const openForMail = ref(null);
const contextX = ref(null);
const contextY = ref(null);
const openContext = ref(false);
const contextRef = ref(null);
const loading = ref(true);
const changeIframe = (item) => {
  openMail.value = item
};

onMounted(() => {
  loading.value = false;
})

const closeContextMenu = () => {
  openForMail.value = null;
  openContext.value = false;
}

const openContextMenu = (event, item) => {
  openForMail.value = item;
  openContext.value = true;
  contextX.value = event.clientX;
  contextY.value = event.clientY;

  setTimeout(() => {
    const docRef = document.getElementById('contextRef');

    if ((contextY.value + docRef.clientHeight) < window.innerHeight) {
      styleForContext.value = 'top:'+contextY.value+'px;left: ' + contextX.value+'px;';
    } else {
      styleForContext.value = 'top:'+(contextY.value - docRef.clientHeight)+'px;left: ' + contextX.value+'px;';
    }
  }, 1)
}
const spamTarget = computed(() => {
    if (!openMail.value) {
        return '';
    }

    if (openMail.value.spam >= 10) {
        return 'bg-red-900'
    }
    if (openMail.value.spam >= 5) {
        return 'bg-amber-900'
    }

    return 'bg-green-900'
})

const triggerMailLoading = async () => {
  closeContextMenu();
  router.reload({
    only: ['messages']
  })
  loading.value = false;
}

const mark_as_read = async () => {
  loading.value = true;
  await axios.post('/api/mail/mark-as-read', {
    id: openForMail.value.id
  });

  await triggerMailLoading();
}
const mark_move_spam = async () => {
  loading.value = true;
  await axios.post('/api/mail/mark-as-spam', {
    id: openForMail. asyncvalue.id
  });
  await triggerMailLoading();
}
const mark_as_unread = async () => {
  loading.value = true;
  await axios.post('/api/mail/mark-as-unread', openForMail.value);
  await triggerMailLoading();
}
const reply = async () => {
  loading.value = true;
  await triggerMailLoading();
}
const reply_all = async () => {
  loading.value = true;
  closeContextMenu();
  await triggerMailLoading();

}
const forward = async () => {
  loading.value = true;
  closeContextMenu();
  await triggerMailLoading();

}
const apply_tag = async () => {
  loading.value = true;
  closeContextMenu();
  await triggerMailLoading();

}
const delete_mail = async () => {
  loading.value = true;
  closeContextMenu();
  await triggerMailLoading();

}
</script>
