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
                jjkld
            </div>
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
