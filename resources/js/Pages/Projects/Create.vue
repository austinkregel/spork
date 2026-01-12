<template>
    <AppLayout title="Dashboard">
        <div class="w-full border-b dark:border-stone-700 dark:bg-stone-950">
            <div  class="max-w-7xl mx-auto px-8 py-4 flex items-center gap-2 font-semibold text-2xl text-stone-800 dark:text-stone-200 leading-tight">
                <Link href="/-/projects" class="underline">
                    Projects
                </Link>
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true" />
                <span>Create</span>
            </div>
        </div>

        <div class="max-w-5xl w-full mx-auto py-8 px-4 flex flex-col gap-6">
            <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-lg font-semibold text-stone-800 dark:text-stone-100">
                            Create a Project
                        </div>
                        <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                            Pick a starting template and jump into the Project workspace.
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="hasAnyErrors" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-500/10 p-4">
                <div class="text-sm font-semibold text-red-700 dark:text-red-300">Fix the highlighted fields</div>
                <pre class="mt-2 text-xs text-red-700 dark:text-red-300 overflow-auto">{{ errors }}</pre>
            </div>

            <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                <div class="grid grid-cols-1 gap-4">
                    <SporkField v-model="name" label="Project name" placeholder="e.g. Automation Ops" />
                    <SporkField v-model="goal" label="Goal" type="textarea" placeholder="What outcome are you driving toward?" />
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                <div class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                    Template
                </div>
                <div class="mt-3">
                    <ProjectTemplatePicker v-model="templateKey" :templates="project_templates" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <SporkButton small secondary :disabled="saving" @click="router.visit('/-/projects')">
                    Cancel
                </SporkButton>
                <SporkButton small primary :disabled="saving || !name" @click="createProject">
                    {{ saving ? 'Creating…' : 'Create project' }}
                </SporkButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import {usePage, Link, router} from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ChevronRightIcon } from "@heroicons/vue/24/solid";
import { computed, ref } from "vue";
import SporkField from "@/Components/Spork/SporkField.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import ProjectTemplatePicker from "@/Components/Projects/ProjectTemplatePicker.vue";

const $page = usePage()

const { description, project_templates } = defineProps({
    description: Object,
    project_templates: Array,
})

const errors = ref(null);
const saving = ref(false);

const name = ref('');
const templateKey = ref(project_templates?.[0]?.key ?? 'custom');
const goal = ref('');

const hasAnyErrors = computed(() => !!errors.value && Object.keys(errors.value).length > 0);

const createProject = () => {
    saving.value = true;
    errors.value = null;

    router.post('/-/projects', {
        name: name.value,
        user_id: $page?.props?.auth?.user?.id,
        settings: {
            template: templateKey.value,
            goal: goal.value || null,
        },
    }, {
        preserveScroll: true,
        onError: (error) => {
            errors.value = Object.keys(error).reduce((acc, key) => {
                return {
                    ...acc,
                    [key]: [error[key]],
                };
            }, {});
        },
        onFinish: () => {
            saving.value = false;
        },
    });
};

</script>
