<template>
    <div class="space-y-2">
        <div class="px-4 text-sm font-semibold text-stone-700 dark:text-stone-200">{{ name }}</div>
        <div v-if="tasks.length > 0" class="flex flex-col gap-2 px-2 py-2">
            <Task v-for="task in tasks" :key="task.id+task.name" :task="task" />
        </div>
        <div v-else class="px-4 py-4 text-center text-sm italic text-stone-500 dark:text-stone-400">No tasks</div>

        <div class="px-4">
            <GlassButton variant="secondary" size="sm" @click="$emit('open')">Add Task</GlassButton>
        </div>
    </div>
</template>

<script setup>
import GlassButton from "@/Components/Glass/GlassButton.vue";
import Task from "@/Components/Task.vue";

defineProps({
    name: { type: String, default: null },
    tasks: { type: Array, default: () => [] },
});

defineEmits(['open']);
</script>
