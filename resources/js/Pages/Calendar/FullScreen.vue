<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import CalendarView from '@/Components/Spork/Calendar/CalendarView.vue';
import EventModal from '@/Components/Spork/Calendar/EventModal.vue';
import SporkButton from '@/Components/Spork/SporkButton.vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

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
    <div class="min-h-screen bg-stone-50 dark:bg-stone-900">
        <Head :title="title" />

        <div class="fixed top-4 right-4 z-50">
            <Link
                href="/-/calendar"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-stone-700 dark:text-stone-300 bg-white dark:bg-stone-800 border border-stone-300 dark:border-stone-700 rounded-md shadow-lg hover:bg-stone-50 dark:hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                <XMarkIcon class="h-5 w-5" />
                Exit Full Screen
            </Link>
        </div>

        <div class="p-6">
            <CalendarView
                ref="calendarRef"
                :fullscreen="true"
                @event-click="handleEventClick"
                @date-click="handleDateClick"
            />
        </div>

        <EventModal
            :show="eventModalOpen"
            :event="selectedEvent"
            :selected-date="selectedDate"
            @close="closeModal"
            @saved="handleEventSaved"
        />
    </div>
</template>
