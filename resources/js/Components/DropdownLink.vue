<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" type="submit" class="block w-full px-4 py-2 text-left text-sm leading-5 text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 focus:outline-none focus:bg-stone-100 dark:focus:bg-stone-800 transition duration-150 ease-in-out">
            <slot />
        </button>

        <a v-else-if="as =='a'" :href="href" class="block px-4 py-2 text-sm leading-5 text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 focus:outline-none focus:bg-stone-100 dark:focus:bg-stone-800 transition duration-150 ease-in-out">
            <slot />
        </a>

        <Link v-else :href="href" class="block px-4 py-2 text-sm leading-5 text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 focus:outline-none focus:bg-stone-100 dark:focus:bg-stone-800 transition duration-150 ease-in-out">
            <slot />
        </Link>
    </div>
</template>
