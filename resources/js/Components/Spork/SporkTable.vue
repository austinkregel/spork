<style scoped>

</style>

<template>
<div class="w-full rounded-lg overflow-hidden shadow">
    <div class="w-full dark:text-white bg-white dark:bg-stone-700 rounded-t-lg flex flex-wrap items-center justify-between">
        <div class="bg-stone-600 dark:bg-stone-800 relative z-0 border-b border-stone-300 dark:border-stone-700/70 w-full p-4 flex flex-wrap justify-between items-center">
            <slot name="table-top" />
        </div>
        <table class="w-full text-left">
            <thead class="bg-white dark:bg-stone-900 w-full">
                <tr class="w-full">
                    <th v-for="(header, $i) in headers" :key="'crud-header'+$i" scope="col" class="relative z-0 isolate py-3.5 pr-3 text-left text-sm font-semibold text-gray-900 table-cell">
                       {{header}}
                    </th>
                </tr>
            </thead>
            <tbody>
            <tr v-for="datum in data" :key="datum" class="w-full even:bg-stone-50 even:dark:bg-stone-800">
                <td class="w-full relative z-0 py-4 pr-3 text-sm font-medium text-gray-900">
                    <slot name="datum" :datum="datum"></slot>
                </td>
            </tr>
            <tr v-if="data.length === 0">
                <td class="relative z-0 py-4 px-4 text-sm font-medium text-gray-900 dark:text-white">
                    <slot name="no-data" class="w-full p-4 italic text-center">
                        No data present in table
                    </slot>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="w-full dark:text-white flex justify-between flex-wrap bg-stone-100 dark:bg-stone-800 px-4 py-2">
        <slot name="table-bottom" />
    </div>
</div>
</template>

<script setup>
import {ref} from "vue";

const { headers, data } = defineProps({
      headers: {
        type: Array,
        required: true,
          default: () => []
      },
    data: {
        type: Array,
        required: true,
        default: () => []
    },
})

const currentPage = ref(1);
const filtersOpen = ref(false);
const selectedItems = ref([]);

</script>
