<script setup>
defineProps({
    title: String,
    value: Number,
    icon: Object,
})
</script>

<template>
    <div class="flex flex-col gap-2 p-4 bg-white dark:bg-zinc-800 border border-stone-200 dark:border-stone-800 rounded-lg shadow">
        <div class="font-semibold text-stone-600 dark:text-stone-300 dark:text-stone-300">
            {{ title }}
        </div>
        <div class="text-4xl text-stone-800 dark:text-stone-100">
            {{ value }}
        </div>
    </div>
</template>
