<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                Domains
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- We need to figure out a better way to get the crud actions. -->
                    <crud-view
                        :form="form"
                        singular="Domain"
                        @destroy="onDelete"
                        @index="({ page, limit, ...args}) => fetch({ page, limit, ...args })"
                        @execute="onExecute"
                        :data="data"
                        :paginator="pagination"
                    >
                        <template #modal-title>
                            <div>
                                Create a domain
                            </div>
                        </template>
                        <template v-slot:data="{ data }">
                            <div class="flex flex-col">
                                <div class="text-lg text-left">
                                    <Link :href="'/domains/'+ data.id" class="underline">
                                        {{ data.name }}
                                    </Link>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div class="text-xs dark:text-stone-300">
                                        Expires At: {{ data.expires_at.split(' ')[0] }}
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #no-data>No domains</template>

                        <template #form>
                            <div>
                                <div class="grid grid-cols-6 gap-4 mt-2">
                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">Name</label>
                                        <spork-input v-model="form.name" type="text" name="name" id="name" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">Type</label>
                                        <spork-input v-model="form.settings.type" type="text" name="name" id="name" />
                                    </div>

                                </div>
                            </div>
                        </template>

                    </crud-view>
                </div>
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

export default {
    components: {
        Link,
        CrudView,
        AppLayout,
        SporkInput
    },
    props: ['domains'],
    setup(props) {
        let { data, ...pagination } = props.domains;
        return {
            createOpen: ref(false),
            form: ref(({
                name: '',
                settings: {},
            })),
            data: ref(data ?? []),
            pagination: ref(pagination ?? {}),

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
            return '<span class="text-zinc-900">' + contact.starts_at  + '  at </span>' +
                '<span class="text-zinc-800">' + dayjs(contact.last_occurrence || contact.remind_at).format('h:mma') + '</span>'
        },
        async save(form) {
            if (!form.id) {
                await axios.post('/api/domains', form);
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await axios.delete('/api/domains/' + form.id);
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
            const { data: { data, ...pagination} } = await axios.get(buildUrl(
                '/api/domains', {
                    page, limit,
                    ...args,
                    include: [],
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
