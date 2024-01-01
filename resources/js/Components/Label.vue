<script setup>
defineProps({
    value: String,
});
</script>

<template>
    <label class="block font-medium text-sm text-stone-700 dark:text-stone-200">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
