<template>
    <div class="w-full">
        <crud-view
            :form="form"
            title="Reminders"
            singular="Reminder"
            :save="save"
            @destroy="onDelete"
            @index="() => ''"
            @execute="onExecute"
            :data="$store.getters.reminderEvents"
            :paginator="$store.getters.featuresPagination"
        >
            <template v-slot:data="{ data }">
                <div class="flex flex-col border-l-2 pl-4" :style="'border-color: ' + data.calendarEvent.color">
                    <div class="text-lg text-left">
                        {{ data.calendarEvent.name }}
                    </div>
                    <div class="text-slate-600 dark:text-slate-300">
                        <span v-date="data.nextOccurrences(dayjs(), dayjs().add(1, 'day'))[0] ?? data.calendarEvent.date_start"></span>
                    </div>
                </div>
            </template>
            <template v-slot:modal-title>What would you like to be reminded of?</template>
            <template v-slot:form>
                <div class="flex flex-col gap-4">
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="property">
                            Reminder Name
                        </label>
                        <spork-input v-model="form.name" :class="{'border-red-500': form.hasErrors('name') }" id="name" type="text" placeholder="Laundry"/>
                        <div v-if="form.hasErrors('name')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('name')}}
                        </div>
                    </div>
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="date_start">
                            Remind at
                        </label>
                        <spork-input v-model="form.date_start" :class="{'border-red-500': form.hasErrors('date_start') }" id="date_start" type="datetime-local" placeholder="2022-01-01 00:00:00"/>
                        <div v-if="form.hasErrors('date_start')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('date_start')}}
                        </div>
                    </div>
                    <div class="">
                        <label class="block text-sm font-bold mb-2" for="color">
                            Reminder Color
                        </label>
                        <div class="flex items-center p-1 rounded-md overflow-hidden dark:bg-gray-500">
                            <input v-model="form.color" class="w-10 h-10 border focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:placeholder-gray-300" :class="{'border-red-500': form.hasErrors('color') }" id="color" type="color" />
                            <input v-model="form.color" placeholder="#db4a39" type="text" class="flex-1 border border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:placeholder-gray-300" :class="{'border-red-500': form.hasErrors('color') }">
                        </div>
                        <div v-if="form.hasErrors('color')" class="w-full text-red-500 text-sm italic">
                            {{ form.error('color')}}
                        </div>
                    </div>

                    <div class="w-full flex flex-wrap border border-slate-200 dark:border-slate-500 rounded divide-y divide-slate-200 dark:divide-slate-500">
                        <div class="w-full flex">
                            <div class="py-2 px-4 w-2/3">All-Day</div>
                            <div class="py-2 px-4 w-1/3 text-right">
                                <toggle-input v-model="form.all_day"/>
                            </div>
                        </div>
                        <div class="w-full flex">
                            <div class="py-2 px-4 w-2/3">Does this repeat?</div>
                            <div class="py-2 px-4 w-1/3 text-right">
                                <toggle-input v-model="form.repeats"/>
                            </div>
                        </div>
                        <div class="px-4 py-2 w-full flex items-center gap-2" v-if="form.repeats">
                            <div class="flex items-center gap-2">
                                <span>Repeat</span>
                                <span v-if="form.settings?.for_set_position !== 'number_of_occurrences'">every</span>
                                <span v-else>after</span>
                            </div>
                            <spork-input v-model="form.frequency" />
                            <select v-model="form.for_set_position" class="mt-1 py-2 px-4 border focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:placeholder-gray-300 rounded-md" >
                                <option value="for_week_numbers">week</option>
                                <option value="for_month_day">month</option>
                                <option value="for_year_day">year</option>
                                <option value="for_day">day</option>
                                <option value="number_of_occurrences">number of occurrences</option>
                                <!-- Our library does support finer options, but for the sake of performance lots not allow that... -->
                                <!-- Yes I see you. That means you could use for_hour, for_minute, and for_second, but that may cripple your server... You have been warned. -->
                            </select>
                        </div>

                        <div v-if="form.for_set_position === 'for_week_numbers'" class="flex items-center w-full py-4">
                            <spork-input v-model="form[form.for_set_position]" />
                        </div>
                        <div v-if="form.for_set_position === 'for_month'" class="flex items-center w-full py-4">
                            <select v-model="form.settings[form.for_set_position]" class="mt-1 py-2 px-4 border focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:placeholder-gray-300 rounded-md" >
                                <option value="">For month</option>
                            </select>
                        </div>

                        <div class="px-4 py-2 w-full flex justify-between items-center">
                            <label class="block mb-2" for="date_end">
                                Does this have an end date?
                            </label>

                            <Switch
                                @click="() => {
                                    if (form.date_end) {
                                        form.date_end = undefined;
                                    } else {
                                        form.date_end = dayjs().format('YYYY-MM-DD')+'T'+dayjs().format('HH:ss')
                                    }
                                }"
                                :class="form.date_end ? 'bg-blue-900 dark:bg-blue-400' : 'bg-slate-500 dark:bg-slate-400'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full"
                            >
                                <span
                                :class="form.date_end ? 'translate-x-6' : 'translate-x-1'"
                                class="inline-block h-4 w-4 transform transition rounded-full bg-white dark:bg-slate-600"
                                />
                            </Switch>
                        </div>

                        <div class="px-4 py-2 w-full" v-if="form.date_end">
                            <label class="block text-sm font-bold mb-2" for="date_end">
                                Ends at (optional)
                            </label>
                            <spork-input v-model="form.date_end" class="w-full" :class="{'border-red-500': form.hasErrors('date_end') }" id="date_end" type="datetime-local" placeholder="2022-01-01 00:00:00"/>
                            <div v-if="form.hasErrors('date_end')" class="w-full text-red-500 text-sm italic">
                                {{ form.error('date_end')}}
                            </div>
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
import { Switch } from '@headlessui/vue'

export default {
    components: {
        Switch
    },
    setup() {
        return {
            paginator: ref({}),
            vehicles: ref([]),
            page: ref(1),
            createOpen: ref(false),
            form: ref(new Form({
                color: '#db4a39',
                repeats: false,
                all_day: false,
                interval: null,
                frequency: null,
                weekday_start: null,
                number_of_occurrences: null,
                date_start: null,
                date_end: null,
                for_months: null,
                for_week_numbers: null,
                for_year_day: null,
                for_month_day: null,
                for_day: null,
                for_hour: null,
                for_minute: null,
                for_second: null,
                for_set_position: null,
            })),
            years: Array(200).fill(1, 0, 200).map((i, year) => year + 1900),
            decoded: ref({}),
            repeat_amount: 1,
            repeat_unit: 'for_month',
            dayjs,
        }
    },
    watch: {
        'form.color'(to, from) {
            if (!(this.form?.color ?? '').startsWith('#')) {
                this.form.color = '#'+this.form.color
            }
            if ((this.form?.settings?.color ?? '') === '#') {
                this.form.color = '#db4a39'
            }
        },
    },
    methods: {
        hasErrors(error) {
            if (!this.form.errors) {
                return '';
            }

            return this.form.errors[error] ?? null;
        },
        async save(form) {
            if (!form.id) {
                this.$store.dispatch('createReminder', form)
            } else {
                console.log('No edit method defined')
            }
        },
        async onDelete(data) {
            await this.$store.dispatch('deleteReminder', data.calendarEvent);
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
