<script setup>
defineProps({
    title: String,
    value: Number,
    icon: Object,
})
</script>

<template>
    <div class="flex flex-col gap-2 p-4 bg-white dark:bg-zinc-800 border border-slate-200 dark:border-slate-800 rounded-lg shadow">
        <div class="font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300">
            {{ title }}
        </div>
        <div class="text-4xl text-gray-800 dark:text-slate-100">
            {{ value }}
        </div>
    </div>
</template>
