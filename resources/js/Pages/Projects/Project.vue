<template>
    <AppLayout title="Dashboard">
        <div class="w-full border-b dark:border-stone-700 dark:bg-stone-950">
            <div
                class="max-w-7xl mx-auto px-8 py-4 flex items-center gap-2 font-semibold text-2xl text-stone-800 dark:text-stone-200 leading-tight">
                <Link href="/-/projects" class="underline">
                    Projects
                </Link>
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true"/>
                {{ $page.props.project.name }}
            </div>
        </div>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-4">
                <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-lg font-semibold text-stone-800 dark:text-stone-100">
                                Resources
                            </div>
                            <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                                Attach existing resources to this project without copying data by hand.
                            </div>
                        </div>

                        <SporkButton small primary @click="openAttachModal([])">
                            Attach resources
                        </SporkButton>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button
                            v-for="group in resourceGroups"
                            :key="group.key"
                            type="button"
                            class="text-left rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950 p-4 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            @click="openAttachModal([group.key])"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold text-stone-800 dark:text-stone-100">
                                    {{ group.label }}
                                </div>
                                <div class="text-xs px-2 py-1 rounded-md bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200">
                                    {{ group.count }}
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <div
                                    v-for="pill in group.preview"
                                    :key="pill.key"
                                    class="text-xs px-2 py-1 rounded-md bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-200"
                                >
                                    {{ pill.label }}
                                </div>
                                <div v-if="group.preview.length === 0" class="text-xs text-stone-500 dark:text-stone-400">
                                    No items yet
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!--                <div class="uppercase tracking-wider">Todo tasks</div>-->
                <!--                <div class="grid grid-cols-3 w-full gap-4">-->
                <!--                    <div class="bg-stone-700 rounded-lg py-4">-->
                <!--                        <SmallTaskList :tasks="$page.props.daily_tasks" name="Daily Tasks" @open="() => { createTask = true; form.type = 'daily'}"/>-->
                <!--                    </div>-->
                <!--                    <div class="bg-stone-700 rounded-lg py-4">-->
                <!--                        <SmallTaskList :tasks="$page.props.today_tasks" name="Today's Tasks" @open="() => { createTask = true; form.type = 'today'}" />-->
                <!--                    </div>-->
                <!--                    <div class="bg-stone-700 rounded-lg py-4">-->
                <!--                        <SmallTaskList :tasks="$page.props.future_tasks" name="Future tasks" @open="() => { createTask = true; form.type = 'future'}"/>-->
                <!--                    </div>-->
                <!--                </div>-->

                <div class="grid grid-cols-1 gap-6 w-full">
                    <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
                        <div class="flex items-center justify-between">
                            <div class="text-lg font-semibold text-stone-800 dark:text-stone-100">
                                Infrastructure
                            </div>
                            <div class="text-sm text-stone-600 dark:text-stone-300">
                                Deployments and their attached servers/domains
                            </div>
                        </div>
                    </div>

                    <div v-for="deployment in project.deployments" :key="deployment" class="flex flex-col">
                        <div class="text-xl">
                            {{ deployment.name}}
                        </div>
                        <div v-if="deployment.domains.length > 0" class="flex flex-wrap gap-2">
                            <div v-for="domain in deployment.domains" class="text-xs border border-stone-400 dark:border-stone-600 px-1 py-0 rounded-lg">{{ domain.name }}</div>
                        </div>
                        <div class="py-2"></div>

                        <CheckboxList
                            md
                            :data="deployment.domains"
                            :data-for-attachment="attach"
                            :key-accessor="item => item.name"
                            header-text="Domains"
                            open-modal-text="Attach a domains"
                            modal-title="Attaching a domains"
                            no-data-text="There are no domains to attach"
                            @detach="detach"
                            @attach="(items) => items.map(item => attachToDeployment('App\\Models\\Domain', item, deployment))"
                            @open="() => fetchDomains({page: 1, limit: 100})"
                            @close="() => attach = []"
                        >
                            <template #preview="{ item }">
                                <div class="px-2 py-0.5 text-sm">
                                    {{ item.name }}
                                </div>
                            </template>

                            <template #buttons>
                                <Link href="/-/manage/domains">
                                    View All Domains
                                </Link>
                            </template>
                        </CheckboxList>
                        <div class="py-2"></div>
                        <CheckboxList
                            :data="deployment.servers"
                            :data-for-attachment="attach"
                            :key-accessor="item => item.name"
                            header-text="Servers"
                            open-modal-text="Attach a servers"
                            modal-title="Attaching a servers"
                            no-data-text="There are no servers to attach"
                            @detach="detach"
                            @attach="(items) => items.map(item => attachToDeployment('App\\Models\\Server', item, deployment))"
                            @open="() => fetchServers({page: 1, limit: 100})"
                            @close="() => attach = []"
                        >
                            <template #preview="{ item }">
                                <Server :server="item" />
                            </template>

                            <template #buttons>
                                <Link href="/-/manage/servers">
                                    View All servers
                                </Link>
                            </template>
                        </CheckboxList>

                    </div>

<!--                    <CrudView-->
<!--                        :data="$page.props.project.tasks"-->
<!--                        :key-accessor="item => item.id"-->
<!--                        :fields="[-->
<!--                            {label: 'Name', key: 'name'},-->
<!--                            {label: 'Type', key: 'type'},-->
<!--                            {label: 'Status', key: 'status'},-->
<!--                            {label: 'Notes', key: 'notes'},-->
<!--                            {label: 'Start Date', key: 'start_date'},-->
<!--                            {label: 'Checklist', key: 'checklist'},-->
<!--                        ]"-->
<!--                        :actions="[-->
<!--                            {label: 'Edit', icon: CodeBracketIcon, action: () => {}},-->
<!--                            {label: 'Delete', icon: TrashIcon, action: onDelete},-->
<!--                        ]"-->
<!--                    >-->
<!--                        <template #status="{item}">-->
<!--                            <Status :status="item.status"/>-->
<!--                        </template>-->
<!--                    </CrudView>-->
<!--                    <CheckboxList-->
<!--                        :data="$page.props.project.servers"-->
<!--                        :key-accessor="item => item.name"-->
<!--                        header-text="Infrastructure"-->
<!--                        open-modal-text="Attach a server"-->
<!--                        modal-title="Attaching a servers"-->
<!--                        no-data-text="There are no servers to attach"-->
<!--                        @detach="detach"-->
<!--                        @attach="attachToProject"-->
<!--                    >-->
<!--                        <template #preview>-->
<!--                            <div class="text-2xl">-->
<!--                                {{ item.name }}-->
<!--                            </div>-->
<!--                        </template>-->

<!--                        <template #buttons>-->
<!--                            <Link href="/-/manage/servers">-->
<!--                                View All Infrastructure-->
<!--                            </Link>-->
<!--                        </template>-->
<!--                    </CheckboxList>-->

<!--                    <CheckboxList-->
<!--                        :data="$page.props.project.domains"-->
<!--                        :key-accessor="item => item.name"-->
<!--                        header-text="Domains"-->
<!--                        open-modal-text="Attach a domains"-->
<!--                        modal-title="Attaching a domains"-->
<!--                        no-data-text="There are no domains to attach"-->
<!--                        @detach="detach"-->
<!--                        @attach="attachToProject"-->
<!--                    >-->
<!--                        <template #preview>-->
<!--                            <div class="text-2xl">-->
<!--                                {{ item.name }}-->
<!--                            </div>-->
<!--                        </template>-->

<!--                        <template #buttons>-->
<!--                            <Link href="/-/manage/domains">-->
<!--                                View All Domains-->
<!--                            </Link>-->
<!--                        </template>-->
<!--                    </CheckboxList>-->

<!--                    <CheckboxList-->
<!--                        :data="$page.props.project.pages"-->
<!--                        :key-accessor="item => item.name"-->
<!--                        header-text="Pages"-->
<!--                        open-modal-text="Attach a pages"-->
<!--                        modal-title="Attaching a pages"-->
<!--                        no-data-text="There are no pages to attach"-->
<!--                        @detach="detach"-->
<!--                        @attach="attachToProject"-->
<!--                    >-->
<!--                        <template #preview>-->
<!--                            <div class="flex flex-wrap items-center gap-6">-->
<!--                                <div v-if="item.redirect" class="flex flex-wrap items-center gap-2 text-stone-300">-->
<!--                                    {{ item.domain.name }}-->
<!--                                    <ArrowLongRightIcon class="w-4 h-4 text-stone-100"/>-->
<!--                                    &lt;!&ndash; SSL  &ndash;&gt;-->
<!--                                    <LockClosedIcon class="w-4 h-4 text-green-500"/>-->
<!--                                </div>-->
<!--                                <div v-else class="flex flex-wrap items-center gap-2 text-stone-300">-->
<!--                                    <LockClosedIcon class="w-4 h-4 text-green-500"/>-->
<!--                                    {{ item.domain.name }}-->
<!--                                </div>-->
<!--                            </div>-->
<!--                        </template>-->

<!--                        <template #buttons>-->
<!--                            <div>-->

<!--                                <Link href="/-/pages/create">-->
<!--                                    Create a Page-->
<!--                                </Link>-->
<!--                                <Link href="">-->
<!--                                    View all pages-->
<!--                                </Link>-->
<!--                            </div>-->
<!--                        </template>-->
<!--                    </CheckboxList>-->


<!--                    <CheckboxList-->
<!--                        :data="$page.props.project.research"-->
<!--                        :key-accessor="item => item.name"-->
<!--                        header-text="Research"-->
<!--                        open-modal-text="Attach a research"-->
<!--                        modal-title="Attaching a research"-->
<!--                        no-data-text="There are no research to attach"-->
<!--                        @detach="detach"-->
<!--                        @attach="attachToProject"-->
<!--                    >-->
<!--                        <template #preview>-->
<!--                            <div class="text-2xl">-->
<!--                                {{ item.name }}-->
<!--                            </div>-->
<!--                        </template>-->

<!--                        <template #buttons>-->
<!--                            <Link href="/-/research">-->
<!--                                Investigate & Research-->
<!--                            </Link>-->
<!--                        </template>-->
<!--                    </CheckboxList>-->
                </div>
            </div>

            <DialogModal :show="createTask" :closeable="true" @close="createTask = false">
                <template #title>
                    <div class="dark:text-stone-200 p-4">
                        Create a task
                    </div>
                </template>
                <template #content>
                    <div class="dark:text-stone-200 p-4 flex flex-col gap-4 border dark:border-stone-600 rounded-lg">
                        <SporkField v-model="form.name" label="Name" placeholder="hello there"/>
                        <SporkField v-model="form.type" label="Type"/>
                        <SporkField v-model="form.status" label="Status"/>
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
                        <spork-button @click="saveTask(form); createTask = !createTask" small primary>
                            Save
                        </spork-button>
                    </div>
                </template>
            </DialogModal>

            <DialogModal :show="attachOpen" :closeable="true" @close="attachOpen = false">
                <template #title>
                    <div class="dark:text-stone-200 p-4">
                        Attach resources
                    </div>
                </template>
                <template #content>
                    <div class="p-4">
                        <ProjectResourcePicker
                            :registry="$page.props.project_resource_registry"
                            :allowed-groups="attachAllowedGroups"
                            :selected="selectedResources"
                            @add="attachToProject"
                        />
                    </div>
                </template>
                <template #footer>
                    <div class="dark:text-stone-200 p-4 flex justify-end gap-2">
                        <SporkButton small secondary @click="attachOpen = false">
                            Close
                        </SporkButton>
                    </div>
                </template>
            </DialogModal>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CrudView from "@/Components/Spork/CrudView.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkField from '@/Components/Spork/SporkField.vue';
import Status from '@/Components/Status.vue'
import { buildUrl } from "@kbco/query-builder";
import {
    ShieldCheckIcon,
    ChevronRightIcon,
    CheckIcon,
    CheckCircleIcon,
    TrashIcon,
    LockClosedIcon,
    CodeBracketIcon,
    ForwardIcon,
    ExclamationTriangleIcon,
} from "@heroicons/vue/24/solid";

import {
    ArrowLongRightIcon,
    DocumentIcon,
} from "@heroicons/vue/20/solid";
import Modal from "@/Components/Modal.vue";
import DialogModal from "@/Components/DialogModal.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";
import SporkChecklist from "@/Components/Spork/SporkChecklist.vue";
import SmallTaskList from "@/Components/Spork/SmallTaskList.vue";
import CheckboxList from "@/Components/Spork/Atoms/CheckboxList.vue";
import Server from "@/Components/Spork/Molecules/Server.vue";
import ProjectResourcePicker from "@/Components/Projects/ProjectResourcePicker.vue";

const allSelected = ref(false);
const createOpen = ref(false);
const form = ref(({
    status: 'To Do',
}));
const $page = usePage();
const data = ref([]);
const pagination = ref({});
const attach = ref([]);
const resources = ref([]);
const attachOpen = ref(false);
const attachAllowedGroups = ref([]);
const createTask = ref(false);

const project = computed(() => $page.props.project);

const selectedResources = computed(() => {
    const selected = [];

    const add = (type, items, labelKey = 'name') => {
        (items ?? []).forEach((item) => {
            selected.push({
                resource_type: type,
                resource_id: item.id,
                label: item[labelKey] ?? item.id,
            });
        });
    };

    add('App\\\\Models\\\\Task', project.value?.tasks, 'name');
    add('App\\\\Models\\\\Deployment', project.value?.deployments, 'name');

    add('App\\\\Models\\\\Server', project.value?.servers, 'name');
    add('App\\\\Models\\\\Domain', project.value?.domains, 'name');
    add('App\\\\Models\\\\Credential', project.value?.credentials, 'name');

    add('App\\\\Models\\\\Research', project.value?.research, 'topic');
    add('App\\\\Models\\\\Page', project.value?.pages, 'title');

    add('App\\\\Models\\\\Finance\\\\Budget', project.value?.budgets, 'name');
    add('App\\\\Models\\\\Finance\\\\Account', project.value?.accounts, 'name');
    add('App\\\\Models\\\\Finance\\\\Transaction', project.value?.transactions, 'name');

    add('App\\\\Models\\\\ExternalRssFeed', project.value?.external_rss_feeds ?? project.value?.externalRssFeeds, 'name');
    add('App\\\\Models\\\\Person', project.value?.people, 'name');
    add('App\\\\Models\\\\Thread', project.value?.threads, 'id');
    add('App\\\\Models\\\\Automation', project.value?.automations, 'name');

    return selected;
});

const resourceGroups = computed(() => {
    const groups = $page.props?.project_resource_registry?.groups ?? {};
    const resources = $page.props?.project_resource_registry?.resources ?? [];

    const byGroup = Object.keys(groups).map((key) => {
        const allowedTypes = resources.filter((r) => r.group === key).map((r) => r.type);
        const items = selectedResources.value.filter((s) => allowedTypes.includes(s.resource_type));

        return {
            key,
            label: groups[key] ?? key,
            count: items.length,
            preview: items.slice(0, 3).map((i) => ({ key: `${i.resource_type}:${i.resource_id}`, label: i.label })),
        };
    });

    return byGroup;
});

const openAttachModal = (groups) => {
    attachAllowedGroups.value = groups ?? [];
    attachOpen.value = true;
};

const hasErrors = (error) => {
    if (!form.errors) {
        return '';
    }

    return form.errors[error] ?? null;
}
const onDelete = async (data) => {
    await axios.delete('/api/crud/projects/' + form.id);
}
const fetchServers = async ({page, limit}) => {
    const {data: {data, ...pagination}} = await axios.get(buildUrl(
        '/api/crud/servers', {
            page, limit,
            include: ['tags']
        }
    ));
    attach.value = data;
    // Let's set a default server selection to be any server with the default tag.
    resources.value = data.filter(server => server?.tags?.map(tag => tag.name.en).includes('default'))?.map(server => server.id) ?? [];
}
const fetchDomains = async ({page, limit}) => {
    const {data: {data, ...pagination}} = await axios.get(buildUrl(
        '/api/crud/domains', {
            page, limit,
            action: 'pagination:100',
            sort: 'name',
            include: ['projects']
        }
    ));

    attach.value = data.filter(domain => domain.projects.length === 0);
}
const fetchCredentials = async ({page, limit}) => {
    const {data: {data, ...pagination}} = await axios.get(buildUrl(
        '/api/crud/credentials', {
            page, limit,
            action: 'pagination:100',
            sort: 'name',
            include: []
        }
    ));

    attach.value = data;
}
const fetchResearch = async ({page, limit}) => {
    const {data: {data, ...pagination}} = await axios.get(buildUrl(
        '/api/crud/research', {
            page, limit,
            action: 'pagination:100',
            include: []
        }
    ));

    attach.value = data;
}
const fetchPages = async ({page, limit}) => {
    const {data: {data, ...pagination}} = await axios.get(buildUrl(
        '/api/crud/pages', {
            page, limit,
            action: 'pagination:100',
            include: []
        }
    ));

    attach.value = data;
}
const attachToDeployment = async (type, model, deployment) => {
    await axios.post(
        route('deployment.attach', [deployment.id]), {
            resource_type: type,
            resource_id: model,
        }
    )
    router.reload({only: [
            'project',
        'deployments.domains',
        'deployments.servers',
        'deployments.domain',
        'deployments.server',
    ]})
};

const detach = async (item) => {
    await axios.post(route('project.detach', [$page.props.project.id]), item.pivot);
    router.reload({only: ['project']})
}
const saveTask = async (form) => {
    await axios.post('/api/projects/' + $page.props.project.id + '/tasks', {
        ...form,
    });
    createTask.value = false;

    router.reload({})
}

const attachToProject = async (item) => {
    await axios.post(route('project.attach', [$page.props.project.id]), {
        resource_type: item.resource_type,
        resource_id: item.resource_id,
    });

    router.reload({ only: ['project'] });
};

</script>

<style scoped>

</style>
