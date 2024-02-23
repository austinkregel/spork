<script setup>
import SporkLabel from "@/Components/Spork/SporkLabel.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";

defineProps({
    label: {
        type: String,
        default: "Label"
    },
    required: {
        type: Boolean,
        default: false
    },
    modelValue: {
        type: String,
        default: ""
    },
    type: {
        type: String,
        default: "text"
    },
    placeholder: {
        type: String,
        default: ""
    }
});
defineEmits(["update:modelValue"]);
</script>

<template>
<div>
    <SporkLabel>
        <span>{{ label }}</span>
    </SporkLabel>
    <SporkInput
        :model-value="modelValue"
        @change="e => $emit('update:modelValue', e.target.value)"
        :type="type"
        :placeholder="placeholder"
    />
</div>

</template>

<style scoped>

</style>
