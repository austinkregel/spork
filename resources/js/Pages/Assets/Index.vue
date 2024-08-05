<script setup>
import { ref, computed } from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import {buildUrl} from "@kbco/query-builder";
import SporkTable from "@/Components/Spork/Atoms/SporkTable.vue";
const form = ref({
    input: ''
});

const data = ref(null);
const isNumber = computed(() => {
    return isNaN(Number(form.value.input));
});
const decodedValue = computed(() => {
    return !isNaN(Number(form.value.input)) ? Number(form.value.input) : atob(form.value.input);
});

const search = () => {
    axios.get(buildUrl('/api/crud/assets', {
        filter: {
            id: decodedValue.value
        }
    })).then(response => {
        data.value = (response.data);
    });
}
</script>

<template>
    <AppLayout title="Assets">
        <div id="printable" class="text-black px-8 py-2 rounded-lg ">
            <input
                autofocus
                v-model="form.input"
                @keyup.enter="search"
                class="dark:bg-stone-800 dark:text-white dark:border-stone-600"
                type="text"
                placeholder="Search by ID or Asset ID"
            />
        </div>

        <SporkTable
            header="Asset Search"
            description="Search for assets by ID or Asset ID"

            :headers="[{
                name: 'ID',
                accessor: 'id'
            }, {
                name: 'Name',
                accessor: 'name'
            }
            ]"
            v-if="data && data?.data"
            :items="data.data ?? []"

        />
    </AppLayout>
</template>

<style scoped>
</style>
