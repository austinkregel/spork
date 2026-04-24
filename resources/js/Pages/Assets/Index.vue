<script setup>
import { ref, computed } from 'vue';
import { router } from "@inertiajs/vue3";
import axios from 'axios';
import { buildUrl } from "@kbco/query-builder";
import AppLayout from "@/Layouts/AppLayout.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassTable from "@/Components/Glass/GlassTable.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassField from "@/Components/Glass/GlassField.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassModal from "@/Components/Glass/GlassModal.vue";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";

const props = defineProps({
    description: Object,
    assets: Object,
});

const DynamicFormToFillableArray = (model) =>
    Object.keys(model).map(key => ({
        value: typeof (model[key] ?? '') === 'object' ? JSON.stringify(model[key] ?? '') : (model[key] ?? null),
        name: key,
    }));

const form = ref(null);
const scannedInput = ref(null);
const data = ref(null);
const errors = ref(null);

const decodedValue = computed(() =>
    !isNaN(Number(scannedInput.value)) ? Number(scannedInput.value) : atob(scannedInput.value),
);

const search = () => {
    axios.get(buildUrl('/api/crud/assets', {
        filter: { id: decodedValue.value },
        include: ['owner'],
    })).then((response) => {
        data.value = response.data;
        if (data.value.data.length === 1) {
            form.value = DynamicFormToFillableArray(data.value.data[0]);
        }
    });
};

const onSave = async (fo, toggle) => {
    const payload = fo.reduce((all, { name, value }) => ({ ...all, [name]: value }), {});
    let url = '/api/crud/' + props.description.name;
    if (payload?.id) url += '/' + payload.id;

    axios[payload?.id ? 'put' : 'post'](url, payload).then(() => {
        router.reload({ only: ['data', 'paginator'] });
        toggle();
    }).catch((e) => {
        toggle();
        errors.value = e?.response?.data?.errors;
    });
};
</script>

<template>
    <AppLayout title="Assets">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard title="Asset lookup" subtitle="Scan or search">
                <GlassField label="Scan an asset ID or paste an encoded value">
                    <GlassInput
                        v-model="scannedInput"
                        placeholder="Search by ID or Asset ID"
                        @enter="search"
                    />
                </GlassField>
            </GlassCard>

            <GlassTable
                v-if="!form && data && data?.data"
                header="Asset Search"
                description="Search for assets by ID or Asset ID"
                :headers="[
                    { name: 'ID', accessor: 'id' },
                    { name: 'Name', accessor: 'name' },
                    { name: 'Owner', accessor: item => item?.owner?.name },
                ]"
                :items="data.data ?? []"
                empty-message="No assets matched."
            />

            <GlassTable
                header="Asset Details"
                description="Asset details"
                :headers="[
                    { name: 'ID', accessor: 'id' },
                    { name: 'Name', accessor: 'name' },
                    { name: 'Location', accessor: 'location' },
                    { name: 'Type', accessor: 'type' },
                    { name: 'Description', accessor: d => d.description ?? '' },
                    { name: 'Owner', accessor: item => item?.owner?.name },
                ]"
                :items="assets.data"
                empty-message="No assets yet."
            />
        </div>

        <GlassModal :open="!!form" title="Edit asset" size="md" @close="form = null">
            <div class="space-y-2">
                <div v-for="(field, i) in form" :key="i+'.form-value'">
                    <SporkDynamicInput
                        v-if="description.types[field.name]"
                        v-model="form[i]"
                        :autofocus="i === 1"
                        :type="description.types[field.name].type ?? 'text'"
                        :disabled-input="!description.fillable.includes(field.name)"
                        :editable-label="false"
                        :errors="errors?.[field.name]"
                    />
                </div>
            </div>
            <template #footer>
                <GlassButton variant="secondary" size="sm" @click="form = null">Cancel</GlassButton>
                <GlassButton size="sm" @click="onSave(form, () => form = null)">Apply</GlassButton>
            </template>
        </GlassModal>
    </AppLayout>
</template>
