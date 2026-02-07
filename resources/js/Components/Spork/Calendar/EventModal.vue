<script setup>
import { ref, computed, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';
import dayjs from 'dayjs';
import SporkButton from '@/Components/Spork/SporkButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    event: {
        type: Object,
        default: null,
    },
    selectedDate: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const title = ref('');
const description = ref('');
const startDate = ref('');
const startTime = ref('');
const endDate = ref('');
const endTime = ref('');
const color = ref('#3b82f6');
const isRecurring = ref(false);
const recurrenceFrequency = ref('WEEKLY');
const recurrenceInterval = ref(1);
const saving = ref(false);

const isEditing = computed(() => props.event !== null);

const colorOptions = [
    { value: '#3b82f6', label: 'Blue' },
    { value: '#10b981', label: 'Green' },
    { value: '#f59e0b', label: 'Amber' },
    { value: '#ef4444', label: 'Red' },
    { value: '#8b5cf6', label: 'Purple' },
    { value: '#ec4899', label: 'Pink' },
];

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.event) {
            // Editing existing event
            title.value = props.event.title || '';
            description.value = props.event.extendedProps?.description || '';
            const start = dayjs(props.event.start);
            const end = dayjs(props.event.end);
            startDate.value = start.format('YYYY-MM-DD');
            startTime.value = start.format('HH:mm');
            endDate.value = end.format('YYYY-MM-DD');
            endTime.value = end.format('HH:mm');
            color.value = props.event.color || '#3b82f6';
            isRecurring.value = props.event.extendedProps?.recurring || false;
        } else if (props.selectedDate) {
            // Creating new event on selected date
            const date = dayjs(props.selectedDate);
            startDate.value = date.format('YYYY-MM-DD');
            startTime.value = '09:00';
            endDate.value = date.format('YYYY-MM-DD');
            endTime.value = '10:00';
            title.value = '';
            description.value = '';
            color.value = '#3b82f6';
            isRecurring.value = false;
        } else {
            // Creating new event - default to today
            const today = dayjs();
            startDate.value = today.format('YYYY-MM-DD');
            startTime.value = '09:00';
            endDate.value = today.format('YYYY-MM-DD');
            endTime.value = '10:00';
            title.value = '';
            description.value = '';
            color.value = '#3b82f6';
            isRecurring.value = false;
        }
    }
});

const buildRRule = () => {
    if (!isRecurring.value) {
        return null;
    }

    const freq = recurrenceFrequency.value;
    const interval = recurrenceInterval.value;

    return `FREQ=${freq};INTERVAL=${interval}`;
};

const save = async () => {
    if (!title.value.trim()) {
        return;
    }

    saving.value = true;

    try {
        const startAt = dayjs(`${startDate.value} ${startTime.value}`).toISOString();
        const endAt = dayjs(`${endDate.value} ${endTime.value}`).toISOString();
        const rrule = buildRRule();

        const payload = {
            title: title.value,
            description: description.value || null,
            start_at: startAt,
            end_at: endAt,
            color: color.value,
            rrule: rrule,
        };

        if (isEditing.value && props.event?.extendedProps?.model_id) {
            // Only update if it's an Event model (not Task/Budget/Operation)
            if (props.event.extendedProps.model_type === 'App\\Models\\Event') {
                await axios.put(`/api/calendar/events/${props.event.extendedProps.model_id}`, payload);
            } else {
                // Can't edit non-Event models from calendar
                console.warn('Cannot edit non-Event models from calendar');
                return;
            }
        } else {
            await axios.post('/api/calendar/events', payload);
        }

        emit('saved');
        close();
    } catch (error) {
        console.error('Failed to save event:', error);
    } finally {
        saving.value = false;
    }
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Dialog :open="show" @close="close" class="relative z-50">
        <div class="fixed inset-0 bg-stone-500 dark:bg-stone-900 bg-opacity-75 transition-opacity" />

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <DialogPanel
                    class="relative transform overflow-hidden rounded-lg bg-white dark:bg-stone-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                >
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button
                            type="button"
                            class="rounded-md bg-white dark:bg-stone-800 text-stone-400 hover:text-stone-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            @click="close"
                        >
                            <span class="sr-only">Close</span>
                            <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <DialogTitle as="h3" class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-100 mb-4">
                                {{ isEditing ? 'Edit Event' : 'Create Event' }}
                            </DialogTitle>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                        Title *
                                    </label>
                                    <input
                                        v-model="title"
                                        type="text"
                                        class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Event title"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="description"
                                        rows="3"
                                        class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Event description"
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                            Start Date *
                                        </label>
                                        <input
                                            v-model="startDate"
                                            type="date"
                                            class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                            Start Time *
                                        </label>
                                        <input
                                            v-model="startTime"
                                            type="time"
                                            class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                            End Date *
                                        </label>
                                        <input
                                            v-model="endDate"
                                            type="date"
                                            class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                            End Time *
                                        </label>
                                        <input
                                            v-model="endTime"
                                            type="time"
                                            class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                        Color
                                    </label>
                                    <select
                                        v-model="color"
                                        class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option v-for="option in colorOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input
                                            v-model="isRecurring"
                                            type="checkbox"
                                            class="rounded border-stone-300 dark:border-stone-700 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-stone-700 dark:text-stone-300">Recurring event</span>
                                    </label>

                                    <div v-if="isRecurring" class="mt-2 grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                                Frequency
                                            </label>
                                            <select
                                                v-model="recurrenceFrequency"
                                                class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            >
                                                <option value="DAILY">Daily</option>
                                                <option value="WEEKLY">Weekly</option>
                                                <option value="MONTHLY">Monthly</option>
                                                <option value="YEARLY">Yearly</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">
                                                Interval
                                            </label>
                                            <input
                                                v-model.number="recurrenceInterval"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-3 py-2 text-sm text-stone-900 dark:text-stone-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                        <SporkButton primary @click="save" :disabled="saving || !title.trim()">
                            {{ saving ? 'Saving...' : 'Save' }}
                        </SporkButton>
                        <SporkButton @click="close">
                            Cancel
                        </SporkButton>
                    </div>
                </DialogPanel>
            </div>
        </div>
    </Dialog>
</template>
