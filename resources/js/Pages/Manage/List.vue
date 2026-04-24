<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { buildUrl } from '@kbco/query-builder';
import DynamicIcon from "@/Components/DynamicIcon.vue";
import CrudView from "@/Components/Spork/CrudView.vue";
import Manage from "@/Layouts/Manage.vue";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import GlassPill from "@/Components/Glass/GlassPill.vue";

const props = defineProps({
    data: Array,
    title: String,
    paginator: Object,
    description: Object,
    singular: String,
    plural: String,
    link: String,
    body: String,
    apiLink: String,
    metrics: Array,
});

const FillableArrayToDynamicForm = (fillable) =>
    fillable.map(value => ({ value: '', name: value, type: 'text' }));

const DynamicFormToFillableArray = (model) =>
    Object.keys(model).map(key => ({
        value: typeof (model[key] ?? '') === 'object' ? JSON.stringify(model[key] ?? '') : (model[key] ?? ''),
        name: key,
    }));

const formatDateIso = (date) => dayjs(date).format('YYYY-MM-DD HH:mm:ss');

const form = ref(FillableArrayToDynamicForm(props.description.fillable));
const errors = ref(null);

const fetchData = async (options) => {
    await axios.get(buildUrl(props.apiLink, {
        page: 1,
        limit: 15,
        ...(options ?? {}),
    }));
    router.reload({ only: ['data', 'paginator'] });
};

const tagTone = (type) => {
    switch (type) {
        case 'finance': return 'info';
        case 'server': return 'warning';
        case 'automatic':
        default: return 'success';
    }
};

const onDelete = (data) => {
    axios.delete('/api/crud/' + props.plural + '/' + data.id).finally(() => {
        router.reload({ only: ['data', 'paginator'] });
    });
};

const onDeleteMany = (manyData) => {
    axios.post('/api/crud/' + props.plural + '/delete-many', {
        items: manyData.map(item => item.id),
    }).finally(() => {
        router.reload({ only: ['data', 'paginator'] });
    });
};

const onExecute = async ({ selectedItems, actionToRun, next }) => {
    await axios.post('/api/actions/' + actionToRun.slug, {
        items: selectedItems.map(item => item.id),
    });
    next();
};

const onSave = async (formData, toggle) => {
    const payload = formData.reduce((all, { name, value }) => ({ ...all, [name]: value }), {});
    let url = '/api/crud/' + props.plural;
    if (payload?.id) url += '/' + payload.id;

    axios[payload?.id ? 'put' : 'post'](url, payload).then(() => {
        router.reload({ only: ['data', 'paginator'] });
        toggle();
    }).catch((e) => {
        toggle();
        errors.value = e?.response?.data?.errors;
    });
};

const possibleDescriptionForData = (data) => {
    const fieldsToUse = props.description?.fields?.filter(field => ![
        'id', 'name', 'user_id', 'created_at', 'updated_at', 'icon', 'href', 'order', 'value,',
    ]?.includes(field) && !field.endsWith('_id') && typeof data[field] !== 'boolean')
        .filter(field => data[field]);

    if (props.description?.fields?.includes('cloudflare_id')) {
        return data.cloudflare_id;
    }
    return data[fieldsToUse[0] ?? 0] ?? '';
};
</script>

<template>
    <Manage :title="title" sub-title="Manage" home="/-/manage">
        <crud-view
            :form="form"
            :singular="singular"
            :plural="plural"
            :description="description"
            @destroy="onDelete"
            @destroy-many="onDeleteMany"
            @index="fetchData"
            @execute="onExecute"
            @save="onSave"
            @clear-form="() => { form = FillableArrayToDynamicForm(description.fillable); }"
            :api-link="apiLink"
            :data="data"
            :paginator="paginator"
        >
            <template #modal-title>
                <div>Upsert {{ singular }}</div>
            </template>
            <template #data="{ data, openModal }">
                <div class="relative z-0 grid w-full grid-cols-6">
                    <div class="col-span-5">
                        <div class="flex flex-col">
                            <Link :href="route('manage.show', [plural])" class="flex items-center gap-2 text-base font-semibold text-stone-900 dark:text-stone-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm">
                                <img v-if="data?.personal_finance_icon" :src="data?.personal_finance_icon" class="h-5 w-5" alt="">
                                {{ data.name }}
                            </Link>
                            <div class="mt-1 flex flex-col gap-2">
                                <div class="text-xs text-stone-500 dark:text-stone-400">
                                    {{ possibleDescriptionForData(data) }}
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <GlassPill v-for="tag in data?.tags" :key="tag.name" size="sm" :tone="tagTone(tag.type)">
                                        {{ tag.name.en }}
                                    </GlassPill>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-1 flex items-center justify-end gap-3 px-4">
                        <button type="button" class="text-stone-500 hover:text-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm" aria-label="Edit" @click="form = DynamicFormToFillableArray(data); openModal();">
                            <DynamicIcon icon-name="PencilIcon" class="h-5 w-5 text-emerald-500" />
                        </button>
                        <button type="button" class="text-stone-500 hover:text-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded-sm" aria-label="Delete" @click="onDelete(data)">
                            <DynamicIcon icon-name="TrashIcon" class="h-5 w-5 text-red-500" />
                        </button>
                    </div>
                </div>
            </template>
            <template #no-data>
                <div class="w-full px-4 py-4 text-center italic text-stone-500 dark:text-stone-400">No {{ singular }} data</div>
            </template>

            <template #form>
                <div class="-mt-4 flex flex-col">
                    <div v-for="(field, i) in form" :key="i+'.form-value'">
                        <SporkDynamicInput
                            v-if="description.types[field.name]"
                            v-model="form[i]"
                            :type="description.types[field.name].type ?? 'text'"
                            :disabled-input="!description.fillable.includes(field.name)"
                            :editable-label="false"
                            :errors="errors?.[field.name]"
                            class="mt-4"
                        />
                    </div>
                </div>
            </template>
        </crud-view>
    </Manage>
</template>
