<template>
    <div class="relative flex w-full flex-col gap-2">
        <label class="block text-xs font-semibold uppercase tracking-wide text-stone-700 dark:text-stone-300">
            <input
                :value="modelValue.name"
                :disabled="!editableLabel"
                :class="labelClasses"
                placeholder="Field name"
                type="text"
                @input="emit('update:modelValue', { ...modelValue, name: $event.target.value })"
            >
        </label>

        <template v-if="type !== 'object' && type !== 'select'">
            <GlassInput
                v-if="!type || type === 'text'"
                :model-value="modelValue.value"
                :disabled="disabledInput"
                @update:model-value="(v) => emit('update:modelValue', { ...modelValue, value: v })"
            />
            <GlassInput
                v-else-if="['number', 'numeric', 'int', 'bigint'].includes(type)"
                type="number"
                :model-value="modelValue.value"
                :disabled="disabledInput"
                @update:model-value="(v) => emit('update:modelValue', { ...modelValue, value: v })"
            />
            <textarea
                v-else-if="type === 'textarea'"
                :value="modelValue.value"
                :disabled="disabledInput"
                rows="4"
                class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100 dark:placeholder:text-stone-500"
                @input="emit('update:modelValue', { ...modelValue, value: $event.target.value })"
            />
            <label
                v-else-if="['checkbox', 'tinyint'].includes(type)"
                class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200"
            >
                <input
                    type="checkbox"
                    :checked="!!modelValue.value"
                    :disabled="disabledInput"
                    class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600"
                    @change="emit('update:modelValue', { ...modelValue, value: $event.target.checked })"
                >
                Enabled
            </label>
            <GlassInput
                v-else-if="['date', 'datetime'].includes(type)"
                type="datetime-local"
                :model-value="modelValue.value"
                :disabled="disabledInput"
                @update:model-value="(v) => emit('update:modelValue', { ...modelValue, value: v })"
            />
        </template>

        <div v-if="Array.isArray(modelValue.value)" class="ml-2 flex flex-col gap-2">
            <div v-for="(option, i) in modelValue.value" :key="i" class="flex items-center gap-2">
                <GlassInput
                    class="flex-1"
                    :model-value="option"
                    :disabled="disabledInput"
                    @update:model-value="(v) => emit('update:modelValue', { ...modelValue, value: modelValue.value.map((existing, j) => i === j ? v : existing) })"
                />
                <GlassButton
                    variant="destructive"
                    size="sm"
                    aria-label="Remove option"
                    @click="emit('update:modelValue', { ...modelValue, value: modelValue.value.filter((_, j) => i !== j) })"
                >
                    <TrashIcon class="h-4 w-4" />
                </GlassButton>
            </div>
            <GlassButton
                variant="ghost"
                size="sm"
                @click="emit('update:modelValue', { ...modelValue, value: [...modelValue.value, ''] })"
            >
                Add
            </GlassButton>
        </div>

        <GlassSelect
            v-if="type === 'select'"
            :model-value="modelValue.value"
            :disabled="disabledInput"
            @update:model-value="(v) => emit('update:modelValue', { ...modelValue, value: v })"
        >
            <option v-for="option in options" :key="option?.id ?? option?.name ?? option" :value="option?.value ?? option?.id ?? option">
                {{ prettyOptionName(option?.name ?? option) }}
            </option>
        </GlassSelect>

        <div v-if="errors" class="flex flex-col">
            <div v-for="error in errors" :key="error" class="text-xs text-red-500 dark:text-red-400">{{ error }}</div>
        </div>

        <div v-if="modelValue?.name === 'uuid'" class="absolute right-2 top-0">
            <GlassButton variant="ghost" size="sm" @click="fillUuid">Fill</GlassButton>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import axios from 'axios';
import { TrashIcon } from "@heroicons/vue/24/outline";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassSelect from "@/Components/Glass/GlassSelect.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
    modelValue: Object,
    type: String,
    autofocus: Boolean,
    disabledInput: { type: Boolean, default: true },
    editableLabel: { type: Boolean, default: false },
    errors: { type: Array, default: null },
    options: { type: Array, default: () => [] },
});

const prettyOptionName = (option) => {
    if (typeof option === 'object' && option !== null) {
        return option.en ?? option.name ?? '';
    }
    return option;
};

const labelClasses = computed(() => [
    'block w-full rounded-md border bg-transparent px-3 py-1 text-xs font-semibold uppercase tracking-wide text-stone-800 dark:text-stone-200',
    props.editableLabel
        ? 'border-stone-300 dark:border-stone-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
        : 'border-transparent cursor-not-allowed',
]);

const fillUuid = () => {
    axios.get(route('spork.uuid'))
        .then(({ data }) => {
            emit('update:modelValue', { ...props.modelValue, value: data.uuid });
        })
        .catch((error) => {
            console.log(error);
        });
};
</script>
