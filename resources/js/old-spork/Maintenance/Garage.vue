<template>
    <div class="w-full">
        <crud-view
            :form="form"
            title="Garage"
            singular="Vehicle"
            :save="save"
            @destroy="onDelete"
            @index="() => ''"
            @execute="onExecute"
            :data="$store.getters.vehicles"
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
            <template v-slot:modal-title>Add to your garage</template>
            <template v-slot:form>
                <div class="flex flex-col gap-4">
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="property">
                            Name of your Vehicle
                        </label>
                        <spork-input v-model="form.name" :class="{'border-red-500': form.hasErrors('name') }" id="name" type="text" placeholder="Buggy"/>
                        <div v-if="form.hasErrors('name')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('name')}}
                        </div>
                    </div>
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="property">
                            VIN
                        </label>
                        <spork-input v-model="form.settings.vin" :class="{'border-red-500': form.hasErrors('vin') }" id="property" type="text" placeholder="1FAFP52..." />
                        <div v-if="form.hasErrors('vin')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('vin')}}
                        </div>
                    </div>
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="property">
                            Vehicle year:
                        </label>
                        <select v-model="form.settings.model_year" :class="{'border-red-500': form.hasErrors('model_year') }" class="appearance-none border border-gray-300 dark:border-gray-600 rounded w-full py-2 px-3 text-gray-700 dark:text-gray-100 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-500">
                            <option v-for="year in years" :key="'year'+year" :value="year">{{ year }}</option>
                        </select>
                        <div v-if="form.hasErrors('model_year')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('model_year')}}
                        </div>
                    </div>


                    <div class="w-full flex items-center px-4">
                        <div class="flex items-center h-5">
                            <input v-model="form.settings.track_oil_changes" id="track_oil_changes" name="track_oil_changes" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="track_oil_changes" class="font-medium">Track oil changes?</label>
                            <p class="text-gray-500 dark:text-gray-300">Get notified every few months to get an oil change. If configured with an OBD2 sensor, it will notify you every 8000 miles from the last reset..</p>
                        </div>
                    </div>

                    <div class="w-full  flex items-center px-4">
                        <div class="flex items-center h-5">
                            <input v-model="form.settings.track_oem_maintenance" id="track_oem" name="track_oem" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="track_oem" class="font-medium">Track OEM recommended maintenance?</label>
                            <p class="text-gray-500 dark:text-gray-300">This will monitor OEM channels for part recalls, recommended maintenance schedules (timing belt changes, etc...)</p>
                        </div>
                    </div>
                </div>
            </template>

            <template #no-data>No vehicles in your garage</template>
        </crud-view>

    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    setup() {
        return {
            paginator: ref({}),
            vehicles: ref([]),
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
        dateFormat(vehicle) {
            return '<span class="text-gray-900">' + vehicle.starts_at  + '  at </span>' +
                '<span class="text-gray-800">' + dayjs(vehicle.last_occurrence || vehicle.remind_at).format('h:mma') + '</span>'
        },
        async save(form) {
            if (!form.id) {
                this.$store.dispatch('createVehicle', form)
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await this.$store.dispatch('deleteVehicle', data);
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
    mounted() {
    }
}
</script>

<style scoped>

</style>
