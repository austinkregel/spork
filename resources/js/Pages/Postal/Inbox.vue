<template>
    <AppLayout title="Dashboard">
        <!-- component -->
        <main class="flex flex-wrap w-full h-full min-h-screen justify text-white">
          <div class="p-4 flex flex-col rounded divide-stone-600 divide-y w-1/2 xl:w-1/4">
            <button @click="() => changeIframe(item)" v-for="item in page.props.messages" class="flex flex-col w-full px-4 py-2" :class="item.seen ? 'dark:bg-stone-800' : 'dark:bg-stone-700'">
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
          <div class="w-1/2 xl:w-3/4" v-if="openMail">
            <div class="p-4 border-b-4 border-blue-400" :class="spamTarget">
                <div class="text-2xl max-w-full">{{ openMail.subject }}</div>
                <div class="flex flex-wrap justify-between w-full pr-4 overflow-hidden">
                    <div class="flex flex-col text-sm">
                      <div class="truncate max-w-full overflow-ellipsis">From: {{ openMail.from.name }} {{ openMail.from.email }}</div>
                      <div class="truncate max-w-full overflow-ellipsis">To: {{ openMail.to.name }} {{ openMail.from.email }}</div>
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
            <iframe :src="'/-/inbox/' +openMail?.id " class="w-full h-full" />

          </div>
        </main>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
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

const openMail = ref(null);

const changeIframe = (item) => {
  openMail.value = item
};

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
</script>
