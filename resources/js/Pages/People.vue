<template>
    <div class="w-full">
        <crud-view
            :form="form"
            title="Contacts"
            singular="Contact"
            @destroy="onDelete"
            @index="({ page, limit }) => $store.dispatch('getFeatureLists', { page, limit, feature: 'contacts' })"
            @execute="onExecute"
            :data="$store.getters?.features?.contacts ?? []"
            :paginator="$store.getters.featuresPagination"
            :feature="true"
        >
            <template v-slot:data="{ data }">
                <div class="flex flex-col">
                    <div class="text-lg text-left">
                        {{ data.settings.year }}
                    </div>
                    <div class="text-xs">
                        {{ data.settings.vin }}
                    </div>
                </div>
            </template>
            <template #no-data>No contacts in your Contacts</template>
        </crud-view>

    </div>
</template>

<script>
import { ref } from 'vue';
class Form {
    constructor(props) {
    }
}
export default {
    setup() {
        return {
            createOpen: ref(false),
            form: ref(({
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
                this.$store.dispatch('createContact', form)
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await this.$store.dispatch('deleteContact', data);
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
                console.error(e.message, 'error');
            }
        },
    },
    created() {
        // this.$store.dispatch('getFeatureLists', { page: 1, limit: 15, feature: 'contacts' })
    }

}
</script>

<style scoped>

</style>
