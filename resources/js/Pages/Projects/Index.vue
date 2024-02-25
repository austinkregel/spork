<template>
    <AppLayout title="Dashboard">
        <div class="w-full border-b dark:border-slate-700 dark:bg-stone-950">
            <div  class="max-w-7xl mx-auto px-8 py-4 flex items-center gap-2 font-semibold text-2xl text-stone-800 dark:text-stone-200 leading-tight">
                <Link href="/-/projects" class="underline">
                    Projects
                </Link>
            </div>
        </div>
        <div class="max-w-7xl w-full mx-auto mt-4 px-4 grid grid-cols-4 gap-4">
            <div class="col-span-3"></div>
            <div class="col-span-1">
                <Link href="/-/projects/create">
                    <div class="p-4 bg-stone-200 dark:bg-stone-700 rounded-lg shadow-md hover:shadow-lg flex items-center justify-between">
                        <span class="text-stone-800 dark:text-stone-200">Create a new project</span>
                        <PlusIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true" />
                    </div>
                </Link>
            </div>
        </div>

        <div class="max-w-7xl w-full mx-auto py-8 px-4 grid grid-cols-2 gap-4">
            <div v-for="item in data" class="">
                <Link :href="'/-/projects/' +  item.id" class="block p-4 bg-white dark:bg-stone-700 rounded-lg shadow-md hover:shadow-lg flex items-center justify-between">
                    <span>{{ item.name }}</span>
                    <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { usePage, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ChevronRightIcon, PlusIcon } from "@heroicons/vue/24/solid";

const $page = usePage()

const { data, paginator } = defineProps({
    data: Array,
    paginator: Object,
})

</script>
