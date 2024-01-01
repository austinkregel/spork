<script setup>
import {computed, onMounted  } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: String,
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});


</script>


<template>
    <input
        v-model="proxyChecked"
        type="checkbox"
        :value="value"
        class="rounded dark:bg-stone-900 border-stone-300 dark:border-stone-700 text-slate-600 shadow-sm focus:ring-slate-500 dark:focus:ring-slate-600 dark:focus:ring-offset-stone-800"
    >
</template>
