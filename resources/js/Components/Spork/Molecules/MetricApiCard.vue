<script setup>
import MetricCard from "@/Components/Spork/Atoms/MetricCard.vue";
import {onMounted, ref} from "vue";

const { url, title } = defineProps({
    url: String,
    title: String,
});

const value = ref(null);
const loading = ref(true);

onMounted(() => {
    axios.get(url)
        .then(response => {
            value.value = response.data;
        }).finally(() => {
            loading.value = false;
        });
});
</script>

<template>
    <MetricCard :title="title" :value="value" :loading="loading" />
</template>
