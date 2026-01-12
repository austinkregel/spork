<template>
    <AppLayout title="Project">
        <div class="w-full border-b dark:border-stone-700 dark:bg-stone-950">
            <div class="max-w-7xl mx-auto px-8 py-4 flex items-center gap-2 font-semibold text-2xl text-stone-800 dark:text-stone-200 leading-tight">
                <Link href="/-/projects" class="underline">Projects</Link>
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true" />
                {{ project.name }}
            </div>
        </div>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-6">
                <div v-if="goal" class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                    <div class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">Goal</div>
                    <div class="mt-1 text-stone-800 dark:text-stone-100">{{ goal }}</div>
                </div>

                <!-- Automations (hero) -->
                <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">Hero</div>
                            <div class="mt-1 text-xl font-semibold text-stone-900 dark:text-stone-100">Automations</div>
                            <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                                Run playbooks, monitor behavior, and iterate on steps.
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <SporkButton small secondary @click="openAttach('Attach automations', automationType)">Attach</SporkButton>
                            <Link :href="route('automation.automations.create')" class="px-3 py-2 text-sm rounded-md bg-indigo-500 dark:bg-indigo-600 text-white">
                                Create
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4 divide-y divide-stone-200 dark:divide-stone-700 border border-stone-200 dark:border-stone-800 rounded-lg">
                        <div v-if="project.automations?.length === 0" class="p-4 text-sm text-stone-500 dark:text-stone-400">
                            No automations attached yet.
                        </div>
                        <div v-for="a in project.automations" :key="a.id" class="p-4 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-medium text-stone-900 dark:text-stone-100 truncate">{{ a.name }}</div>
                                <div class="text-sm text-stone-600 dark:text-stone-300">
                                    Enabled: {{ a.enabled ? 'yes' : 'no' }} · Cron: {{ a.cron_expression ?? '—' }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <Link :href="route('automation.automations.show', a.id)" class="px-2 py-1.5 text-xs rounded-md border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-200">
                                    View
                                </Link>
                                <Link :href="route('automation.automations.edit', a.id)" class="px-2 py-1.5 text-xs rounded-md border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-200">
                                    Edit steps
                                </Link>
                                <Link :href="route('automation.automations.run-now', a.id)" method="post" as="button" class="px-2 py-1.5 text-xs rounded-md bg-indigo-500 dark:bg-indigo-600 text-white">
                                    Run now
                                </Link>
                                <button
                                    type="button"
                                    class="px-2 py-1.5 text-xs rounded-md text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900"
                                    @click="detachResource(automationType, a.id)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks -->
                <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xl font-semibold text-stone-900 dark:text-stone-100">Tasks</div>
                            <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">Keep a small queue of concrete next actions.</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <SporkButton small secondary @click="openAttach('Attach tasks', taskType)">Attach</SporkButton>
                            <SporkButton small primary @click="createTaskOpen = true">New task</SporkButton>
                        </div>
                    </div>

                    <div class="mt-4 divide-y divide-stone-200 dark:divide-stone-700 border border-stone-200 dark:border-stone-800 rounded-lg">
                        <div v-if="project.tasks?.length === 0" class="p-4 text-sm text-stone-500 dark:text-stone-400">
                            No tasks yet.
                        </div>
                        <div v-for="t in project.tasks" :key="t.id" class="p-4 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-medium text-stone-900 dark:text-stone-100 truncate">{{ t.name }}</div>
                                <div class="text-sm text-stone-600 dark:text-stone-300">
                                    {{ t.status ?? '—' }} · {{ t.type ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Research -->
                <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xl font-semibold text-stone-900 dark:text-stone-100">Research</div>
                            <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                                Capture sources and questions, then turn them into tasks or automation.
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <SporkButton small secondary @click="openAttach('Attach research', researchType)">Attach</SporkButton>
                            <SporkButton small secondary @click="openAttach('Attach pages', pageType)">Attach pages</SporkButton>
                            <SporkButton small secondary @click="openAttach('Attach RSS feeds', feedType)">Attach feeds</SporkButton>
                            <SporkButton small primary @click="createResearchOpen = true">New research</SporkButton>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="rounded-lg border border-stone-200 dark:border-stone-800 p-4">
                            <div class="text-sm font-semibold text-stone-900 dark:text-stone-100">Research</div>
                            <div class="mt-2 space-y-2">
                                <div v-if="project.research?.length === 0" class="text-sm text-stone-500 dark:text-stone-400">
                                    None yet.
                                </div>
                                <div v-for="r in project.research" :key="r.id" class="text-sm text-stone-800 dark:text-stone-100">
                                    {{ r.topic }}
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-stone-200 dark:border-stone-800 p-4">
                            <div class="text-sm font-semibold text-stone-900 dark:text-stone-100">Pages</div>
                            <div class="mt-2 space-y-2">
                                <div v-if="project.pages?.length === 0" class="text-sm text-stone-500 dark:text-stone-400">
                                    None yet.
                                </div>
                                <div v-for="p in project.pages" :key="p.id" class="text-sm text-stone-800 dark:text-stone-100">
                                    {{ p.title }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attach modal (fixed type per lane) -->
            <DialogModal :show="attachOpen" :closeable="true" @close="attachOpen = false">
                <template #title>
                    <div class="p-4 dark:text-stone-200">{{ attachTitle }}</div>
                </template>
                <template #content>
                    <div class="p-4 flex flex-col gap-3">
                        <SporkField v-model="attachQuery" label="Search" placeholder="Search…" />
                        <div v-if="attachLoading" class="text-sm text-stone-500 dark:text-stone-400">Searching…</div>
                        <div v-else class="divide-y divide-stone-200 dark:divide-stone-700 border border-stone-200 dark:border-stone-800 rounded-lg">
                            <div v-if="attachResults.length === 0" class="p-4 text-sm text-stone-500 dark:text-stone-400">No results.</div>
                            <div v-for="item in attachResults" :key="item.id" class="p-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm text-stone-900 dark:text-stone-100 truncate">{{ item.label }}</div>
                                    <div class="text-xs text-stone-500 dark:text-stone-400">#{{ item.id }}</div>
                                </div>
                                <SporkButton xsmall primary @click="attachResource(item.id)">Attach</SporkButton>
                            </div>
                        </div>
                    </div>
                </template>
                <template #footer>
                    <div class="p-4 flex justify-end gap-2">
                        <SporkButton small secondary @click="attachOpen = false">Close</SporkButton>
                    </div>
                </template>
            </DialogModal>

            <!-- Create task modal -->
            <DialogModal :show="createTaskOpen" :closeable="true" @close="createTaskOpen = false">
                <template #title>
                    <div class="p-4 dark:text-stone-200">New task</div>
                </template>
                <template #content>
                    <div class="p-4 flex flex-col gap-4">
                        <SporkField v-model="taskForm.name" label="Name" />
                        <SporkField v-model="taskForm.type" label="Type" />
                        <SporkField v-model="taskForm.status" label="Status" />
                    </div>
                </template>
                <template #footer>
                    <div class="p-4 flex justify-end gap-2">
                        <SporkButton small secondary @click="createTaskOpen = false">Cancel</SporkButton>
                        <SporkButton small primary @click="saveTask">Save</SporkButton>
                    </div>
                </template>
            </DialogModal>

            <!-- Create research modal -->
            <DialogModal :show="createResearchOpen" :closeable="true" @close="createResearchOpen = false">
                <template #title>
                    <div class="p-4 dark:text-stone-200">New research</div>
                </template>
                <template #content>
                    <div class="p-4 flex flex-col gap-4">
                        <SporkField v-model="researchForm.topic" label="Topic" />
                        <SporkField v-model="researchForm.notes" label="Notes" type="textarea" />
                    </div>
                </template>
                <template #footer>
                    <div class="p-4 flex justify-end gap-2">
                        <SporkButton small secondary @click="createResearchOpen = false">Cancel</SporkButton>
                        <SporkButton small primary @click="saveResearch">Save</SporkButton>
                    </div>
                </template>
            </DialogModal>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SporkButton from '@/Components/Spork/SporkButton.vue';
import SporkField from '@/Components/Spork/SporkField.vue';
import { ChevronRightIcon } from '@heroicons/vue/24/solid';

const $page = usePage();

const project = computed(() => $page.props.project);
const goal = computed(() => project.value?.settings?.goal ?? null);

const automationType = 'App\\\\Models\\\\Automation';
const taskType = 'App\\\\Models\\\\Task';
const researchType = 'App\\\\Models\\\\Research';
const pageType = 'App\\\\Models\\\\Page';
const feedType = 'App\\\\Models\\\\ExternalRssFeed';

const attachOpen = ref(false);
const attachTitle = ref('Attach');
const attachType = ref(null);
const attachQuery = ref('');
const attachResults = ref([]);
const attachLoading = ref(false);

const createTaskOpen = ref(false);
const taskForm = ref({ name: '', type: 'today', status: 'To Do' });

const createResearchOpen = ref(false);
const researchForm = ref({ topic: '', notes: '' });

const openAttach = (title, type) => {
    attachTitle.value = title;
    attachType.value = type;
    attachQuery.value = '';
    attachOpen.value = true;
};

let debounce = null;
watch([attachOpen, attachQuery, attachType], async () => {
    attachResults.value = [];
    if (!attachOpen.value || !attachType.value) {
        return;
    }

    if (debounce) {
        clearTimeout(debounce);
    }

    debounce = setTimeout(async () => {
        attachLoading.value = true;
        try {
            const { data } = await axios.get('/api/suggest/models', {
                params: {
                    type: attachType.value,
                    q: attachQuery.value || undefined,
                    limit: 20,
                },
            });
            attachResults.value = data?.data ?? [];
        } finally {
            attachLoading.value = false;
        }
    }, 200);
}, { immediate: true });

const attachResource = async (id) => {
    await axios.post(route('project.attach', [project.value.id]), {
        resource_type: attachType.value,
        resource_id: id,
    });
    router.reload({ only: ['project'] });
};

const detachResource = async (type, id) => {
    await axios.post(route('project.detach', [project.value.id]), {
        resource_type: type,
        resource_id: id,
    });
    router.reload({ only: ['project'] });
};

const saveTask = async () => {
    await axios.post(`/api/projects/${project.value.id}/tasks`, {
        ...taskForm.value,
    });
    createTaskOpen.value = false;
    taskForm.value = { name: '', type: 'today', status: 'To Do' };
    router.reload({ only: ['project'] });
};

const saveResearch = async () => {
    await axios.post(`/api/projects/${project.value.id}/research`, {
        ...researchForm.value,
    });
    createResearchOpen.value = false;
    researchForm.value = { topic: '', notes: '' };
    router.reload({ only: ['project'] });
};
</script>





