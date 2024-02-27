<template>
    <ContextMenu>
        <div
            class="flex flex-col gap-1 bg-stone-800 p-2 rounded-lg my-2 border-t-4"
            :class="[color]"
        >
            <button @click="() => { createTask = true;}" class="text-left">{{ task.name}}</button>
            <SporkChecklist v-model="task.checklist" :can-add-more="true"/>
            <div class="text-xs justify-end flex -mt-4">
                {{ checklistStatus }}
            </div>
        </div>

        <DialogModal
            :show="createTask"
            :closeable="true"
            @close="createTask = false"
        >
            <template #title>
                <div class="dark:text-stone-200 p-4">
                    Create a task
                </div>
            </template>
            <template #content>
                <div class="dark:text-stone-200 p-4 flex flex-col gap-4 border dark:border-stone-600 rounded-lg">
                    <SporkField v-model="form.name" label="Name" placeholder="hello there" />
                    <SporkField v-model="form.type" label="Type" />
                    <SporkField v-model="form.status" label="Status" />
                    <SporkField v-model="form.notes" label="Notes" type="textarea"/>
                    <SporkField v-model="form.start_date" label="Start Date" type="date"/>
                    <SporkChecklist v-model="form.checklist" label="Checklist"/>
                </div>
            </template>
            <template #footer>
                <div class="dark:text-stone-200 p-4 flex justify-between gap-4">
                    <spork-button @click="createTask = !createTask" small secondary>
                        Close
                    </spork-button>
                    <spork-button @click="updateTask(form); createTask = !createTask" small primary>
                        Save
                    </spork-button>
                </div>
            </template>
        </DialogModal>

        <template #items>
            <!-- Active: "bg-stone-100 text-stone-900", Not Active: "text-stone-700" -->
            <Link :href="'/-/research/'+task.id" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                <ArrowTopRightOnSquareIcon  class="w-4 h-4" />
                Open
            </Link>

            <button @click="() => {}" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                <UserPlusIcon class="w-4 h-4" />
                Share
            </button>

            <hr class="border-t border-stone-200 dark:border-stone-500" />

            <button @click="() => {}" class="flex items-center gap-2 px-4 py-2 ">
                <TrashIcon class="w-4 h-4 text-red-500" />
                Delete
            </button>
        </template>
    </ContextMenu>
</template>

<script setup>
import SporkChecklist from "@/Components/Spork/SporkChecklist.vue";
import { watch, computed, reactive, ref } from 'vue';
import {Link, router} from "@inertiajs/vue3";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import SporkField from "@/Components/Spork/SporkField.vue";
import DialogModal from "@/Components/DialogModal.vue";
import ContextMenu from "@/Components/ContextMenus/ContextMenu.vue";
import {
    ArrowTopRightOnSquareIcon,
    DocumentDuplicateIcon,
    PencilIcon, TrashIcon,
    UserPlusIcon
} from "@heroicons/vue/24/outline/index.js";

const { task } = defineProps({
    task: {
        type: Object,
        default: () => ({
            checklist: []
        })
    }
})
const form = reactive(task);
const openContext = ref(false);
const contextX = ref(0);
const contextY = ref(0);
const openForTopic = ref(null)

const createTask = ref(false);

const openContextMenu = (e,) => {
    openContext.value = true;
    contextX.value = e.clientX;
    contextY.value = e.clientY;
};
const closeMenu = () => {
    openContext.value = false;
    openForTopic.value = null;
};

watch(task, (newVal, oldValue) => {
    const task = Object.assign({}, newVal);

    axios.put('/api/crud/tasks/' + task.id, task)
        .then((response) => {

        })
});

const checklistStatus = computed(() => {
    if (! task?.checklist || task.checklist.length === 0) {
        return '';
    }
    const completed = task.checklist.filter((item) => item.checked).length;
    const total = task.checklist.length;
    return  completed + '/' + total ;
})
const status = computed(() => {
    return task.status
})
const color = computed(() => {
    if (! task?.checklist || task.checklist.length === 0) {
        return 'border-stone-950';
    }

    if (task.checklist.filter((item) => item.checked).length === task.checklist.length) {
        return 'border-green-500';
    }

    if (task.checklist.filter((item) => item.checked).length > 0) {
        return 'border-yellow-500';
    }

    switch(status.value) {
        case 'todo':
            return 'border-red-500';
        case 'in-progress':
            return 'border-yellow-500';
        case 'done':
            return 'border-green-500';
        default:
            return 'border-stone-800';
    }
})

const updateTask = async () => {
    await axios.put('/api/crud/tasks/' + form.id, {
        ...form,
    });
    router.reload({ })
}
</script>
