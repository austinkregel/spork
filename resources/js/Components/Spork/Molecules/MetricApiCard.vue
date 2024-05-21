<script setup>
import MetricCard from "@/Components/Spork/Atoms/MetricCard.vue";
import {onMounted, ref} from "vue";

const { url, title } = defineProps({
    url: String,
    title: String,
});

const value = ref(null);

onMounted(() => {
    axios.get(url)
        .then(response => {
            value.value = response.data;
        });
});
</script>

<template>
    <MetricCard :title="title" :value="value" />
</template>
