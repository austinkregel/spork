<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import MetricCard from '@/Components/Spork/Molecules/MetricCard.vue';
import WeatherHeader from "@/Pages/Petoskey/WeatherHeader.vue";
import { ref, onMounted } from 'vue';
import dayjs from 'dayjs';
import CollapsibleArticle from "@/Components/Spork/CollapsibleArticle.vue";

const { weather, news, expiring_domains, job_batches } = defineProps({
    project_count: Number,
    server_count: Number,
    domain_count: Number,
    page_count: Number,
    credential_count: Number,
    user_count: Number,
    weather: Object,
    tasks_today: Number,
    news: Object,
    expiring_domains: Object,
    job_batches: Object,
})

const now = ref(dayjs());
const timeInterval = ref(null);

const domainColors = (domain) => {
    if (dayjs(domain.expires_at).diff(now.value, 'days') < 7) {
        return 'text-orange-500 dark:text-orange-400';
    }
    if (dayjs(domain.expires_at).diff(now.value, 'days') < 0) {
        return 'text-red-500 dark:text-red-300';
    }
    return 'text-stone-400 dark:text-yellow-400';
}
const dateFormat = (date) => {
    return dayjs(date * 1000).format('MMMM D HH:mm');
}

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
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mx-8">
            <div>
                <div class="text-xl tracking-wider leading-tight underline pb-4 pt-2">News</div>
                <div class="flex-col flex max-h-[50vh] overflow-auto dark:bg-stone-800 rounded-lg divide-y dark:divide-stone-600">
                    <CollapsibleArticle v-for="article in news.data" :article="article" />
                </div>
            </div>

            <div>
                <div class="text-xl tracking-wider leading-tight underline pb-4 pt-2">Domains Expiring Soon</div>
                <div class="flex-col flex gap-4 max-h-[50vh] overflow-auto dark:bg-stone-800 p-4 rounded-lg">
                    <div v-for="domain in expiring_domains.data" class="flex justify-between">
                        <div class="flex-grow text-indigo-200">{{domain.name}}</div>
                        <div :class="domainColors(domain)">{{domain.expires_at}}</div>
                    </div>
                    <div v-if="expiring_domains.total === 0" class="italic text-stone-300">
                        No domains are expiring soon.
                    </div>
                </div>
                <div class="text-xl tracking-wider leading-tight underline pb-4 pt-4">Batch Jobs</div>
                <div class="flex-col flex gap-4 max-h-[50vh] overflow-auto dark:bg-stone-800 p-4 rounded-lg">
                    <div v-for="batch in job_batches.data" class="flex justify-between">
                        <div class="flex-grow text-indigo-200">{{batch.name}}</div>
                        <div class="flex flex-wrap gap-1">
                            <span v-if="batch.total_jobs === batch.failed_jobs" class="text-red-300">Failed</span>
                            <span v-else-if="batch.pending_jobs > 0" class="text-yellow-300">Processing {{ ((batch.total_jobs - batch.pending_jobs) / batch.total_jobs) * 100}}</span>
                            <span v-else-if="batch.finished_at !== null" class="text-green-400">{{ dateFormat(batch.finished_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style type="sass">
.prose > img, .prose > figure > img {
    max-height: 150px !important;
    border-radius: 1rem;
    margin: 0 auto;
}

</style>
