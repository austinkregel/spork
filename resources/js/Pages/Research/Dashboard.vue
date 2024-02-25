<template>
  <AppLayout title="Dashboard">
    <div class="flex flex-wrap gap-4 m-4">
        <div class="w-full font-medium text-stone-600 dark:text-stone-300 uppercase ml-3">Recent</div>
        <div
            v-for="(topic, i) in research ?? []"
            class="w-64 p-3 border border-stone-200 dark:border-stone-600 rounded-lg bg-white dark:bg-stone-600"
            @contextmenu.prevent="(e) => openContextMenu(e, topic)"
            :key="'research-'+i"
            >
            <Link
                :href="'/-/research/'+ topic.id"
            >
                <div  class="font-medium truncate">{{ topic.name }}</div>
            <pre class=" h-48 shadow-inset overflow-hidden text-xs border-t py-2 my-2">{{ topic.notes }}</pre>
            </Link>
            <div class="text-stone-500 dark:text-stone-200 border-t mt-4 pt-2 flex items-center justify-between">
                <span>{{ date(topic.updated_at) }}</span>

                <button @click="() => deleteFeature(topic)">
                    <TrashIcon class="w-4 h-4 text-red-500" />
                </button>
            </div>
        </div>

        <div  v-if="openContext && openForTopic">
            <div @click="openContext = false" class="absolute inset-0 z-0 bg-stone-900/20 cusor-pointer"></div>

            <div class="absolute z-10 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-stone-600 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1"
            :style="'top: '+contextY+'px; left: '+contextX+'px;'"
            >
                <div class="py-1 text-sm flex flex-col" role="none">
                    <!-- Active: "bg-stone-100 text-stone-900", Not Active: "text-stone-700" -->
                    <Link :href="'/-/research/'+openForTopic.id" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <ArrowTopRightOnSquareIcon  class="w-4 h-4" />
                        Open
                    </Link>

                    <button @click="duplicateFeature" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <DocumentDuplicateIcon class="w-4 h-4" />
                        Duplicate
                    </button>

                    <button @click="renameFeature" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <PencilIcon class="w-4 h-4" />
                        Rename
                    </button>

                    <button @click="shareFeature" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <UserPlusIcon class="w-4 h-4" />
                        Share
                    </button>

                    <hr class="border-t border-stone-200 dark:border-stone-500" />

                    <button @click="deleteFeature" class="flex items-center gap-2 px-4 py-2 ">
                        <TrashIcon class="w-4 h-4 text-red-500" />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
  </AppLayout>
</template>

<script setup>
import {
    TrashIcon,
    ArrowTopRightOnSquareIcon ,
    DocumentDuplicateIcon,
    PencilIcon,
    UserPlusIcon
} from '@heroicons/vue/24/outline';
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue';

const { research } = defineProps({
  research: Array,
})

const openContext = ref(false);
const contextX = ref(0);
const contextY = ref(0);
const openForTopic = ref(null)

const shareFeature = () => {};
const deleteFeature = () => {};
const renameFeature = () => {};
const duplicateFeature = () => {};
const date = () => {}
const openContextMenu = (e, topic) => {
  openContext.value = true;
  contextX.value = e.clientX;
  contextY.value = e.clientY;
  openForTopic.value = topic;
};
const closeMenu = () => {
  openContext.value = false;
  openForTopic.value = null;
};

</script>
