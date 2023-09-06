<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import MetricCard from '@/Components/Spork/Molecules/MetricCard.vue';

defineProps({
    project_count: Number,
    server_count: Number,
    domain_count: Number,
    page_count: Number,
    credential_count: Number,
    user_count: Number,
    activity_logs: Object
})
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-5 gap-4">
                <MetricCard title="Projects" :value="project_count" />
                <MetricCard title="Users" :value="user_count" />
                <MetricCard title="Servers" :value="server_count" />
                <MetricCard title="Domains" :value="domain_count" />
                <MetricCard title="Credentials" :value="credential_count" />

                <div class="col-span-5 border-t border-zinc-700"> </div>
                <div class="col-span-5 text-white">
                    <div v-for="log in activity_logs.data">
                        {{ log.log_name}}
                        {{ log.description}}
                        {{ log.causer_type}}
                        {{ log.subject_type}}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
