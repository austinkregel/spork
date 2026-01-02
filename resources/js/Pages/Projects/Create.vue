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
            <div
                class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-lg font-semibold text-stone-800 dark:text-stone-100">
                            Project Builder
                        </div>
                        <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                            Choose a template, then attach resources with search. We’ll auto-store the template in settings.
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div
                            class="text-xs px-2 py-1 rounded-md bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200"
                        >
                            Step {{ step }} / 3
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="hasAnyErrors" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-500/10 p-4">
                <div class="text-sm font-semibold text-red-700 dark:text-red-300">Fix the highlighted fields</div>
                <pre class="mt-2 text-xs text-red-700 dark:text-red-300 overflow-auto">{{ errors }}</pre>
            </div>

            <!-- Step 1 -->
            <div v-if="step === 1" class="flex flex-col gap-6">
                <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                    <div class="grid grid-cols-1 gap-4">
                        <SporkField v-model="name" label="Project name" placeholder="e.g. Spork Infrastructure" />
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
            </div>

            <!-- Step 2 -->
            <div v-if="step === 2" class="flex flex-col gap-6">
                <div
                    v-for="section in selectedTemplate?.sections ?? []"
                    :key="section.key"
                    class="flex flex-col gap-3"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                                {{ section.label }}
                            </div>
                            <div v-if="section.description" class="text-sm text-stone-600 dark:text-stone-300">
                                {{ section.description }}
                            </div>
                        </div>

                        <div class="text-xs text-stone-500 dark:text-stone-400">
                            {{ (section.allowed_resource_groups?.length ?? 0) ? 'Filtered' : 'All' }}
                        </div>
                    </div>

                    <ProjectResourcePicker
                        :registry="project_resource_registry"
                        :allowed-groups="section.allowed_resource_groups ?? []"
                        :selected="attachments"
                        @add="onAddAttachment"
                        @remove="onRemoveAttachment"
                    />
                </div>
            </div>

            <!-- Step 3 -->
            <div v-if="step === 3" class="flex flex-col gap-6">
                <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                    <div class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                        Review
                    </div>
                    <div class="mt-2 text-sm text-stone-600 dark:text-stone-300">
                        We’ll create the project and attach {{ attachments.length }} resource(s).
                    </div>

                    <div class="mt-4">
                        <div v-if="attachments.length === 0" class="text-sm text-stone-500 dark:text-stone-400">
                            No resources selected.
                        </div>
                        <div v-else class="flex flex-col gap-2">
                            <div
                                v-for="item in attachments"
                                :key="item.resource_type + ':' + item.resource_id"
                                class="flex items-center justify-between gap-3 rounded-md border border-stone-200 dark:border-stone-800 px-3 py-2"
                            >
                                <div class="min-w-0">
                                    <div class="text-sm text-stone-800 dark:text-stone-100 truncate">
                                        {{ item.label ?? item.resource_id }}
                                    </div>
                                    <div class="text-xs text-stone-500 dark:text-stone-400">
                                        {{ item.resource_type }} #{{ item.resource_id }}
                                    </div>
                                </div>

                                <SporkButton xsmall secondary @click="onRemoveAttachment(item)">
                                    Remove
                                </SporkButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <SporkButton small secondary :disabled="step === 1 || saving" @click="prevStep">
                    Back
                </SporkButton>

                <div class="flex items-center gap-2">
                    <SporkButton
                        v-if="step < 3"
                        small
                        primary
                        :disabled="!canContinue || saving"
                        @click="nextStep"
                    >
                        Continue
                    </SporkButton>
                    <SporkButton
                        v-else
                        small
                        primary
                        :disabled="saving || !name"
                        @click="createProject"
                    >
                        {{ saving ? 'Creating…' : 'Create project' }}
                    </SporkButton>
                </div>
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
import ProjectResourcePicker from "@/Components/Projects/ProjectResourcePicker.vue";

const $page = usePage()

const { description, project_templates, project_resource_registry } = defineProps({
    description: Object,
    project_templates: Array,
    project_resource_registry: Object,
})

const errors = ref(null);
const step = ref(1);
const saving = ref(false);

const name = ref('');
const templateKey = ref(project_templates?.[0]?.key ?? 'custom');
const attachments = ref([]);

const selectedTemplate = computed(() => (project_templates ?? []).find(t => t.key === templateKey.value) ?? null);
const hasAnyErrors = computed(() => !!errors.value && Object.keys(errors.value).length > 0);
const canContinue = computed(() => {
    if (step.value === 1) {
        return !!name.value && !!templateKey.value;
    }
    if (step.value === 2) {
        return true;
    }
    return true;
});

const nextStep = () => {
    if (step.value < 3) {
        step.value += 1;
    }
};

const prevStep = () => {
    if (step.value > 1) {
        step.value -= 1;
    }
};

const onAddAttachment = (item) => {
    attachments.value = [
        ...attachments.value,
        item,
    ];
};

const onRemoveAttachment = (item) => {
    attachments.value = attachments.value.filter((i) => !(i.resource_type === item.resource_type && i.resource_id === item.resource_id));
};

const createProject = () => {
    saving.value = true;
    errors.value = null;

    router.post('/-/projects', {
        name: name.value,
        user_id: $page?.props?.auth?.user?.id,
        settings: {
            template: templateKey.value,
        },
        attachments: attachments.value.map((a) => ({
            resource_type: a.resource_type,
            resource_id: a.resource_id,
        })),
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
