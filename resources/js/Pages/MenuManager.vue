<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {ref} from "vue";
import draggable from "vuedraggable";
import NestedDraggable from "@/Components/NestedDraggable.vue";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const menu = ref([]);
// https://sortablejs.github.io/vue.draggable.next/#/nested-example
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                Menu Manager
            </h2>
        </template>
        <div class="py-12 h-full">
            <div
                class="relative sm:flex dark:text-white sm:justify-center sm:items-center h-full bg-dots-darker bg-center bg-stone-100 dark:bg-dots-lighter dark:bg-stone-900 selection:bg-red-500 selection:text-white">
                <div class="w-full bg-stone-800 rounded-lg">

                    <draggable
                        :group="{ name: 'g1' }"
                        :list="menu"
                        class="px-4 pt-4 flex flex-wrap"
                        item-key="name"
                        style="min-width:300px;"
                        tag="ul"
                    >
                        <template #item="{ element }">
                            <li class="w-full p-2 flex flex-col">
                                <div class="font-bold flex justify-between p-2 border border-stone-500"
                                     style="width:400px;">
                                    <div>{{ element.name }}</div>
                                    <button v-if="open" @click="open = !open">&gt; <span v-if="open" class="underline">Close</span>
                                    </button>
                                    <button v-if="!open" @click="open = !open">&lt; <span v-if="!open"
                                                                                          class="underline">Open</span>
                                    </button>
                                </div>
                                <div v-if="open" class="flex gap-2 flex-wrap p-4 w-full">
                                    <label for="">Navigation Label</label>
                                    <input v-model="element.name" class="w-full dark:bg-stone-700 w-full"/>
                                    <label for="">URL</label>
                                    <input v-model="element.path" class="w-full dark:bg-stone-700"/>
                                </div>
                                <div>
                                    <draggable
                                        :group="{ name: 'g1' }"
                                        :list="element.items"
                                        class="px-4 pt-4 flex flex-wrap"
                                        item-key="name"
                                        style="min-width:300px;"
                                        tag="ul"
                                    >
                                        <template #item="{ element: e }">
                                            <li class="w-full p-2 flex flex-col">
                                                <div class="font-bold flex justify-between p-2 border border-stone-500" style="width:400px;">
                                                    <div>{{ e.name }}</div>
                                                    <button v-if="open" @click="open = !open">&gt; <span v-if="open" class="underline">Close</span></button>
                                                    <button v-if="!open" @click="open = !open">&lt; <span v-if="!open" class="underline">Open</span></button>
                                                </div>
                                                <div v-if="open" class="flex gap-2 flex-wrap p-4 w-full">
                                                    <label for="">Navigation Label</label>
                                                    <input v-model="e.name" class="w-full dark:bg-stone-700 w-full"/>
                                                    <label for="">URL</label>
                                                    <input v-model="e.path" class="w-full dark:bg-stone-700"/>
                                                </div>
                                            </li>
                                        </template>
                                    </draggable>
                                </div>
                            </li>
                        </template>
                    </draggable>
                </div>
            </div>
            <button class="text-white px-4 py-2 "
                    @click="() =>menu.push({ name: 'Alala', path: '/iibee', items: [] })"
            >
                + Add more
            </button>
        </div>
    </AppLayout>
</template>

<style>
.bg-dots-darker {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}

@media (prefers-color-scheme: dark) {
    .dark\:bg-dots-lighter {
        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    }
}
</style>
