<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
              {{ plural }}
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <crud-view
                        :form="form"
                        :singular="singular"
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
                                Create a {{ singular.toLowerCase() }}
                            </div>
                        </template>
                        <template v-slot:data="{ data }">
                            <div class="flex flex-col">
                                <div class="text-lg text-left">
                                    <Link :href="'/'+link+'/'+ data.id" class="underline">
                                        {{ data.name }}
                                    </Link>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div class="text-xs dark:text-stone-300">
                                        {{ data }}
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #no-data>No {{ link }}</template>

                        <template #form>
                            <div>
                                <div class="flex flex-wrap">
                                    <div class="w-full" v-for="(_, key) in form" :key="key+'-input'">
                                        <div class="w-full"  v-if="description.fillable.includes(key)">
                                          <label for="name" class="block text-sm font-medium">{{ key }}</label>
                                          <spork-input v-model="form[key]" type="text" name="name" id="name" />
                                        </div>
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
    props: ['description', 'plural', 'singular', 'link', 'paginator'],
    setup(props) {
      const { data, ...paginator} = props.paginator;
        return {
            createOpen: ref(false),
            form: ref(props.description?.required?.reduce((fields, field) => ({
              ...fields,
              [field]: ''
            }), {})),
            data: ref(data ?? []),
            pagination: ref(paginator ?? {}),
        }
    },
    methods: {
        hasErrors(error) {
            if (!this.form.errors) {
                return '';
            }

            return this.form.errors[error] ?? null;
        },
      fillDefaultTeamAndUser(form) {
        if (form.hasOwnProperty('team_id')) {
          form.team_id = this.$attrs.auth.user.current_team_id;
        }
        if (form.hasOwnProperty('user_id')) {
          form.user_id = this.$attrs.auth.user.id;
        }

        return form;
      },
        async save(form) {
          form = this.fillDefaultTeamAndUser(form);

            if (!form.id) {
                await axios.post('/api/crud/'+this.link, form);
            } else {
                console.log('No edit method defined')
            }
            await this.fetch({ page: 1, limit: 15, });
            this.clearForm();
        },
      clearForm() {
        for (let key in form) {
          if (form.hasOwnProperty(key)) {
            form[key] = '';
          }
        }
      },
        async onDelete(data) {
            await axios.delete('/api/crud/'+this.link+'/' + form.id);
            await this.fetch({ page: 1, limit: 15, });
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
                '/api/crud/'+this.link, {
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
