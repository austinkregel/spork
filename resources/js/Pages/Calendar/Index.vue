<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CalendarView from '@/Components/Spork/Calendar/CalendarView.vue';
import EventModal from '@/Components/Spork/Calendar/EventModal.vue';
import SporkButton from '@/Components/Spork/SporkButton.vue';
import { ArrowsPointingOutIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    title: String,
});

const eventModalOpen = ref(false);
const selectedEvent = ref(null);
const selectedDate = ref(null);
const calendarRef = ref(null);

const handleEventClick = (event) => {
    selectedEvent.value = event;
    eventModalOpen.value = true;
};

const handleDateClick = (info) => {
    selectedEvent.value = null;
    selectedDate.value = info.dateStr;
    eventModalOpen.value = true;
};

const handleEventSaved = () => {
    if (calendarRef.value?.refresh) {
        calendarRef.value.refresh();
    }
};

const closeModal = () => {
    eventModalOpen.value = false;
    selectedEvent.value = null;
    selectedDate.value = null;
};
</script>

<template>
    <AppLayout :title="title">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                    Calendar
                </h2>
                <Link
                    href="/-/calendar/fullscreen"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-stone-700 dark:text-stone-300 bg-white dark:bg-stone-800 border border-stone-300 dark:border-stone-700 rounded-md hover:bg-stone-50 dark:hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <ArrowsPointingOutIcon class="h-5 w-5" />
                    Full Screen
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <CalendarView
                    ref="calendarRef"
                    @event-click="handleEventClick"
                    @date-click="handleDateClick"
                />
            </div>
        </div>

        <EventModal
            :show="eventModalOpen"
            :event="selectedEvent"
            :selected-date="selectedDate"
            @close="closeModal"
            @saved="handleEventSaved"
        />
    </AppLayout>
</template>
