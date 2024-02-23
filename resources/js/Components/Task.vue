<template>
    <div>
        <div class="flex flex-col gap-1 bg-stone-800 p-2 rounded-lg my-2 border-t-4" :class="[color]">
            <button @click="() => { createTask = true;}" class="text-left">{{ task.name}}</button>
            <SporkChecklist v-model="task.checklist" :can-add-more="true"/>
            <div class="text-xs justify-end flex -mt-4">
                {{ checklistStatus }}
            </div>
        </div>



        <DialogModal :show="createTask" :closeable="true" @close="createTask = false" >
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
    </div>
</template>

<script setup>
import SporkChecklist from "@/Components/Spork/SporkChecklist.vue";
import {watch, computed, reactive, ref} from 'vue';
import {router} from "@inertiajs/vue3";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import SporkField from "@/Components/Spork/SporkField.vue";
import DialogModal from "@/Components/DialogModal.vue";

const { task } = defineProps({
    task: {
        type: Object,
        default: () => ({})
    }
})
const form = reactive(task);

const createTask = ref(false);

watch(task, (newVal, oldValue) => {
    const task = Object.assign({}, newVal);

    axios.put('/api/crud/tasks/' + task.id, task)
        .then((response) => {

        })
});

const checklistStatus = computed(() => {
    const completed = task.checklist.filter((item) => item.checked).length;
    const total = task.checklist.length;
    return  completed + '/' + total ;
})
const status = computed(() => {
    return task.status
})
const color = computed(() => {
    if (task.checklist.length === 0) {
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
