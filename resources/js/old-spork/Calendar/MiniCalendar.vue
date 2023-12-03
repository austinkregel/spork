<template>
<div>
    <div class="flex items-center justify-between mx-2">
        <div class="py-2 text-left">
            <span v-text="months[month]" class="text-lg font-bold"></span>
            <span v-text="year" class="ml-1 text-lg font-normal"></span>
        </div>
        <div>
            <button
                type="button"
                class="transition ease-in-out duration-100 inline-flex cursor-pointer p-1 rounded-full"
                @click="goBackAMonth">
                <svg class="h-4 w-4 inline-flex"  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                type="button"
                class="transition ease-in-out duration-100 inline-flex cursor-pointer p-1 rounded-full"
                @click="goForwardAMonth">
                <svg class="h-4 w-4 inline-flex"  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
    <div class="flex flex-wrap mb-3">
        <div v-for="(dayOfTheWeek, index) in headers" :key="index" class="flex-1">
            <div class="px-1" @click="() => chooseDate(dayOfTheWeek)">
                <div
                    v-text="dayOfTheWeek"
                    class="font-medium text-center text-sm"></div>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap h-full">
        <div v-for="blankday in blankDaysAtTheStartOfTheMonth" style="width: 14.28%">
            <div class="text-center p-1 text-sm"></div>
        </div>
        <div v-for="(date, dateIndex) in $store.getters.fullDaysInMonth" :key="dateIndex" style="width: 14.28%">
            <div
                @click="eventClicked(date)"
                class="cursor-pointer w-8 h-8 align-middle justify-center items-center flex flex-col text-center text-sm leading-none rounded-full leading-loose transition ease-in-out duration-100"
                :class="{
                                    'bg-blue-500 text-white dark:bg-blue-700': isToday(date),
                                    'dark:hover:text-gray-200 dark:hover:bg-blue-700 hover:text-gray-600 hover:bg-blue-200': isToday(date) === false,
                                    'bg-gray-100 dark:bg-gray-800': daysInTheMonth.includes(date)
                                }"
            >
                <span>{{ date }}</span>
                <div class="flex items-center mb-1" v-if="events[eventIndex(date)]">
                    <span class="rounded-full w-1 h-1 -mt-1 bg-blue-300"></span>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: "MiniCalendar",
    setup() {
        return {
            headers: ref(["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]),
            day: ref((new Date).getDate()),
            month: ref((new Date).getMonth()),
            year: ref((new Date).getFullYear()),
            months: ref(["January","February","March","April","May","June","July","August","September","October","November","December"]),
        };
    },
    computed: {
        calendars() {
            return this.$store.getters.calendars;
        },
        events() {
            return this.$store.getters.events
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
            const endOfMonthDay =  this.$store.getters.calendarOptions.date
                .endOf('month')
                .$d
                .getDay()

            return createArray(endOfMonthDay === 0 ? 7 : endOfMonthDay+1 );
        }
    },
    watch: {
        '$store.getters.calendarOptions.date'(newValue, oldValue) {
            this.day = newValue.day();
            this.month = newValue.month();
            this.year = newValue.year();
        }
    },
    methods: {
        isToday(date) {
            return dayjs(this.eventIndex(date)).isToday()
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
        },
        goBackAMonth() {
            this.$store.commit('decrementByType');
        },
        goForwardAMonth() {
            this.$store.commit('incrementByType');
        },
        eventClicked(date) {

        },
    },
}
</script>
