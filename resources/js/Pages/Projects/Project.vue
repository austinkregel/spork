<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class=" flex items-center gap-2 font-semibold text-2xl text-zinc-800 dark:text-zinc-200 leading-tight">
                <Link href="/projects" class="underline">
                    Projects
                </Link>
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-gray-400" aria-hidden="true" />
                {{ $page.props.project.name}}
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-4">
                <div class="mx-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-zinc-50">Last 30 days</h3>
                    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div v-for="item in $page.props.project_analytics" :key="item.name" class="overflow-hidden rounded-lg bg-white dark:bg-zinc-700 px-4 py-5 shadow sm:p-6">
                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-zinc-300">{{ item.name }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-zinc-50">{{ item.stat }}</dd>
                            <dd class="truncate text-sm font-medium text-gray-500 dark:text-zinc-300">Over {{ item.duration }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="border-t border-zinc-600"></div>
                <div class="mx-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-zinc-50 ">Servers</h3>

                    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 dark:text-zinc-50 text-zinc-900">
                        <div v-for="item in $page.props.project.servers" :key="item.name" class="overflow-hidden rounded-lg bg-white dark:bg-zinc-700 px-4 py-5 shadow sm:p-6">
                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-zinc-300">{{ item.name }}</dt>
                            <dd class="mt-1 font-semibold tracking-tight text-gray-900 dark:text-zinc-50">
                                <span class="text-3xl">{{ item.memory }}</span> MB
                                <span>/ {{ item.vcpu }} core</span>
                                <div class="my-2 text-monospace">{{ item.tags.map(tag => tag.name.en).join(', ') }}</div>
                                <div>
                                    <spork-button secondary @click="detach(item)">
                                        Delete
                                    </spork-button>
                                </div>
                            </dd>

                        </div>

                        <div v-if="$page.props.project.servers.length === 0" class="p-4 rounded bg-zinc-700 italic col-span-2">
                            There are no servers on this project
                        </div>

                    </dl>
                    <div class="text-slate-300 text-sm font-semibold mt-2 flex justify-between">
                        <button @click="() => {attachOpen = !attachOpen; fetchServers({ page: 1, limit: 100})}">
                            Attach a server
                        </button>
                        <Link href="">
                            View all servers
                        </Link>
                    </div>
                </div>
                <div class="border-t border-zinc-600"></div>
                <div class="mx-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-zinc-50">Domains</h3>
                    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 dark:text-zinc-50 text-zinc-900">
                        <div v-for="item in $page.props.project.domains" :key="item.name" class="overflow-hidden rounded-lg bg-white dark:bg-zinc-700 px-4 py-5 shadow sm:p-6">
                            <dd class="mt-1 font-semibold tracking-tight text-gray-900 dark:text-zinc-50">

                                <div class="flex flex-wrap items-center justify-between border border-zinc-700" v-if="item.domain_analytics.length > 0">
                                    <div class="flex flex-col p-2" v-for="analytic in item.domain_analytics">
                                        <div class="text-3xl">{{analytic.query_count}}</div>
                                        <div>Queries</div>
                                    </div>
                                    <div class="flex flex-col p-2 " v-for="analytic in item.domain_analytics">
                                        <div class="text-3xl">{{analytic.uncached_count}}</div>
                                        <div>Uncached</div>
                                    </div>
                                    <div class="flex flex-col p-2 " v-for="analytic in item.domain_analytics">
                                        <div class="text-3xl">{{analytic.stale_count}}</div>
                                        <div>Stale</div>
                                    </div>

                                </div>
                                <div class="flex flex-wrap justify-between">
                                    <div class="flex flex-col">
                                        <div class="text-2xl">
                                            <Link class="underline" :href="'/domains/'+item.id">{{ item.name }}</Link>
                                        </div>
                                        <div class="text-sm">{{ item.expires_at }}</div>
                                    </div>

                                    <spork-button secondary @click="detach(item)">
                                        Delete
                                    </spork-button>
                                </div>
                            </dd>
                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-zinc-300">{{ item.registered_at }}</dt>

                        </div>
                        <div v-if="$page.props.project.domains.length === 0" class="p-4 rounded bg-zinc-700 italic col-span-2">
                            There are no domains on this project
                        </div>
                    </dl>

                    <div class="text-slate-300 text-sm font-semibold mt-2 flex justify-between">
                        <button @click="() => {attachOpen = !attachOpen; fetchDomains({ page: 1, limit: 100})}">
                            Attach a domain
                        </button>
                        <Link href="">
                            View all domains
                        </Link>
                    </div>
                </div>
                <div class="border-t border-zinc-600"></div>

                <div class="mx-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-zinc-50">Credentials</h3>
                    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 dark:text-zinc-50 text-zinc-900">
                        <div v-for="item in $page.props.project.credentials" :key="item.name" class="overflow-hidden rounded-lg bg-white dark:bg-zinc-700 px-4 py-5 shadow sm:p-6">
                            <dd class="mt-1 font-semibold tracking-tight text-gray-900 dark:text-zinc-50">
                                <div class="flex flex-wrap justify-between">
                                    <div class="text-2xl">
                                        {{ item.name }}

                                        <div class="text-sm">{{ item.service }}</div>
                                    </div>

                                    <spork-button secondary @click="detach(item)">
                                        Delete
                                    </spork-button>
                                </div>
                            </dd>
                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-zinc-300">{{ item.expires_at }}</dt>

                        </div>
                        <div v-if="!$page.props.project.credentials?.length" class="p-4 rounded bg-zinc-700 italic col-span-2">
                            There are no credentials on this project
                        </div>
                    </dl>

                    <div class="text-slate-300 text-sm font-semibold mt-2 flex justify-between">
                        <button @click="() => {attachOpen = !attachOpen; fetchCredentials({ page: 1, limit: 100})}">
                            Attach a credential
                        </button>
                        <Link href="">
                            View all credentials
                        </Link>
                    </div>
                </div>
                <div class="border-t border-zinc-600"></div>
                <div class="mx-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-zinc-50">Pages</h3>
                    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 dark:text-zinc-50 text-zinc-900">
                        <div v-for="item in $page.props.project.pages" :key="item.name" class="overflow-hidden rounded-lg bg-white dark:bg-zinc-700 p-4 shadow sm:p-6">
                            <dt class="truncate flex flex-wrap items-center gap-1  font-medium text-gray-500 dark:text-zinc-300">
                                <CheckCircleIcon class="w-5 h-5 text-green-500" v-if="item.is_active" />
                                <ExclamationTriangleIcon class="w-5 h-5 text-yellow-500" v-else />
                                {{ item.title }}
                            </dt>
                            <dd class="mt-1 font-semibold tracking-tight text-gray-900 dark:text-zinc-50">
                                <span class="text-3xl">{{item.uri}}</span>
                            </dd>
                            <div class="flex items-center flex-wrap justify-between">
                                <div class="flex flex-wrap items-center gap-6">
                                    <div v-if="item.redirect" class="flex flex-wrap items-center gap-2 text-stone-300">
                                        {{ item.domain.name}}
                                        <ArrowLongRightIcon class="w-4 h-4 text-stone-100" />
                                        <!-- SSL  -->
                                        <LockClosedIcon class="w-4 h-4 text-green-500" />
                                        undefined.services
                                    </div>
                                    <div v-else class="flex flex-wrap items-center gap-2 text-stone-300">
                                        <LockClosedIcon class="w-4 h-4 text-green-500" />
                                        {{ item.domain.name}}
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="flex items-center gap-2 bg-stone-800 p-1 rounded">
                                        <!-- Page is currently published -->
                                        <ShieldCheckIcon v-if="item.published_at" class="w-5 h-5 text-emerald-500" />
                                        <!-- Page with a custom function  -->
                                        <CodeBracketIcon v-if="item.content" class=" w-5 h-5 text-stone-400" />
                                        <!-- Page with a redirect  -->
                                        <ForwardIcon v-if="item.redirect" class="w-5 h-5 text-blue-400" />
                                        <!-- Has a custom view -->
                                        <DocumentIcon v-if="item.view" class="w-5 h-5 text-stone-300" />
                                    </div>
                                    <button class="target:outline-none" @click="detach(item)">
                                        <TrashIcon class="w-5 h-5 text-white" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="$page.props.project.pages.length === 0" class="p-4 rounded bg-zinc-700 italic col-span-2">
                            There are no pages on this project
                        </div>
                    </dl>
                    <div class="text-slate-300 text-sm font-semibold mt-2 flex justify-between">
                        <div class="flex flex-wrap items-center gap-4">
                            <button @click="() => {attachOpen = !attachOpen; fetchPages({ page: 1, limit: 100})}">
                                Attach a Page
                            </button>
                            <Link href="/pages/create">
                                Create a Page
                            </Link>
                        </div>

                        <Link href="">
                            View all pages
                        </Link>
                    </div>
                </div>
            </div>
            <DialogModal :show="attachOpen" :closeable="true" @close="attachOpen = false" >
                <template #title>
                    <div class="dark:text-zinc-200 p-4">
                        Attach to project
                    </div>
                </template>
                <template #content>
                    <div class="max-h-72 overflow-y-scroll dark:text-zinc-200 p-4 flex flex-col gap-2 border dark:border-zinc-600 rounded-lg">
                        <button @click="allSelected = !allSelected" class="cursor-pointer flex flex-wrap items-center gap-2">
                            <input
                                class="dark:bg-zinc-700"
                                type="checkbox"
                                v-model="allSelected"
                            />
                            <span>
                                Select All
                            </span>
                        </button>

                        <div v-for="item in attach">
                            <label class="cursor-pointer flex flex-wrap items-center gap-2">
                                <input
                                    class="dark:bg-zinc-700"
                                    type="checkbox"
                                    v-model="resources"
                                    :value="item.id"
                                />
                                <span>
                                    {{ item.name ?? item.title}} ({{item?.server_id ?? item?.credential?.name ?? item?.credential_id ?? item.slug }})
                                </span>
                            </label>
                        </div>
                    </div>
                </template>
                <template #footer>
                    <div class="dark:text-zinc-200 p-4 flex justify-between">
                        <spork-button @click="attachOpen = !attachOpen" small secondary>
                            Close
                        </spork-button>
                        <spork-button @click="attachToProject(type); attachOpen = !attachOpen" small primary>
                            Attach
                        </spork-button>
                    </div>
                </template>
            </DialogModal>
        </div>
    </AppLayout>
</template>

<script>
import { ref } from 'vue';
import {Head, Link, router} from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CrudView from "@/Components/Spork/CrudView.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import {buildUrl} from "@kbco/query-builder";
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

export default {
    components: {
        SporkButton,
        DialogModal,
        Modal,
        CrudView,
        AppLayout,
        ChevronRightIcon,
        SporkInput,
        Link,
        CheckIcon,
        CheckCircleIcon,
        ShieldCheckIcon,
        TrashIcon,
        LockClosedIcon,
        CodeBracketIcon,
        ForwardIcon,
        DocumentIcon,
        ExclamationTriangleIcon,
        ArrowLongRightIcon,
    },
    setup() {
        return {
            console,
            allSelected: ref(false),
            createOpen: ref(false),
            form: ref(({
                name: '',
                settings: {},
            })),
            data: ref([]),
            pagination: ref({}),
            attach: ref([]),
            resources: ref([]),
            attachOpen: ref(false),
            type: 'App\\Models\\Server',
        }
    },
    watch: {
        date(to, from) {
            this.form.remind_at = dayjs(to).startOf('day').utc().format("YYYY-MM-DD HH:mm:ss")
        },
        allSelected(newValue, oldValue) {
            if (newValue) {
                this.resources = [... this.attach.map(a => a.id)];
            } else {
                this.resources = [];
            }
        }
    },
    methods: {
        hasErrors(error) {
            if (!this.form.errors) {
                return '';
            }

            return this.form.errors[error] ?? null;
        },
        dateFormat(contact) {
            return '<span class="text-zinc-900">' + contact.starts_at  + '  at </span>' +
                '<span class="text-zinc-800">' + dayjs(contact.last_occurrence || contact.remind_at).format('h:mma') + '</span>'
        },
        async save(form) {
            if (!form.id) {
                await axios.post('/api/projects', {
                    ...form,
                    team_id: this.$page.props.auth.user.current_team.id,
                });

                this.fetch({ page: 1, limit: 15,})
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await axios.delete('/api/projects/' + form.id);
        },
        async onExecute({ actionToRun, selectedItems}) {
            try {
                await this.$store.dispatch('executeAction', {
                    url: actionToRun.url,
                    data: {
                        selectedItems
                    },
                });

            } catch (e) {
                console.log(e.message, 'error');
            }
        },
        async fetch({ page, limit }) {
            console.log('attempting to fetch', { page, limit })
        },
        async fetchServers({ page, limit }) {

            const { data: { data, ...pagination} } = await axios.get(buildUrl(
                '/api/servers', {
                    page, limit,
                include:  ['tags']
                }
            ));
            this.type = 'App\\Models\\Server';

            this.attach = data;
            // Let's set a default server selection to be any server with the default tag.
            this.resources = data.filter(server => server?.tags?.map(tag => tag.name.en).includes('default'))?.map(server => server.id) ?? [];
        },
        async fetchDomains({ page, limit }) {
            const {data: {data, ...pagination}} = await axios.get(buildUrl(
                '/api/domains', {
                    page, limit,
                    action: 'pagination:100',
                    sort: 'name',
                    include: ['projects']
                }
            ));

            this.type = 'App\\Models\\Domain';
            this.attach = data.filter(domain => domain.projects.length === 0);
        },
        async fetchCredentials({ page, limit }) {
            const {data: {data, ...pagination}} = await axios.get(buildUrl(
                '/api/credentials', {
                    page, limit,
                    action: 'pagination:100',
                    sort: 'name',
                    include: []
                }
            ));

            this.type = 'App\\Models\\Credential';
            this.attach = data;
        },
        async fetchPages({ page, limit }) {
            const {data: {data, ...pagination}} = await axios.get(buildUrl(
                '/api/pages', {
                    page, limit,
                    action: 'pagination:100',
                    include: []
                }
            ));

            this.type = 'App\\Models\\Page';
            this.attach = data;
        },
        async attachToProject(type, model) {
            Promise.all(this.resources.map(async (modelId) => {
                await axios.post('/project/' + this.$page.props.project.id + '/attach', {
                    resource_type: type,
                    resource_id: modelId,
                })
            })).then(() => {
                router.reload({ only: ['project'] })

                this.fetch({ page: 1, limit: 100 });
            });
        },
        async detach(item) {
            await axios.post('/project/' + this.$page.props.project.id + '/detach', item.pivot);
            router.reload({ only: ['project'] })
        }
    },
    created() {
        this.fetch({ page: 1, limit: 15 })
    }

}
</script>

<style scoped>

</style>
