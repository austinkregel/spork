<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { usePage } from "@inertiajs/vue3";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import axios from "axios";
import { ref } from 'vue';

const page = usePage();

const transform = (configs) => {
    const result = {};
    for (const [section, values] of Object.entries(configs)) {
        if (typeof values === 'object') {
            result[section] = Object.entries(values)
                .filter(([_, v]) => typeof v !== 'object')
                .map(([k, v]) => ({ name: k, value: v }));
        }
    }
    return result;
};

const settings = ref(transform(page.props.settings.configs));

const save = () => {
    const payload = { configs: {} };
    for (const [section, fields] of Object.entries(settings.value)) {
        payload.configs[section] = {};
        for (const field of fields) {
            payload.configs[section][field.name] = field.value;
        }
    }

    axios.put(route('settings.update'), payload);
};
</script>

<template>
    <AppLayout>
        <template #default>
            <div class="mx-16 mt-8 space-y-8">
                <div v-for="(fields, section) in settings" :key="section">
                    <div class="text-2xl py-2">{{ section }}</div>
                    <div class="grid sm:grid-cols-4 gap-4">
                        <div v-for="(field, index) in fields" :key="index" class="sm:col-span-4">
                            <SporkDynamicInput
                                v-model="settings[section][index]"
                                :type="typeof field.value === 'boolean' ? 'checkbox' : 'text'"
                                :disabled-input="false"
                            />
                        </div>
                    </div>
                </div>
                <SporkButton class="mt-4" @click="save">Save</SporkButton>
            </div>
        </template>
    </AppLayout>
</template>

<style scoped>
</style>
