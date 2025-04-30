<script setup>
import { ref, computed } from 'vue';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div class="min-h-screen">
        <Head :title="title" />
        <Banner />


        <main class=""  style="max-height: calc(100vh - 65px); min-height: calc(100vh - 65px);">
            <slot/>
        </main>
    </div>
</template>
