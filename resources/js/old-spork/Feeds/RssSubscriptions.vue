<template>
    <div class="w-full">
        <crud-view
            :form="form"
            title="Rss"
            singular="Rss"
            :save="save"
            @save="save"
            :destroy="onDelete"
            @destroy="onDelete"
            @index="() => ''"
            @execute="onExecute"
            :data="$store.getters.features.rss"
            :paginator="$store.getters.featuresPagination"
            :create-button-menu="[{href: '/compendium/rss/unfiltered',name: 'Unfiltered feed' }]"
        >
            <template v-slot:data="{ data }">
                <div class="flex flex-col">
                    <div class="text-lg text-left">
                        {{ data.name }}
                    </div>
                    <div class="text-xs">
                        {{ data.settings }}
                    </div>
                </div>
            </template>
            <template v-slot:modal-title>Add to your feed</template>
            <template v-slot:form>
                <div class="flex flex-col gap-4">
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="rss">
                            Name of your Rss Feed
                        </label>
                        <spork-input v-model="form.name" id="name" type="text" placeholder="Buggy"/>
                    </div>
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="property">
                            URL
                        </label>
                        <spork-input v-model="form.settings.url" id="url" type="text" placeholder="https://...rss.xml" />
                    </div>
                </div>
            </template>

            <template #no-data>No rss in your garage</template>
        </crud-view>

    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    setup() {
        return {
            paginator: ({}),
            rsss: ([]),
            page: (1),
            createOpen: false,
            form: {
                name: '',
                settings: {
                    url: '',
                }
            },
            years: Array(200).fill(1, 0, 200).map((i, year) => year + 1900),
            date: ref(new Date()),
            decoded: ref({}),
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
        dateFormat(rss) {
            return '<span class="text-gray-900">' + rss.starts_at  + '  at </span>' +
                '<span class="text-gray-800">' + dayjs(rss.last_occurrence || rss.remind_at).format('h:mma') + '</span>'
        },
        async save(form) {
            if (!form.id) {
                console.log('creating with', form)
                this.$store.dispatch('createFeature', {
                    ...form,
                    feature: 'rss'
                })
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await this.$store.dispatch('deleteFeature', data);
        },
        async onExecute({ actionToRun, selectedItems}) {
            try {
                await this.$store.dispatch('executeAction', {
                    url: actionToRun.url,
                    data: {
                        selectedItems
                    },
                });
                Spork.toast('Success! Running action...');

            } catch (e) {
                Spork.toast(e.message, 'error');
            }
        },
    },
    async mounted() {
    }
}
</script>

<style scoped>

</style>
