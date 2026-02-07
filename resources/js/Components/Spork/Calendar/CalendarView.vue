<script setup>
import { ref, onMounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import axios from 'axios';
import dayjs from 'dayjs';

const props = defineProps({
    fullscreen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['eventClick', 'dateClick']);

const calendarRef = ref(null);
const events = ref([]);
const loading = ref(false);

const calendarOptions = ref({
    plugins: [dayGridPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek,dayGridDay',
    },
    events: [],
    eventClick: (info) => {
        emit('eventClick', {
            id: info.event.id,
            title: info.event.title,
            start: info.event.start,
            end: info.event.end,
            color: info.event.backgroundColor,
            extendedProps: info.event.extendedProps,
        });
    },
    dateClick: (info) => {
        emit('dateClick', {
            date: info.dateStr,
            dateObj: info.date,
        });
    },
    datesSet: handleDatesSet,
    eventDisplay: 'block',
    height: props.fullscreen ? 'auto' : '600px',
    eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short',
    },
});

const handleDatesSet = (info) => {
    fetchEvents(info.start, info.end);
};

const fetchEvents = async (start, end) => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/calendar/events', {
            params: {
                start: start.toISOString(),
                end: end.toISOString(),
            },
        });

        events.value = data.events.map((event) => ({
            id: event.id,
            title: event.title,
            start: event.start,
            end: event.end,
            backgroundColor: event.color,
            borderColor: event.color,
            extendedProps: {
                description: event.description,
                type: event.type,
                rrule: event.rrule,
                recurring: event.recurring,
                model_id: event.model_id,
                model_type: event.model_type,
            },
        }));

        if (calendarRef.value?.getApi) {
            const api = calendarRef.value.getApi();
            api.removeAllEvents();
            api.addEventSource(events.value);
        }
    } catch (error) {
        console.error('Failed to fetch events:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    // Initial load will happen via datesSet callback
});

defineExpose({
    refresh: () => {
        if (calendarRef.value?.getApi) {
            const api = calendarRef.value.getApi();
            const view = api.view;
            fetchEvents(view.activeStart, view.activeEnd);
        }
    },
});
</script>

<template>
    <div class="calendar-container">
        <div v-if="loading" class="flex items-center justify-center p-8">
            <div class="text-stone-600 dark:text-stone-400">Loading events...</div>
        </div>
        <FullCalendar ref="calendarRef" :options="calendarOptions" />
    </div>
</template>

<style scoped>
.calendar-container {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    padding: 1rem;
}

.dark .calendar-container {
    background-color: rgb(28 25 23);
}

.fc {
    color: rgb(28 25 23);
}

.dark .fc {
    color: rgb(245 245 244);
}

.fc-header-toolbar {
    margin-bottom: 1rem;
}

.fc-button {
    background-color: rgb(231 229 228);
    color: rgb(41 37 36);
    border: 1px solid rgb(214 211 209);
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.dark .fc-button {
    background-color: rgb(68 64 60);
    color: rgb(231 229 228);
    border-color: rgb(87 83 78);
}

.fc-button:hover {
    background-color: rgb(214 211 209);
}

.dark .fc-button:hover {
    background-color: rgb(87 83 78);
}

.fc-button-active {
    background-color: rgb(99 102 241);
    color: white;
}

.dark .fc-button-active {
    background-color: rgb(79 70 229);
}

.fc-daygrid-day {
    background-color: white;
}

.dark .fc-daygrid-day {
    background-color: rgb(28 25 23);
}

.fc-daygrid-day.fc-day-today {
    background-color: rgb(238 242 255);
}

.dark .fc-daygrid-day.fc-day-today {
    background-color: rgb(55 48 163 / 0.2);
}

.fc-daygrid-day-number {
    color: rgb(68 64 60);
}

.dark .fc-daygrid-day-number {
    color: rgb(214 211 209);
}

.fc-col-header-cell {
    background-color: rgb(245 245 244);
    color: rgb(68 64 60);
}

.dark .fc-col-header-cell {
    background-color: rgb(68 64 60);
    color: rgb(214 211 209);
}

.fc-event {
    border: 0;
    border-radius: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    cursor: pointer;
}

.fc-event-title {
    font-weight: 500;
}
</style>
