<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FileOrFolder from "@/old-spork/Development/FileOrFolder.vue";

defineProps({
    directories: {
        type: Array,
        default: () => [],
    },
    files: {
        type: Array,
        default: () => [],
    },
});
const openFile = (file) => console.log('opening file', file)
</script>

<template>

    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                Pages
            </h2>
        </template>
        <div class="max-w-7xl mx-auto py-12 flex flex-col gap-8">
            <div class="flex flex-wrap gap-4">
                <div
                    v-for="(topic, i) in directories"
                    class="w-64 p-3 border border-stone-200 dark:border-stone-600 rounded-lg bg-white dark:bg-stone-600"
                    @contextmenu.prevent="(e) => openContextMenu(e, topic)"
                    :key="'research-'+i"
                    >
                    <router-link
                        :to="'/research/'+ topic.id"
                    >
                        <div  class="font-medium truncate">{{ topic.name }}</div>
                    </router-link>
                    <pre class=" h-48 shadow-inset overflow-hidden text-xs border-t py-2 my-2">{{ topic?.settings?.body }}</pre>
                    <div class="text-stone-500 dark:text-stone-200 border-t mt-4 pt-2 flex items-center justify-between">
                        <span>{{ topic.updated_at }}</span>

                        <button @click="() => deleteFeature(topic)">
                            <TrashIcon class="w-4 h-4 text-red-500" />
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap divide-y divide-stone-900">
                <div
                    v-for="(topic, i) in files"
                    class="w-full p-3 bg-white dark:bg-stone-600"
                    :key="'research-'+i"
                    >
                    <router-link
                        :to="'/research/'+ topic.id"
                    >
                        <div  class="font-medium truncate">{{ topic.name }}</div>
                    </router-link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
</style>
