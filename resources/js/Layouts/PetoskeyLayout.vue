<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div class="antialiased">
        <Head :title="title" />
        <slot />
    </div>
</template>
<style>
iframe, img, image, video, svg, object, embed, canvas, audio, picture, source {
    max-width: 400px;
}
</style>
