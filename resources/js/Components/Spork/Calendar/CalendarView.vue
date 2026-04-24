<script setup>
import { ref, onMounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import axios from 'axios';

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

const colorMap = {
    '#3b82f6': { bg: 'rgba(59,130,246,0.12)', bgDark: 'rgba(59,130,246,0.2)', border: '#3b82f6' },
    '#10b981': { bg: 'rgba(16,185,129,0.12)', bgDark: 'rgba(16,185,129,0.2)', border: '#10b981' },
    '#8b5cf6': { bg: 'rgba(139,92,246,0.12)', bgDark: 'rgba(139,92,246,0.2)', border: '#8b5cf6' },
    '#f59e0b': { bg: 'rgba(245,158,11,0.12)', bgDark: 'rgba(245,158,11,0.2)', border: '#f59e0b' },
    '#ef4444': { bg: 'rgba(239,68,68,0.12)', bgDark: 'rgba(239,68,68,0.2)', border: '#ef4444' },
    '#ec4899': { bg: 'rgba(236,72,153,0.12)', bgDark: 'rgba(236,72,153,0.2)', border: '#ec4899' },
};

function getPastelColors(hex) {
    if (colorMap[hex]) return colorMap[hex];
    return { bg: hex + '1f', bgDark: hex + '33', border: hex };
}

const isDark = () => document.documentElement.classList.contains('dark');

const typeLabels = {
    budget: '$',
    task: '\u2713',
    operation: '\u2699',
    event: '\u25CF',
};

function formatDuration(start, end) {
    if (!start || !end) return '';
    const ms = end - start;
    const mins = Math.round(ms / 60000);
    if (mins < 60) return `${mins}m`;
    const hrs = Math.floor(mins / 60);
    const rm = mins % 60;
    return rm > 0 ? `${hrs}h ${rm}m` : `${hrs}h`;
}

function formatTime(date) {
    if (!date) return '';
    let h = date.getHours();
    const m = date.getMinutes();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return m > 0 ? `${h}:${String(m).padStart(2, '0')} ${ampm}` : `${h} ${ampm}`;
}

const fetchEvents = async (start, end) => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/calendar/events', {
            params: {
                start: start.toISOString(),
                end: end.toISOString(),
            },
        });

        events.value = data.events.map((event) => {
            const pastel = getPastelColors(event.color);
            const dark = isDark();
            return {
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end,
                backgroundColor: dark ? pastel.bgDark : pastel.bg,
                borderColor: 'transparent',
                textColor: dark ? 'rgb(245,245,244)' : 'rgb(28,25,23)',
                display: event.display || 'auto',
                allDay: event.allDay || false,
                extendedProps: {
                    description: event.description,
                    type: event.type,
                    rrule: event.rrule,
                    recurring: event.recurring,
                    model_id: event.model_id,
                    model_type: event.model_type,
                    accentColor: pastel.border,
                    originalColor: event.color,
                },
            };
        });

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

const handleDatesSet = (info) => {
    fetchEvents(info.start, info.end);
};

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    events: [],
    nowIndicator: true,
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    slotDuration: '00:30:00',
    slotLabelInterval: '01:00:00',
    slotLabelFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short',
    },
    allDaySlot: true,
    allDayText: 'All Day',
    eventContent: (arg) => {
        const type = arg.event.extendedProps?.type || 'event';
        const icon = typeLabels[type] || '';
        const title = arg.event.title || '';
        const accent = arg.event.extendedProps?.accentColor || '#3b82f6';
        const viewType = arg.view?.type || '';
        const isTimeGrid = viewType.startsWith('timeGrid');
        const start = arg.event.start;
        const end = arg.event.end;

        const wrapper = document.createElement('div');
        wrapper.classList.add('fc-event-content-custom');
        wrapper.setAttribute('data-event-type', type);
        wrapper.style.borderLeft = `3px solid ${accent}`;

        if (isTimeGrid && !arg.event.allDay) {
            wrapper.classList.add('fc-event-card');

            const titleRow = document.createElement('div');
            titleRow.classList.add('fc-event-card-title');

            const badgeEl = document.createElement('span');
            badgeEl.classList.add('fc-event-type-icon');
            badgeEl.textContent = icon;
            badgeEl.style.color = accent;
            titleRow.appendChild(badgeEl);

            const titleEl = document.createElement('span');
            titleEl.textContent = title;
            titleRow.appendChild(titleEl);

            wrapper.appendChild(titleRow);

            if (start && end) {
                const timeRow = document.createElement('div');
                timeRow.classList.add('fc-event-card-time');
                const duration = formatDuration(start, end);
                timeRow.textContent = `${formatTime(start)} \u203A ${formatTime(end)}${duration ? ` (${duration})` : ''}`;
                wrapper.appendChild(timeRow);
            }
        } else {
            wrapper.classList.add('fc-event-chip');

            const badgeEl = document.createElement('span');
            badgeEl.classList.add('fc-event-type-icon');
            badgeEl.textContent = icon;
            badgeEl.style.color = accent;
            wrapper.appendChild(badgeEl);

            const titleEl = document.createElement('span');
            titleEl.classList.add('fc-event-chip-title');
            titleEl.textContent = title;
            wrapper.appendChild(titleEl);
        }

        return { domNodes: [wrapper] };
    },
    eventClick: (info) => {
        emit('eventClick', {
            id: info.event.id,
            title: info.event.title,
            start: info.event.start,
            end: info.event.end,
            color: info.event.extendedProps?.originalColor || info.event.backgroundColor,
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
    eventDisplay: 'auto',
    dayMaxEvents: true,
    height: props.fullscreen ? 'calc(100vh - 5rem)' : 'auto',
    eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short',
    },
    selectable: false,
    editable: false,
});

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
    <div class="calendar-container rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm p-4">
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="text-sm text-stone-500 dark:text-stone-400">Loading events...</div>
        </div>
        <FullCalendar ref="calendarRef" :options="calendarOptions" />
    </div>
</template>

<style>
/* ================================================================
   Base
   ================================================================ */
.calendar-container .fc {
    color: rgb(28 25 23);
    font-family: inherit;
}
.dark .calendar-container .fc {
    color: rgb(245 245 244);
}

/* ================================================================
   Toolbar
   ================================================================ */
.calendar-container .fc-header-toolbar {
    margin-bottom: 1rem;
}
.calendar-container .fc-toolbar-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: rgb(28 25 23);
}
.dark .calendar-container .fc-toolbar-title {
    color: rgb(245 245 244);
}
.calendar-container .fc-button {
    background-color: rgb(231 229 228);
    color: rgb(41 37 36);
    border: 1px solid rgb(214 211 209);
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color 150ms ease-out, border-color 150ms ease-out;
}
.dark .calendar-container .fc-button {
    background-color: rgb(68 64 60);
    color: rgb(231 229 228);
    border-color: rgb(87 83 78);
}
.calendar-container .fc-button:hover {
    background-color: rgb(214 211 209);
}
.dark .calendar-container .fc-button:hover {
    background-color: rgb(87 83 78);
}
.calendar-container .fc-button:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgb(99 102 241 / 0.5);
}
.calendar-container .fc-button-active {
    background-color: rgb(99 102 241);
    color: white;
    border-color: rgb(99 102 241);
}
.dark .calendar-container .fc-button-active {
    background-color: rgb(79 70 229);
    border-color: rgb(79 70 229);
}

/* ================================================================
   Shared grid borders
   ================================================================ */
.calendar-container .fc-theme-standard td,
.calendar-container .fc-theme-standard th,
.calendar-container .fc-theme-standard .fc-scrollgrid {
    border-color: rgb(231 229 228);
}
.dark .calendar-container .fc-theme-standard td,
.dark .calendar-container .fc-theme-standard th,
.dark .calendar-container .fc-theme-standard .fc-scrollgrid {
    border-color: rgb(68 64 60);
}

/* Column headers (shared between dayGrid & timeGrid) */
.calendar-container .fc-col-header-cell {
    background-color: rgb(245 245 244);
    color: rgb(68 64 60);
    padding: 0.5rem 0;
    font-size: 0.875rem;
    font-weight: 500;
}
.dark .calendar-container .fc-col-header-cell {
    background-color: rgb(68 64 60);
    color: rgb(214 211 209);
}

/* ================================================================
   Day-grid specifics (month view)
   ================================================================ */
.calendar-container .fc-daygrid-day {
    background-color: white;
}
.dark .calendar-container .fc-daygrid-day {
    background-color: rgb(28 25 23);
}
.calendar-container .fc-daygrid-day.fc-day-today {
    background-color: rgb(238 242 255);
}
.dark .calendar-container .fc-daygrid-day.fc-day-today {
    background-color: rgb(55 48 163 / 0.15);
}
.calendar-container .fc-daygrid-day-number {
    color: rgb(68 64 60);
    padding: 0.5rem;
}
.dark .calendar-container .fc-daygrid-day-number {
    color: rgb(214 211 209);
}

/* ================================================================
   Time-grid specifics (week / day views)
   ================================================================ */
.calendar-container .fc-timegrid-slot {
    height: 3rem;
    border-color: rgb(231 229 228);
}
.dark .calendar-container .fc-timegrid-slot {
    border-color: rgb(68 64 60);
}
.calendar-container .fc-timegrid-slot-label {
    font-size: 0.75rem;
    color: rgb(120 113 108);
    padding: 0 0.5rem;
    vertical-align: top;
}
.dark .calendar-container .fc-timegrid-slot-label {
    color: rgb(168 162 158);
}

/* All-day section */
.calendar-container .fc-timegrid-allday {
    background-color: rgb(250 250 249);
}
.dark .calendar-container .fc-timegrid-allday {
    background-color: rgb(41 37 36);
}

/* Column backgrounds */
.calendar-container .fc-timegrid-col {
    background-color: white;
}
.dark .calendar-container .fc-timegrid-col {
    background-color: rgb(28 25 23);
}

/* Today column in time-grid */
.calendar-container .fc-timegrid-col.fc-day-today {
    background-color: rgb(238 242 255 / 0.5);
}
.dark .calendar-container .fc-timegrid-col.fc-day-today {
    background-color: rgb(55 48 163 / 0.08);
}

/* Now-indicator line */
.calendar-container .fc-timegrid-now-indicator-line {
    border-color: rgb(239 68 68);
    border-width: 2px;
}
/* Now-indicator dot */
.calendar-container .fc-timegrid-now-indicator-arrow {
    border-color: rgb(239 68 68);
    border-top-color: transparent;
    border-bottom-color: transparent;
}

/* ================================================================
   Events – Daybridge-style pastel cards
   ================================================================ */

/* Base event styling (all views) */
.calendar-container .fc-event {
    border: 0 !important;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: box-shadow 150ms ease-out, transform 150ms ease-out;
    overflow: hidden;
}
.calendar-container .fc-event:hover {
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    transform: translateY(-1px);
}
/* Time-grid events should not shift vertically on hover */
.calendar-container .fc-timegrid-event:hover {
    transform: none;
    box-shadow: 0 2px 8px 0 rgb(0 0 0 / 0.15);
}

/* Prevent FullCalendar default inner containers from clipping */
.calendar-container .fc-event-main {
    overflow: visible;
}

/* ----------------------------------------------------------------
   Event content – chip mode (month / all-day row)
   ---------------------------------------------------------------- */
.calendar-container .fc-event-chip {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.375rem;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    line-height: 1.4;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}
.calendar-container .fc-event-chip-title {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ----------------------------------------------------------------
   Event content – card mode (time-grid timed events)
   ---------------------------------------------------------------- */
.calendar-container .fc-event-card {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    padding: 0.375rem 0.5rem;
    height: 100%;
    overflow: hidden;
    border-radius: 0.25rem;
}
.calendar-container .fc-event-card-title {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-weight: 600;
    font-size: 0.8125rem;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.calendar-container .fc-event-card-time {
    font-size: 0.6875rem;
    opacity: 0.7;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ----------------------------------------------------------------
   Type icon (shared)
   ---------------------------------------------------------------- */
.calendar-container .fc-event-type-icon {
    flex-shrink: 0;
    font-size: 0.6875rem;
    font-weight: 700;
}

/* ----------------------------------------------------------------
   Budget event de-emphasis
   ---------------------------------------------------------------- */
.calendar-container .fc-event:has(.fc-event-content-custom[data-event-type="budget"]) {
    opacity: 0.65;
}
.calendar-container .fc-event:has(.fc-event-content-custom[data-event-type="budget"]) .fc-event-chip-title,
.calendar-container .fc-event:has(.fc-event-content-custom[data-event-type="budget"]) .fc-event-card-title {
    font-weight: 400;
    font-style: italic;
}

/* ================================================================
   Background events
   ================================================================ */
.calendar-container .fc-bg-event {
    opacity: 0.08;
    border-radius: 0;
}
.dark .calendar-container .fc-bg-event {
    opacity: 0.1;
}

/* ================================================================
   "+N more" popover
   ================================================================ */
.calendar-container .fc-popover {
    background-color: white;
    border: 1px solid rgb(231 229 228);
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    z-index: 40;
}
.dark .calendar-container .fc-popover {
    background-color: rgb(41 37 36);
    border-color: rgb(68 64 60);
}
.calendar-container .fc-popover .fc-popover-header {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: rgb(41 37 36);
    background-color: rgb(245 245 244);
    border-radius: 0.5rem 0.5rem 0 0;
}
.dark .calendar-container .fc-popover .fc-popover-header {
    background-color: rgb(68 64 60);
    color: rgb(214 211 209);
}
.calendar-container .fc-popover .fc-popover-body {
    padding: 0.25rem;
}

/* "+N more" link */
.calendar-container .fc-daygrid-more-link {
    color: rgb(99 102 241);
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.125rem 0.25rem;
}
.dark .calendar-container .fc-daygrid-more-link {
    color: rgb(165 180 252);
}
.calendar-container .fc-daygrid-more-link:hover {
    color: rgb(79 70 229);
    text-decoration: underline;
}
.dark .calendar-container .fc-daygrid-more-link:hover {
    color: rgb(199 210 254);
}
</style>
