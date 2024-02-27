<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-slate-400 dark:border-slate-600 text-lg font-medium leading-5 text-stone-900 dark:text-stone-100 focus:outline-none focus:border-slate-700 transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-lg font-medium leading-5 text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-stone-700 focus:outline-none focus:text-stone-700 dark:focus:text-stone-300 focus:border-stone-300 dark:focus:border-stone-700 transition duration-150 ease-in-out';
});
</script>

<template>
    <Link :href="href ?? '#'" :class="classes">
        <slot />
    </Link>
</template>
