<script setup>
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc.js';
import { computed } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import LinkAccount from "@/Components/Spork/Finance/LinkAccount.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassTable from "@/Components/Glass/GlassTable.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";

const page = usePage();
dayjs.extend(utc);

const accounts = computed(() => page.props.accounts);

const transactionHeaders = [
    { name: 'Name', accessor: 'name' },
    {
        name: 'Amount',
        accessor: value => value?.amount ? value.amount.toLocaleString('en-US', { style: 'currency', currency: 'USD' }) : null,
        align: 'right',
    },
    {
        name: 'Date',
        accessor: (value) => value?.date ? dayjs.utc(value.date).format("MMM DD, YYYY") : null,
    },
    {
        name: 'Tags',
        accessor: value => value?.tags?.map(tag => tag.name.en)?.join(', '),
    },
];
</script>

<template>
    <AppLayout title="Banking">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard
                title="Banking"
                subtitle="Link your account, and tag your transactions"
            />

            <LinkAccount :accounts="accounts" />

            <GlassTable
                header="All your transactions"
                description="Transactions"
                :headers="transactionHeaders"
                :items="page.props.transactions.data"
                empty-message="No transactions yet."
            >
                <template #pagination>
                    <div class="flex items-center justify-between">
                        <GlassButton
                            variant="secondary"
                            size="sm"
                            :disabled="!page.props?.transactions?.prev_page_url"
                            :href="page.props?.transactions?.prev_page_url ?? undefined"
                        >
                            Previous
                        </GlassButton>
                        <GlassButton
                            variant="secondary"
                            size="sm"
                            :disabled="!page.props?.transactions?.next_page_url"
                            :href="page.props?.transactions?.next_page_url ?? undefined"
                        >
                            Next
                        </GlassButton>
                    </div>
                </template>
            </GlassTable>
        </div>
    </AppLayout>
</template>
