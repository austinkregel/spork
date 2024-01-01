<template>
    <div class="h-full flex flex-col">
        <div class="flex flex-wrap py-2">
            <div v-for="(dayOfTheWeek, index) in headers" :key="index" class="flex-1">
                <div class="px-1">
                    <!-- Monday, Tuesday... etc...-->
                    <div v-text="dayOfTheWeek" class="font-medium text-center text-sm"></div>
                </div>
            </div>
        </div>
        <div class="grid flex-grow" :class="gridWidth">
            <div v-for="blankday in blankDaysAtTheStartOfTheMonth" class="bg-stone-300 dark:bg-stone-600" v-if="$store.getters.calendarOptions.type === 'month'">
                <div class="h-full border border-stone-200 dark:border-stone-500 text-left p-1 text-sm">{{ blankday }}</div>
            </div>
            <div v-for="(date, dateIndex) in daysInTheMonth"  :key="dateIndex" class="h-full" >
                <div class="h-full cursor-pointer border-t border-r flex flex-col border-stone-200 dark:border-stone-500">
                    <div class="h-full flex flex-col" >
                        <div class="flex items-center w-8 h-8 m-1 rounded-full justify-center" :class="{'bg-blue-500 dark:bg-blue-600 text-white': isToday(date) }">{{ date }}</div>

                        <div v-if="$store.getters.calendarOptions.type === 'month'" class="flex flex-col flex-1 relative">
                        <!-- <div v-if="true" class="flex flex-col flex-1 relative"> -->
                            <button
                                class="px-1 text-left text-sm flex items-center gap-1 overflow-hidden"
                                @click="(e) => openEventDisplay(e, event, eventIndex(date))" v-for="event in ($store.getters.events[eventIndex(date)] ?? []).slice(0, 4)" :key="'event'+event.id">
                                <div class="flex text-xs">
                                    <span v-if="event.instanceDate.format('m') != '0'">{{ event.instanceDate.format('H:mma') }}</span>
                                    <span v-else>{{ event.instanceDate.format('Ha') }}</span>
                                </div>
                                <span class="text-sm whitespace-nowrap overflow-ellipsis">{{ event.calendarEvent.name }}</span>
                            </button>
                            <div
                                class="w-full text-green-900 border-green-100 border bg-green-50 px-1 text-sm"
                                v-if="$store.getters.events[eventIndex(date)] && $store.getters.events[eventIndex(date)].length > 4"
                            >
                                {{ $store.getters.events[eventIndex(date)].length - 4 }}+ events
                            </div>

                        </div>
                        <div v-else class="flex flex-col flex-grow gap-1" v-if="$store.getters.events[eventIndex(date)]">
                            <!-- Timeslots -->
                            <div class="px-2 border border-stone-200 dark:border-stone-600 flex items-center gap-1" v-for="event in $store.getters.events[eventIndex(date)]" :key="'event'+event.id" :style="'border-color:' + event.calendarEvent.color">
                                <div class="flex text-xs">
                                    <span v-if="event.instanceDate.format('m') != '0'">{{ event.instanceDate.format('H:mma') }}</span>
                                    <span v-else>{{ event.instanceDate.format('Ha') }}</span>
                                </div>
                                <span class="text-sm">{{ event.calendarEvent.name }}</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="flex-grow border-t border-r border-stone-100 dark:border-stone-500 bg-stone-300 dark:bg-stone-600" v-for="index in daysAfterTheMonth" v-if="$store.getters.calendarOptions.type === 'month'">
                <div class="p-2">{{ index }}</div>
            </div>
        </div>
    </div>
</template>

<script>
import {ref} from "vue";

export default {
    name: "MasterCalendar",
    computed: {
        calendars() {
            return this.$store.getters.calendars
        },
        headers() {
            switch (this.$store.getters.calendarOptions.type) {
                case "month":
                    return ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                case "week":
                    return this.shift((new Date(this.year, this.month, this.day)).getDay(), 7);
                case "4days":
                    return this.shift((new Date(this.year, this.month, this.day)).getDay(), 4);
                case "5days":
                    return this.shift((new Date(this.year, this.month, this.day)).getDay(), 5);
                case "day":
                default:
                    return this.shift((new Date(this.year, this.month, this.day)).getDay(), 3).splice(1, 1);
            }
        },
        blankDaysAtTheStartOfTheMonth() {
            const blankDays =  this.$store.getters.calendarOptions.date
                .startOf('month')
                .$d
                .getDay()

            const daysInLastMonth =  this.$store.getters.calendarOptions.date
                .startOf('month')
                .subtract(1, 'month')
                .daysInMonth();

            return createArray(
               blankDays === 7 ? 0 : blankDays
            ).map(i => daysInLastMonth - i + 1).reverse();
        },
        daysInTheMonth() {
            return this.$store.getters.daysInSelectedMonth
        },
        daysAfterTheMonth() {
            const endOfMonthDay = this.$store.getters.calendarOptions.date
                .endOf('month')
                .$d
                .getDay()

            return createArray(6 - endOfMonthDay);
        },
        gridWidth() {
            if (this.headers.length === 7) {
                return 'grid-cols-7';
            }
            if (this.headers.length === 4) {
                return 'grid-cols-4';
            }

            if (this.headers.length === 5) {
                return 'grid-cols-5';
            }

            return 'grid-cols-1';
        }
    },
    methods: {
        isToday(date) {
            return dayjs(this.eventIndex(date)).isToday()
        },
        isOpen(date) {
            return dayjs(this.eventIndex(date)).isToday()
        },
        openEventDisplay(event, repeatingEvent, date) {
            this.openEvent = new CalendarEvent(repeatingEvent)
            this.openEvent.setDate(dayjs(date));
            this.$store.dispatch('openEvent', this.openEvent);
        },
        shift(startIndex, dayCount, days = []) {
            const weekDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            let StartOffset = startIndex === 0 ? 0 : startIndex - 1;

            for (let i = 0; i < dayCount; i ++) {
                if (StartOffset >= weekDays.length) {
                    StartOffset = 0;
                }

               days.push(weekDays[StartOffset++])
            }
            return days;
        },
        eventIndex(day) {
            if (day < 10) {
                day = '0' + day;
            }
            let month = this.month + 1;

            if (month < 10) {
                month = '0' + month;
            }

            return this.year + '-' + month + '-' + day;
        }
    },
    watch: {
        '$store.getters.calendarOptions.date'(newValue, oldValue) {
            this.day = newValue.day();
            this.month = newValue.month();
            this.year = newValue.year();
        }
    },
    setup() {
        const openEvent = ref(null);
        return {
            openEvent,
            days: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
            day: (new Date).getDate(),
            month: (new Date).getMonth(),
            year: (new Date).getFullYear(),
            months: ["January","February","March","April","May","June","July","August","September","October","November","December"],
            dayjs,
        }
    },

}
</script>

<style>
.calendar-timeslot {
    background: lightblue;
}
</style>
