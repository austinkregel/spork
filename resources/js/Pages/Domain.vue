<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class=" flex items-center gap-2 font-semibold text-2xl text-stone-800 dark:text-stone-200 leading-tight">
                <Link href="/domains" class="underline" >
                    Domains
                </Link>
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true" />
                {{ domain.name }}
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto">
                <SporkTable
                    v-if="domain?.records"
                    header="Records"
                    description="All DNS records we know about, as told by the linked DNS provider. It may not reflect the actual value in a given host down stream"
                    :headers="headers"
                    :items="domain.records"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CrudView from "@/Components/Spork/CrudView.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import {buildUrl} from "@kbco/query-builder";
import {ChevronRightIcon} from "@heroicons/vue/24/solid/index.js";
import SporkTable from "@/Components/Spork/Molecules/SporkTable.vue";
export default {
    components: {
        SporkTable,
        ChevronRightIcon,
        CrudView,
        AppLayout,
        SporkInput,
        Link
    },
    props: ['domain'],
    setup() {
        return {
            createOpen: ref(false),
            form: ref(({
                name: '',
                settings: {},
            })),
            data: ref([]),
            pagination: ref({}),
            headers: ref([
                {
                    name: 'ID',
                    accessor: 'id',
                },
                {
                    name: 'Type',
                    accessor: 'type',
                },
                {
                    name: 'Name',
                    accessor: 'name'
                },
                {
                    name: 'TTL',
                    accessor: 'ttl',
                },
                {
                    name: 'Value',
                    accessor: 'value',
                }
            ]),
        }
    },
    watch: {
        date(to, from) {
            this.form.remind_at = dayjs(to).startOf('day').utc().format("YYYY-MM-DD HH:mm:ss")
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
            return '<span class="text-stone-900">' + contact.starts_at  + '  at </span>' +
                '<span class="text-stone-800">' + dayjs(contact.last_occurrence || contact.remind_at).format('h:mma') + '</span>'
        },
        async save(form) {
            if (!form.id) {
                await axios.post('/api/crud/domains', form);
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await axios.delete('/api/crud/domains/' + form.id);
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
        async fetch({ page, limit, ...args }) {
            const {data: {data, ...pagination}} = await axios.get(buildUrl(
                '/api/crud/domains', {
                    page, limit,
                    ...args,
                    include: []
                }
            ));

            this.data = data;
            this.pagination = pagination;
        }
    },

}
</script>

<style scoped>

</style>
