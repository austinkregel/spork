<template>
    <div class="w-full">
        <crud-view
            :form="form"
            title="Bookmark"
            singular="Bookmark"
            :save="save"
            :upload="() => {}"
            @destroy="onDelete"
            @index="() => ''"
            @execute="onExecute"
            :data="$store.getters.features?.bookmark"
            :paginator="$store.getters.featuresPagination"
        >
            <template v-slot:data="{ data }">
                <div class="flex flex-col">
                    <div class="text-lg text-left">
                        {{ data.settings.year }}
                        {{ data.settings.make }}
                        {{ data.settings.model }}
                    </div>
                    <div class="text-xs">
                        {{ data.settings.vin }}
                    </div>
                </div>
            </template>
            <template v-slot:modal-title>Add to your Bookmark</template>
            <template v-slot:form>
                <div class="flex flex-col gap-4">
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="property">
                            Name of your Bookmark
                        </label>
                        <spork-input v-model="form.name" :class="{'border-red-500': form.hasErrors('name') }" id="name" type="text" placeholder="Buggy"/>
                        <div v-if="form.hasErrors('name')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('name')}}
                        </div>
                    </div>
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="property">
                            URL
                        </label>
                        <spork-input v-model="form.settings.url" :class="{'border-red-500': form.hasErrors('url') }" id="url" type="text" placeholder="https://..." />
                        <div v-if="form.hasErrors('url')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('url')}}
                        </div>
                    </div>
                </div>
            </template>

            <template #no-data>No bookmark in your Bookmark</template>
        </crud-view>

    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    setup() {
        return {
            paginator: ref({}),
            bookmark: ref([]),
            page: ref(1),
            createOpen: ref(false),
            form: ref(new Form({
                name: '',
                settings: {
                    vin: '',
                    model_year: (new Date).getFullYear(),
                    track_oil_changes: false,
                    track_oem_maintenance: false,
                }
            })),
            years: Array(200).fill(1, 0, 200).map((i, year) => year + 1900),
            date: ref(new Date()),
            decoded: ref({}),
            dragHandler: type => (event) => {
                event.preventDefault();

                if (!event.dataTransfer.getData('text/uri-list')) {
                    return;
                }

                const url = event.dataTransfer.getData('text/uri-list');
                console.log('event thing', {url})
            },
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
                this.$store.dispatch('createBookmark', form)
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await this.$store.dispatch('deleteBookmark', data);
            Spork.toast('Deleted ' + data.name);
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
}
</script>

<style scoped>

</style>
