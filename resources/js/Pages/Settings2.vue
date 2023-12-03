<script setup>
import { ref} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import {usePage} from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";

defineProps({
    config: Object
})
const page = usePage();

const form = ref({
    ...(page?.props?.env ?? {}),
    errors: []
});

</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-8">
                <div v-for="group in page.props.config" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg grid grid-cols-3">
                    <div class="col-span-1 px-4 pt-8 text-white">
                        config/{{ group.name }}
                    </div>
                    <div class=" col-span-2">
                        <div v-for="(value, key) in group.env" class=" flex flex-col p-3 w-full">
                            <InputLabel :for="key" :value="key" />
                            <TextInput
                                :id="key"
                                v-model="form[key]"
                                :placeholder="value"
                                type="text"
                                class="mt-1 block w-full"
                                autocomplete="current-password"
                            />
                            <InputError :message="form.errors[key] ?? ''" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
