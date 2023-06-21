<script setup>
import ApiTokenManager from '@/Pages/API/Partials/ApiTokenManager.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import QueryBuilder from "@/Pages/API/QueryBuilder.vue";

defineProps({
    models: Array,
});
</script>

<template>
    <AppLayout title="API Tokens">
        <template #header>
            <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                Query Builder
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <QueryBuilder
                    :models="models"
                />
            </div>
        </div>
    </AppLayout>
</template>
