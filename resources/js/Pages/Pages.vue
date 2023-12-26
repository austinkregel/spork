<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                Pages
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-stone-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- We need to figure out a better way to get the crud actions. -->
                    <crud-view
                        :form="form"
                        singular="Page"
                        @destroy="onDelete"
                        @index="({ page, limit, ...args}) => fetch({ page, limit, ...args })"
                        @execute="onExecute"
                        @save="save"
                        :save="save"
                        :data="data"
                        :paginator="pagination"
                    >
                        <template #modal-title>
                            <div>
                                Create a page
                            </div>
                        </template>
                        <template v-slot:data="{ data }">
                            <div class="flex flex-col">
                                <div class="text-lg text-left">
                                    {{ data.title }}
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div class="text-xs dark:text-stone-300">
                                        {{ data }}
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #no-data>No pages</template>

                        <template #form>
                            <div>
                                <div class="grid grid-cols-6 gap-4 mt-2">
                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">title</label>
                                        <spork-input v-model="form.title" type="text" name="title" id="title" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">URI</label>
                                        <spork-input v-model="form.uri" type="text" name="name" id="name" />
                                    </div>
                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">route</label>
                                        <spork-input v-model="form.route" type="text" name="name" id="name" />
                                    </div>
                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">Middleware</label>
                                        <spork-input v-model="form.settings.type" type="text" name="name" id="name" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">Subtitle</label>
                                        <spork-input v-model="form.subtitle" type="text" name="name" id="name" />
                                    </div>
                                    <div class="col-span-6">
                                        <label for="excerpt" class="block text-sm font-medium">Excerpt</label>
                                        <spork-input v-model="form.excerpt" type="text" name="excerpt" id="excerpt" />
                                    </div>
                                    <div class="col-span-6">
                                        <label for="view" class="block text-sm font-medium">View</label>
                                        <spork-input v-model="form.view" type="text" name="view" id="view" />
                                    </div>
                                    <div class="col-span-6">
                                        <label for="redirect" class="block text-sm font-medium">redirect</label>
                                        <input v-model="form.redirect" type="checkbox" class="ring-stone-600 bg-stone-700"/>
                                    </div>
                                    <div class="col-span-6">
                                        <label for="name" class="block text-sm font-medium">Is Active?</label>
                                        <input v-model="form.is_active" type="checkbox" class="ring-stone-600 bg-stone-700"/>
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
        CrudView,
        AppLayout,
        SporkInput
    },
    setup() {
        return {
            createOpen: ref(false),
            form: ref(({
                name: '',
                settings: {},
            })),
            data: ref([]),
            pagination: ref({}),

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
                await axios.post('/api/crud/pages', form);
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await axios.delete('/api/crud/pages/' + form.id);
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
                '/api/crud/pages', {
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
