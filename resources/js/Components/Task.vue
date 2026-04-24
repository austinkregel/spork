<template>
    <ContextMenu>
        <div
            class="my-2 flex flex-col gap-1 rounded-lg border-t-4 bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass border-x border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] p-2"
            :class="[color]"
        >
            <button type="button" class="text-left text-sm font-medium text-stone-900 dark:text-stone-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm" @click="createTask = true">{{ task.name }}</button>
            <SporkChecklist v-model="task.checklist" :can-add-more="true" />
            <div class="-mt-4 flex justify-end text-xs text-stone-500 dark:text-stone-400">
                {{ checklistStatus }}
            </div>
        </div>

        <GlassModal :open="createTask" title="Edit task" size="md" @close="createTask = false">
            <div class="space-y-4">
                <GlassField label="Name">
                    <GlassInput v-model="form.name" placeholder="hello there" />
                </GlassField>
                <GlassField label="Type">
                    <GlassInput v-model="form.type" />
                </GlassField>
                <GlassField label="Status">
                    <GlassInput v-model="form.status" />
                </GlassField>
                <GlassField label="Notes">
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100"
                    />
                </GlassField>
                <GlassField label="Start Date">
                    <GlassInput v-model="form.start_date" type="date" />
                </GlassField>
                <SporkChecklist v-model="form.checklist" label="Checklist" />
            </div>
            <template #footer>
                <GlassButton variant="secondary" size="sm" @click="createTask = false">Close</GlassButton>
                <GlassButton size="sm" @click="updateTask(form); createTask = false">Save</GlassButton>
            </template>
        </GlassModal>

        <template #items="{ close }">
            <ContextMenuItem @click="createTask = true">
                <DynamicIcon icon-name="ArrowTopRightOnSquareIcon" class="h-4 w-4" />
                Open
            </ContextMenuItem>
            <ContextMenuItem
                v-if="task.status !== 'In Progress'"
                @click="task.status = 'In Progress'; close()"
            >
                <DynamicIcon icon-name="BriefcaseIcon" class="h-4 w-4" />
                Start Work
            </ContextMenuItem>
            <ContextMenuItem v-if="task.status !== 'To Do'" @click="task.status = 'To Do'; close()">
                <DynamicIcon icon-name="ClockIcon" class="h-4 w-4" />
                Back to the start
            </ContextMenuItem>
            <ContextMenuItem v-if="task.status !== 'Done'" @click="task.status = 'Done'; close()">
                <DynamicIcon icon-name="CheckCircleIcon" class="h-4 w-4" />
                Mark as Done
            </ContextMenuItem>

            <hr class="border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]">

            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left" @click="deleteTask">
                <TrashIcon class="h-4 w-4 text-red-500" aria-hidden="true" />
                Delete
            </button>
        </template>
    </ContextMenu>
</template>

<script setup>
import SporkChecklist from "@/Components/Spork/SporkChecklist.vue";
import { watch, computed, reactive, ref } from 'vue';
import { router } from "@inertiajs/vue3";
import GlassModal from "@/Components/Glass/GlassModal.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassField from "@/Components/Glass/GlassField.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import ContextMenu from "@/Components/ContextMenus/ContextMenu.vue";
import { TrashIcon } from "@heroicons/vue/24/outline";
import DynamicIcon from "@/Components/DynamicIcon.vue";
import ContextMenuItem from "@/Components/ContextMenus/ContextMenuButton.vue";
import axios from 'axios';

const { task } = defineProps({
    task: {
        type: Object,
        default: () => ({ checklist: [] }),
    },
});

const form = reactive(task);
const createTask = ref(false);

watch(() => task, (newVal) => {
    const updated = Object.assign({}, newVal);
    axios.put('/api/crud/tasks/' + updated.id, updated)
        .then(() => {
            createTask.value = false;
            router.reload({ only: ['project', 'daily_tasks', 'today_tasks', 'future_tasks'] });
        });
});

const checklistStatus = computed(() => {
    if (!task?.checklist || task.checklist.length === 0) return '';
    const completed = task.checklist.filter((item) => item.checked).length;
    return completed + '/' + task.checklist.length;
});

const status = computed(() => task.status);
const color = computed(() => {
    switch (status.value) {
        case 'todo':
        case 'To Do':
            return 'border-red-500';
        case 'in-progress':
        case 'in progress':
        case 'In Progress':
            return 'border-yellow-500';
        case 'done':
        case 'Done':
            return 'border-green-500';
        default:
            if (!task?.checklist || task.checklist.length === 0) return 'border-stone-300 dark:border-stone-700';
            if (task.checklist.filter((item) => item.checked).length === task.checklist.length) return 'border-green-500';
            if (task.checklist.filter((item) => item.checked).length > 0) return 'border-yellow-500';
            return 'border-stone-300 dark:border-stone-700';
    }
});

const updateTask = async () => {
    await axios.put('/api/crud/tasks/' + form.id, { ...form });
    router.reload({ only: ['project', 'daily_tasks', 'today_tasks', 'future_tasks'] });
};

const deleteTask = async () => {
    await axios.delete('/api/crud/tasks/' + task.id);
    router.reload({ only: ['project', 'daily_tasks', 'today_tasks', 'future_tasks'] });
};
</script>
