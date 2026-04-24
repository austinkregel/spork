<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';

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

const colorOptions = [
    { value: '#3b82f6', label: 'Blue' },
    { value: '#10b981', label: 'Green' },
    { value: '#f59e0b', label: 'Amber' },
    { value: '#ef4444', label: 'Red' },
    { value: '#8b5cf6', label: 'Purple' },
    { value: '#ec4899', label: 'Pink' },
];

const isEditing = computed(() => props.event !== null);

const isReadOnly = computed(() => {
    if (!props.event) return false;
    const modelType = props.event.extendedProps?.model_type;
    return modelType && modelType !== 'App\\Models\\Event';
});

const readOnlyLabel = computed(() => {
    const type = props.event?.extendedProps?.type;
    if (!type) return '';
    const labels = { task: 'task', budget: 'budget', operation: 'operation' };
    return labels[type] || type;
});

const modalTitle = computed(() => {
    if (!isEditing.value) return 'Create Event';
    return isReadOnly.value ? 'Event Details' : 'Edit Event';
});

function buildRRule() {
    if (!isRecurring.value) return null;
    return `FREQ=${recurrenceFrequency.value};INTERVAL=${recurrenceInterval.value}`;
}

function close() {
    emit('close');
}

watch(() => props.show, (newVal) => {
    if (!newVal) return;

    if (props.event) {
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
    } else {
        const date = props.selectedDate ? dayjs(props.selectedDate) : dayjs();
        startDate.value = date.format('YYYY-MM-DD');
        startTime.value = '09:00';
        endDate.value = date.format('YYYY-MM-DD');
        endTime.value = '10:00';
        title.value = '';
        description.value = '';
        color.value = '#3b82f6';
        isRecurring.value = false;
    }
});

async function save() {
    if (!title.value.trim()) return;

    saving.value = true;

    try {
        const startAt = dayjs(`${startDate.value} ${startTime.value}`).toISOString();
        const endAt = dayjs(`${endDate.value} ${endTime.value}`).toISOString();
        const payload = {
            title: title.value,
            description: description.value || null,
            start_at: startAt,
            end_at: endAt,
            color: color.value,
            rrule: buildRRule(),
        };

        if (isEditing.value && props.event?.extendedProps?.model_id) {
            if (props.event.extendedProps.model_type === 'App\\Models\\Event') {
                await axios.put(`/api/calendar/events/${props.event.extendedProps.model_id}`, payload);
            } else {
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
}
</script>

<template>
    <GlassModal :open="show" :title="modalTitle" size="lg" @close="close">
        <div v-if="isReadOnly" class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                This event is from your {{ readOnlyLabel }} and can't be edited here.
            </p>
        </div>

        <div class="space-y-4">
            <GlassField label="Title" required>
                <GlassInput v-model="title" :disabled="isReadOnly" placeholder="Event title" />
            </GlassField>

            <GlassField label="Description">
                <textarea
                    v-model="description"
                    rows="3"
                    :disabled="isReadOnly"
                    placeholder="Event description"
                    class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100"
                />
            </GlassField>

            <div class="grid grid-cols-2 gap-4">
                <GlassField label="Start Date" required>
                    <GlassInput v-model="startDate" type="date" :disabled="isReadOnly" />
                </GlassField>
                <GlassField label="Start Time" required>
                    <GlassInput v-model="startTime" type="time" :disabled="isReadOnly" />
                </GlassField>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <GlassField label="End Date" required>
                    <GlassInput v-model="endDate" type="date" :disabled="isReadOnly" />
                </GlassField>
                <GlassField label="End Time" required>
                    <GlassInput v-model="endTime" type="time" :disabled="isReadOnly" />
                </GlassField>
            </div>

            <GlassField label="Color">
                <GlassSelect v-model="color" :disabled="isReadOnly">
                    <option v-for="option in colorOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </GlassSelect>
            </GlassField>

            <div v-if="!isReadOnly">
                <label class="flex items-center">
                    <input
                        v-model="isRecurring"
                        type="checkbox"
                        class="rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-700"
                    >
                    <span class="ml-2 text-sm text-stone-700 dark:text-stone-300">Recurring event</span>
                </label>

                <div v-if="isRecurring" class="mt-2 grid grid-cols-2 gap-4">
                    <GlassField label="Frequency">
                        <GlassSelect v-model="recurrenceFrequency">
                            <option value="DAILY">Daily</option>
                            <option value="WEEKLY">Weekly</option>
                            <option value="MONTHLY">Monthly</option>
                            <option value="YEARLY">Yearly</option>
                        </GlassSelect>
                    </GlassField>
                    <GlassField label="Interval">
                        <GlassInput v-model.number="recurrenceInterval" type="number" min="1" />
                    </GlassField>
                </div>
            </div>
        </div>

        <template #footer>
            <GlassButton variant="secondary" @click="close">{{ isReadOnly ? 'Close' : 'Cancel' }}</GlassButton>
            <GlassButton v-if="!isReadOnly" :disabled="saving || !title.trim()" @click="save">
                {{ saving ? 'Saving...' : 'Save' }}
            </GlassButton>
        </template>
    </GlassModal>
</template>
