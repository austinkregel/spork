<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import MetricCard from '@/Components/Spork/Molecules/MetricCard.vue';
import WeatherHeader from "@/Pages/Petoskey/WeatherHeader.vue";
import { ref, onMounted } from 'vue';
import dayjs from 'dayjs';


const { weather, unread_number, ...props } = defineProps({
    project_count: Number,
    server_count: Number,
    domain_count: Number,
    page_count: Number,
    credential_count: Number,
    user_count: Number,
    weather: Object,
    tasks_today: Number,
  unread_messages: Number,

})

const now = ref(dayjs());
const timeInterval = ref(null);

onMounted(() => {
  timeInterval.value = setInterval(() => {
    now.value = dayjs();
  }, 1000)
})
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="mx-auto max-w-7xl px-4 ">
          <WeatherHeader
              :weather="weather"
              :now="now"
          />
        </div>
        <div class="py-2">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-5 gap-4">
                <MetricCard title="Projects" :value="project_count" />
                <MetricCard title="Users" :value="user_count" />
                <MetricCard title="Servers" :value="server_count" />
                <MetricCard title="Domains" :value="domain_count" />
                <MetricCard title="Credentials" :value="credential_count" />
            </div>
          <div class="border-t border-stone-700 mt-4"></div>

          <pre>{{ {unread_messages, tasks_today } }}</pre>
        </div>
    </AppLayout>
</template>
