<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import CalendarView from '@/Components/Spork/Calendar/CalendarView.vue';
import EventModal from '@/Components/Spork/Calendar/EventModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import { XMarkIcon, PlusIcon } from '@heroicons/vue/24/outline';

defineProps({
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

const openCreateModal = () => {
    selectedEvent.value = null;
    selectedDate.value = null;
    eventModalOpen.value = true;
};

const closeModal = () => {
    eventModalOpen.value = false;
    selectedEvent.value = null;
    selectedDate.value = null;
};
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-stone-950">
        <Head :title="title" />

        <div class="fixed right-4 top-4 z-50 flex items-center gap-2">
            <GlassButton :icon-left="PlusIcon" @click="openCreateModal">New Event</GlassButton>
            <GlassButton
                variant="secondary"
                :icon-left="XMarkIcon"
                href="/-/communication/calendar"
            >
                Exit Full Screen
            </GlassButton>
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
