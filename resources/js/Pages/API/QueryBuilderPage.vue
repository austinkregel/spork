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
        <div class="py-6 px-4 dark:bg-stone-950">
            <div class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                Query Builder
            </div>
        </div>

        <div>
                <QueryBuilder
                    :models="models"
                />
            </div>
    </AppLayout>
</template>
