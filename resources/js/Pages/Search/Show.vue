<template>
    <AppLayout :title="'Searching ' + table">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-6 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-sm font-medium uppercase tracking-wide text-stone-500 dark:text-stone-300" aria-label="Breadcrumb">
                <Link :href="route('search')+queryString" class="hover:text-stone-700 dark:hover:text-stone-100 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm">Search</Link>
                <span aria-hidden="true">&gt;</span>
                <Link :href="route('search.show', [table])" class="text-stone-700 dark:text-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm">{{ table }}</Link>
            </nav>

            <GlassTable
                :headers="headers"
                :items="data"
                :header="'Results for ' + queryString"
                :description="plural"
                empty-message="No results."
            >
                <template #pagination>
                    <div class="flex items-center justify-between">
                        <GlassButton
                            variant="secondary"
                            size="sm"
                            :disabled="!paginator?.prev_page_url"
                            :href="paginator?.prev_page_url ?? undefined"
                        >
                            Previous
                        </GlassButton>
                        <GlassButton
                            variant="secondary"
                            size="sm"
                            :disabled="!paginator?.next_page_url"
                            :href="paginator?.next_page_url ?? undefined"
                        >
                            Next
                        </GlassButton>
                    </div>
                </template>
            </GlassTable>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassTable from "@/Components/Glass/GlassTable.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from 'vue';
import dayjs from 'dayjs';

const { data, paginator, table, description, model } = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
    data: {
        type: Array
    },
    table: String,
    plural: String,
    model: String,
    description: Object,
});

const date = (d) => dayjs(d).format('YYYY-MM-DD HH:mm:ss');

const headers = computed(() => {
    switch (model) {
        case 'App\\Models\\User':
            return [
                {name: 'Name', accessor: (v) => v.name},
                {name: 'Email', accessor: (v) => v.email},
                {name: 'Created At', accessor: (v) => date(v.created_at)},
            ];
        case 'App\\Models\\Person':
            return [
                {name: 'Name', accessor: (v) => v.name},
                {name: 'Emails', accessor: (v) => v.emails},
                {name: 'Created At', accessor: (v) => date(v.created_at)},
            ];
        case 'App\\Models\\Domain':
            return [
                {name: 'Name', accessor: (v) => v.name},
                {name: 'Registered At', accessor: (v) => date(v.registered_at)},
                {name: 'Expires At', accessor: (v) => date(v.expires_at)},
            ];
        case 'App\\Models\\DomainRecord':
            return [
                {name: 'Name', accessor: (v) => v.name },
                {name: 'Type', accessor: (v) => v.type },
                {name: 'TTL', accessor: (v) => v.ttl },
                {name: 'Value', accessor: (v) => v.value },
                {name: 'Priority', accessor: (v) => v.priority },
            ];
        case 'App\\Models\\Article':
            return [
                { name: 'Headline', accessor: (v) => v.headline },
                { name: 'Last Modified', accessor: (v) => date(v.last_modified) },
            ]
        case 'App\\Models\\Tag':
            return [
                {name: 'Name', accessor: (v) => v.name},
                {name: 'Created At', accessor: (v) => date(v.created_at)},
            ];
        case 'App\\Models\\Finance\\Account':
            return [
                {
                    name: 'Name',
                    accessor: item => item.name
                },
                {
                    name: 'Balance',
                    accessor: item => item.balance
                },
                {
                    name: 'Available',
                    accessor: item => item.available
                },
                {
                    name: 'Date',
                    accessor: item => date(item.date)
                },
                {
                    name: 'Account ID',
                    accessor: item => item.account_id
                },
            ];
        case 'App\\Models\\Finance\\Transaction':
            return [
                {
                    name: 'Name',
                    accessor: item => item.name
                },
                {
                    name: 'Amount',
                    accessor: item => item.amount
                },
                {
                    name: 'Date',
                    accessor: item => date(item.date)
                },
                {
                    name: 'Account ID',
                    accessor: item => item.account_id
                },
            ];
        case 'App\\Models\\Message':
            return [
                {
                    name: 'From',
                    accessor: item => item.from?.name ?? item?.from_email
                },
                {
                    name: 'To',
                    accessor: item => item.to?.name ?? item?.to_email
                },
                {
                    name: 'Message',
                    accessor: item => item.message
                },
                {
                    name: 'Sent At',
                    accessor: item => date(item.sent_at)
                },
                {
                    name: 'Message ID',
                    accessor: item => item.event_id
                },
            ];
    }
})
const queryString = computed(() => {
    const url = new URL(window.location.href);
    return url?.search
});

</script>
