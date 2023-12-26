<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';

defineProps({
    terms: String,
});
</script>

<template>
    <Head title="Terms of Service" />

    <div class="font-sans text-stone-900 dark:text-stone-100 antialiased">
        <div class="pt-4 bg-stone-100 dark:bg-stone-900">
            <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
                <div>
                    <AuthenticationCardLogo />
                </div>

                <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white dark:bg-stone-800 shadow-md overflow-hidden sm:rounded-lg pslate dark:pslate-invert" v-html="terms" />
            </div>
        </div>
    </div>
</template>
