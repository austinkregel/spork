<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                Settings
            </h2>
        </template>
        <div class="">
            <div v-for="result in results">
                <pre>{{ result.indexUid }} -- found {{ result.estimatedTotalHits }}</pre>

                <div class="grid grid-cols-4 gap-4">
                    <div v-for="hit in result.hits" class="bg-white dark:bg-stone-800 text-sm">
                        <!-- Need a polymorphic object that can preview our various types. -->
                        <!-- Also how do we want to sort things?. -->
                        <div class="truncate overflow-hidden max-h-32" v-html="hit?.content ?? ''"></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
const { results } = defineProps({
    results: {
        type: Array,
        required: true,
    },
})
</script>

<style>
img {
    max-height: 150px;
    margin: 0 auto;
}
</style>
