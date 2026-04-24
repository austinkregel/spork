<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CalendarView from '@/Components/Spork/Calendar/CalendarView.vue';
import EventModal from '@/Components/Spork/Calendar/EventModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import { ArrowsPointingOutIcon, PlusIcon } from '@heroicons/vue/24/outline';

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
    <AppLayout :title="title">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-stone-800 dark:text-stone-200">Calendar</h2>
                <div class="flex items-center gap-2">
                    <GlassButton :icon-left="PlusIcon" @click="openCreateModal">New Event</GlassButton>
                    <GlassButton
                        variant="secondary"
                        :icon-left="ArrowsPointingOutIcon"
                        href="/-/communication/calendar/fullscreen"
                    >
                        Full Screen
                    </GlassButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
