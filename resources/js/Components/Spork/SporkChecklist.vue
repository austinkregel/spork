<script setup>
import SporkLabel from "@/Components/Spork/SporkLabel.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";

defineProps({
    label: {
        type: String,
        default: null
    },
    required: {
        type: Boolean,
        default: false
    },
    modelValue: {
        type: Array,
        default: () => []
    },
    type: {
        type: String,
        default: "text"
    },
    placeholder: {
        type: String,
        default: ""
    },
    canAddMore: {
        type: Boolean,
        default: true
    }
});
defineEmits(["update:modelValue"]);
</script>

<template>
<div class="flex gap-2 flex-col">
    <div v-if="label" class="block uppercase tracking-wide text-xs font-semibold dark:text-stone-300"  >
        {{ label }}
    </div>
    <label v-for="(item, index) in modelValue" class="flex items-center gap-2">
        <input
            type="checkbox"
            :checked="modelValue[index].checked"
            @change="(event) => { modelValue[index].checked = event.target.checked; $emit('update:modelValue', modelValue)}"
            @click="(event) => { modelValue[index].checked = event.target.checked; $emit('update:modelValue', modelValue)}"
            class="bg-stone-300 dark:bg-stone-700 rounded outline-none focus:outline-none"
        />
        <SporkInput
            :model-value="modelValue[index].name"
            @change="(event) => { modelValue[index].name = event.target.value; $emit('update:modelValue', modelValue)}"
            :type="type"
            :placeholder="placeholder"
        />
        <button @click="() => $emit('update:modelValue', modelValue.filter((item, i) => index !== i))">
            <DynamicIcon icon-name="TrashIcon" class="w-5 h-5 text-red-500" />
        </button>
    </label>
    <div v-if="canAddMore" :class="[undefined, 0].includes(modelValue?.length) ? 'mb-3' : ''">
        <SporkButton xsmall plain @click="() => $emit('update:modelValue', Array.isArray(modelValue) ? [...modelValue, { name: '', checked: false }] : [{ name: '', checked: false }])">Add</SporkButton>
    </div>
</div>
</template>
