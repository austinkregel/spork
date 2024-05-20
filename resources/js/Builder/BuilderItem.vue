<script setup>
import BuilderIcon from '@/Builder/BuilderIcon.vue';

const { name, icon, item } = defineProps({
    name: String,
    icon: String,
    item: Object,
});
</script>

<template>
    <div class="flex flex-col items-center border rounded ">
        <BuilderIcon :name="icon" />
        <span class="text-xs">{{ name }}</span>
    </div>
</template>

<style scoped>

</style>
