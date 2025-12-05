<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import axios from 'axios';
import { buildUrl } from '@kbco/query-builder';
import Manage from '@/Layouts/Manage.vue';
import CrudView from '@/Components/Spork/CrudView.vue';

const props = defineProps({
    description: {
        type: Object,
        default: () => ({}),
    },
    plural: {
        type: String,
        default: '',
    },
    singular: {
        type: String,
        default: '',
    },
    link: {
        type: String,
        default: '',
    },
    paginator: {
        type: Object,
        default: () => ({}),
    },
    apiLink: {
        type: String,
        default: '',
    },
});

const page = usePage();
const store = useStore();

const buildEmptyForm = () =>
    (props.description?.required ?? []).reduce((fields, field) => {
        fields[field] = '';
        return fields;
    }, {});

const form = ref(buildEmptyForm());
const records = ref(props.paginator?.data ?? []);
const pagination = ref({ ...(props.paginator ?? {}) });

if (pagination.value.data) {
    delete pagination.value.data;
}

const endpoint = computed(() => {
    if (props.apiLink) {
        return props.apiLink;
    }

    const normalizedLink = props.link?.startsWith('/') ? props.link : `/${props.link}`;
    return `/api/crud${normalizedLink}`;
});

const fillDefaultTeamAndUser = (payload) => {
    const working = { ...payload };
    const user = page.props.auth?.user ?? {};

    if (Object.prototype.hasOwnProperty.call(working, 'team_id')) {
        working.team_id = working.team_id ?? user.current_team_id;
    }

    if (Object.prototype.hasOwnProperty.call(working, 'user_id')) {
        working.user_id = working.user_id ?? user.id;
    }

    return working;
};

const clearForm = () => {
    Object.keys(form.value).forEach((key) => {
        form.value[key] = '';
    });
};

const fetchRecords = async ({ page: currentPage = 1, limit = 15, ...args } = {}) => {
    const { data: response } = await axios.get(
        buildUrl(endpoint.value, {
            page: currentPage,
            limit,
            ...args,
            include: [],
        })
    );

    const { data: dataset, ...meta } = response;
    records.value = dataset ?? [];
    pagination.value = meta;
};

const saveRecord = async (payload) => {
    const submission = fillDefaultTeamAndUser({ ...payload });

    if (!submission.id) {
        await axios.post(endpoint.value, submission);
    } else {
        await axios.put(`${endpoint.value}/${submission.id}`, submission);
    }

    await fetchRecords({ page: 1, limit: 15 });
    clearForm();
};

const destroyRecord = async (record) => {
    if (!record?.id) {
        return;
    }

    await axios.delete(`${endpoint.value}/${record.id}`);
    await fetchRecords({ page: 1, limit: 15 });
};

const executeAction = async ({ actionToRun, selectedItems }) => {
    if (!actionToRun?.url || !selectedItems?.length) {
        return;
    }

    await store.dispatch('executeAction', {
        url: actionToRun.url,
        data: {
            selectedItems,
        },
    });
};
</script>

<template>
    <Manage :title="plural" :sub-title="singular" home="/-/manage" content-width-class="max-w-3xl">
        <CrudView
            :form="form"
            :singular="singular"
            :data="records"
            :paginator="pagination"
            @destroy="destroyRecord"
            @index="fetchRecords"
            @execute="executeAction"
            @save="saveRecord"
        >
            <template #modal-title>
                <div class="text-base font-semibold text-stone-900 dark:text-stone-100">
                    Create a {{ singular.toLowerCase() }}
                </div>
            </template>

            <template #data="{ data }">
                <div class="flex flex-col gap-2 text-left">
                    <div class="text-lg font-semibold text-stone-900 dark:text-white">
                        <Link :href="`${link}/${data.id}`" class="hover:underline">
                            {{ data.name ?? `Record #${data.id}` }}
                        </Link>
                    </div>
                    <div class="text-xs text-stone-500 dark:text-stone-300 whitespace-pre-wrap break-words">
                        {{ data }}
                    </div>
                </div>
            </template>

            <template #no-data>No {{ plural }}</template>

            <template #form>
                <pre class="text-xs bg-stone-100 dark:bg-stone-900 rounded-lg p-4 overflow-auto">{{ description }}</pre>
            </template>
        </CrudView>
    </Manage>
</template>
