<template>
    <div class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-2">
                <button
                    v-for="operation in operations"
                    :key="operation.id"
                    type="button"
                    class="w-full text-left px-4 py-3 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900"
                    :class="selectedOperation?.id === operation.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200' : 'border-stone-200 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-300'"
                    @click="() => selectOperation(operation)"
                >
                    <p class="text-sm font-semibold">{{ operation.label }}</p>
                    <p class="text-xs">{{ operation.description }}</p>
                </button>
            </div>

            <div class="lg:col-span-2 border border-stone-200 dark:border-stone-800 rounded-lg p-4 bg-white dark:bg-stone-900 shadow-sm space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                            Target scope
                        </label>
                        <select
                            v-model="form.scope"
                            class="mt-1 block w-full rounded-md border-stone-300 dark:border-stone-700 dark:bg-stone-800 text-sm text-stone-800 dark:text-stone-100 focus:outline-none focus:ring-stone-500 focus:border-stone-500"
                        >
                            <option value="domains">Domains</option>
                            <option value="servers">Servers</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                            Filter tags
                        </label>
                        <SporkInput v-model="form.filter" class="mt-1 w-full" placeholder="finance, production" />
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                        {{ selectedOperation?.label ?? 'Choose an operation' }} inputs
                    </p>
                    <div v-for="field in (selectedOperation?.fields ?? [])" :key="field.id">
                        <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                            {{ field.label }}
                        </label>
                        <component
                            :is="field.component"
                            v-model="form.payload[field.id]"
                            v-bind="field.props"
                            class="mt-1 w-full"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <SporkButton secondary @click="$emit('cancel')">
                        Cancel
                    </SporkButton>
                    <SporkButton primary @click="submit">
                        Queue operation
                    </SporkButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SporkButton from "@/Components/Spork/SporkButton.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";

import { reactive, ref, watch } from 'vue';

const props = defineProps({
    operations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['submit', 'cancel']);

const createDefaultForm = () => ({
    scope: 'domains',
    filter: '',
    payload: {},
});

const selectedOperation = ref(props.operations[0] ?? {});
const form = reactive(createDefaultForm());

watch(
    () => props.operations,
    (operations) => {
        if (!operations.length) {
            selectedOperation.value = {};
            return;
        }

        if (!operations.find((operation) => operation.id === selectedOperation.value.id)) {
            selectedOperation.value = operations[0];
        }
    },
    { immediate: true },
);

const selectOperation = (operation) => {
    selectedOperation.value = operation;
    form.payload = {};
};

const submit = () => {
    emit('submit', {
        operation: selectedOperation.value.id,
        scope: form.scope,
        filter: form.filter,
        payload: form.payload,
    });
    Object.assign(form, createDefaultForm());
};
</script>

