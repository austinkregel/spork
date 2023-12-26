<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-stone-800 dark:bg-stone-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-stone-800 uppercase tracking-widest hover:bg-stone-700 dark:hover:bg-white focus:bg-stone-700 dark:focus:bg-white active:bg-stone-900 dark:active:bg-stone-300 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
