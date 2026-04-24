<template>
    <AppLayout title="Pages">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-stone-800 dark:text-stone-200">Pages</h2>
        </template>
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <GlassSurface class="overflow-hidden">
                <crud-view
                    :form="form"
                    singular="Page"
                    :data="data"
                    :paginator="pagination"
                    :save="save"
                    @destroy="onDelete"
                    @index="({ page, limit, ...args}) => fetch({ page, limit, ...args })"
                    @execute="onExecute"
                    @save="save"
                >
                    <template #modal-title>Create a page</template>
                    <template v-slot:data="{ data }">
                        <div class="flex flex-col">
                            <div class="text-base font-semibold text-stone-900 dark:text-stone-50">{{ data.title }}</div>
                            <div class="text-xs text-stone-500 dark:text-stone-400">{{ data.uri }}</div>
                        </div>
                    </template>
                    <template #no-data>No pages</template>

                    <template #form>
                        <div class="grid grid-cols-6 gap-4">
                            <div class="col-span-6">
                                <glass-field label="Title">
                                    <glass-input v-model="form.title" name="title" />
                                </glass-field>
                            </div>
                            <div class="col-span-6">
                                <glass-field label="URI">
                                    <glass-input v-model="form.uri" name="uri" />
                                </glass-field>
                            </div>
                            <div class="col-span-6">
                                <glass-field label="Route">
                                    <glass-input v-model="form.route" name="route" />
                                </glass-field>
                            </div>
                            <div class="col-span-6">
                                <glass-field label="Middleware">
                                    <glass-input v-model="form.settings.type" name="middleware" />
                                </glass-field>
                            </div>
                            <div class="col-span-6">
                                <glass-field label="Subtitle">
                                    <glass-input v-model="form.subtitle" name="subtitle" />
                                </glass-field>
                            </div>
                            <div class="col-span-6">
                                <glass-field label="Excerpt">
                                    <glass-input v-model="form.excerpt" name="excerpt" />
                                </glass-field>
                            </div>
                            <div class="col-span-6">
                                <glass-field label="View">
                                    <glass-input v-model="form.view" name="view" />
                                </glass-field>
                            </div>
                            <div class="col-span-6">
                                <label class="flex items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-200">
                                    <input v-model="form.redirect" type="checkbox" class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600">
                                    Redirect
                                </label>
                            </div>
                            <div class="col-span-6">
                                <label class="flex items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-200">
                                    <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600">
                                    Is active
                                </label>
                            </div>
                        </div>
                    </template>
                </crud-view>
            </GlassSurface>
        </div>
    </AppLayout>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import AppLayout from '@/Layouts/AppLayout.vue';
import CrudView from "@/Components/Spork/CrudView.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassField from "@/Components/Glass/GlassField.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import { buildUrl } from "@kbco/query-builder";

export default {
    components: { CrudView, AppLayout, GlassSurface, GlassField, GlassInput },
    setup() {
        return {
            createOpen: ref(false),
            form: ref({ name: '', settings: {} }),
            data: ref([]),
            pagination: ref({}),
        };
    },
    watch: {
        date(to) {
            this.form.remind_at = dayjs(to).startOf('day').utc().format("YYYY-MM-DD HH:mm:ss");
        },
    },
    methods: {
        hasErrors(error) {
            if (!this.form.errors) return '';
            return this.form.errors[error] ?? null;
        },
        async save(form) {
            if (!form.id) {
                await axios.post('/api/crud/pages', form);
            } else {
                console.log('No edit method defined');
            }
        },
        async onDelete() {
            await axios.delete('/api/crud/pages/' + this.form.id);
        },
        async onExecute({ actionToRun, selectedItems }) {
            try {
                await this.$store.dispatch('executeAction', {
                    url: actionToRun.url,
                    data: { selectedItems },
                });
            } catch (e) {
                console.log(e.message, 'error');
            }
        },
        async fetch({ page, limit, ...args }) {
            const { data: { data, ...pagination } } = await axios.get(buildUrl(
                '/api/crud/pages', {
                    page, limit,
                    ...args,
                    include: [],
                },
            ));

            this.data = data;
            this.pagination = pagination;
        },
    },
};
</script>
