<script setup>
import { Head } from '@inertiajs/vue3';

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
