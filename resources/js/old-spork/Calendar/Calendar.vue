<template>
    <div class="flex flex-wrap flex-grow w-full h-full">
        <div style="width: 300px;" class="px-2 bg-white dark:bg-gray-700 h-full flex flex-col justify-between w-full">
            <div class="mt-2">
                <div class="w-full flex flex-wrap justify-between items-center">
                    <feature-required feature="calendar" :allow-more-than-one="true" :settings="defaultCalendarSettings"></feature-required>
                    <div>
                        <select :value="$store.getters.calendarOptions.type" @change="changeType" class="py-1 px-2 rounded border-gray-200 dark:border-gray-500 dark:text-gray-100 dark:bg-gray-600">
                            <option value="month">Month</option>
                            <option value="week">Week</option>
                            <option value="4days">4 Days</option>
                            <option value="5days">5 Days</option>
                            <option value="day">Single Day</option>
                        </select>
                    </div>
                </div>
                <mini-calendar :calendars="$store.getters.calendars" />
                <div class="flex flex-wrap">
                    <div class="text-lg w-full mt-2 font-bold">Calendars</div>

                    <div v-if="$store.getters.calendars.length > 0">
                        <div v-for="calendar in $store.getters.calendars" :key="'calendar' + calendar.id">{{ calendar.name }}</div>
                    </div>
                    <div v-else class="w-full italic">No calendars</div>
                </div>
            </div>
            <div class="flex flex-col my-2" v-if="event?.calendarEvent">
                <div class="text-xl font-medium">{{ event.calendarEvent.name }}</div>
                <div class="text-sm font-thin">
                    {{ event.instanceDate.format('dddd, MMMM D') }} · {{ event.instanceDate.format('HH:mma') }}<span v-if="event.calendarEvent.date_end">&nbsp;&mdash;&nbsp;{{ dayjs(event.calendarEvent.date_end).format('HH:mma') }}</span>
                </div>
                <div v-if="event.calendarEvent.users.length > 0" class="text-sm flex items-center gap-1">
                    <users-icon class="w-4 h-4"/>
                    {{ event.calendarEvent.users.map(user => user.name).join(', ') }}
                </div>
            </div>
        </div>

        <div class="flex flex-col flex-1 border-l dark:border-gray-600 border-gray-200">
            <master-calendar :calendars="$store.getters.calendars">
                <div slot="events" slot-scope="{ events }">
                    <pre>{{ events }}</pre>
                </div>
            </master-calendar>
        </div>
    </div>

</template>

<script>
import MasterCalendar from "./MasterCalendar";
import MiniCalendar from "./MiniCalendar";
import {UsersIcon} from "@heroicons/vue/outline";

export default {
    name: "Calendar",
    components: {
        MasterCalendar,
        MiniCalendar,
        UsersIcon,
    },
    computed: {
        event() {
            const event = this.$store.state.Calendar.openEvent?.calendarEvent;

            if (!event) {
                return null;
            }

            event.calendarEvent.users = event.calendarEvent.users.map(user => ({
                settings: user.settings,
                role: user.role,
                ...user.user
            }))
            return event
        }
    },

    methods: {
        changeType(event) {
            this.$store.commit('setTheCalendarType', event.target.value)
        }
    },
    setup() {
        return {
            dayjs,
            defaultCalendarSettings: {
                start_date: new Date,
                end_date: new Date,
            }
        }
    }
}
</script>

<style scoped>

</style>
