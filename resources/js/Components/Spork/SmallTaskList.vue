<template>
    <div>
        <div class="mx-4">{{ name }}</div>
        <div v-if="tasks.length > 0" class="py-2 mx-2  flex-col gap-4">
            <Task v-for="task in tasks" :key="task.id+task.name" :task="task"></Task>
        </div>
        <div v-else>
            <div class="text-center italic py-4 ">No tasks</div>
        </div>

        <div class="mx-4">
            <SporkButton xsmall secondary @click="() => $emit('open')">Add Task</SporkButton>
        </div>
    </div>
</template>

<script setup>
import SporkChecklist from "@/Components/Spork/SporkChecklist.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import { watch } from 'vue';
import Task from "@/Components/Task.vue";

const { tasks, name } = defineProps({
    name: {
        type: String,
        default: null
    },
    tasks: {
        type: Array,
        default: () => []
    }
})

watch(tasks, (newVal, oldValue) => {

})
</script>
