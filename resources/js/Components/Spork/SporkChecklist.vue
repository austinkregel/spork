<script setup>
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";

defineProps({
    label: { type: String, default: null },
    required: { type: Boolean, default: false },
    modelValue: { type: Array, default: () => [] },
    type: { type: String, default: "text" },
    placeholder: { type: String, default: "" },
    canAddMore: { type: Boolean, default: true },
});
const emit = defineEmits(["update:modelValue"]);
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-if="label" class="block text-xs font-semibold uppercase tracking-wide text-stone-700 dark:text-stone-300">
            {{ label }}
        </div>
        <label
            v-for="(item, index) in modelValue"
            :key="index"
            class="flex items-center gap-2"
        >
            <input
                type="checkbox"
                :checked="modelValue[index].checked"
                class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600"
                @change="(event) => { modelValue[index].checked = event.target.checked; emit('update:modelValue', modelValue); }"
            >
            <GlassInput
                class="flex-1"
                :model-value="modelValue[index].name"
                :type="type"
                :placeholder="placeholder"
                @update:model-value="(value) => { modelValue[index].name = value; emit('update:modelValue', modelValue); }"
            />
            <button
                type="button"
                class="rounded-md p-1 text-red-500 hover:bg-red-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                aria-label="Remove item"
                @click="emit('update:modelValue', modelValue.filter((_, i) => index !== i))"
            >
                <DynamicIcon icon-name="TrashIcon" class="h-5 w-5" />
            </button>
        </label>
        <div v-if="canAddMore" :class="[undefined, 0].includes(modelValue?.length) ? 'mb-3' : ''">
            <GlassButton
                variant="ghost"
                size="sm"
                @click="emit('update:modelValue', Array.isArray(modelValue) ? [...modelValue, { name: '', checked: false }] : [{ name: '', checked: false }])"
            >
                Add
            </GlassButton>
        </div>
    </div>
</template>
