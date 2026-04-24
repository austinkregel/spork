<script setup>
import { ref } from 'vue';
import TransactionRow from '@/Components/Spork/Finance/Transactions/TransactionRow.vue';
import TransactionTagsModal from '@/Components/Spork/Finance/Transactions/TransactionTagsModal.vue';

const props = defineProps({
    transactions: {
        type: Array,
        default: () => [],
    },
    tags: {
        type: Array,
        default: () => [],
    },
});

const tagModal = ref(null);
const openTagEditor = (transaction) => tagModal.value?.open(transaction);
</script>

<template>
    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 overflow-x-auto shadow-sm">
        <table class="min-w-full divide-y divide-stone-100 dark:divide-stone-800 text-sm">
            <thead class="bg-stone-50 dark:bg-stone-900 text-stone-500 dark:text-stone-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Account</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                <template v-for="transaction in props.transactions" :key="transaction.id">
                    <TransactionRow :transaction="transaction" :on-edit-tags="openTagEditor" />
                </template>

                <tr v-if="props.transactions.length === 0">
                    <td colspan="5" class="px-4 py-6 text-center text-stone-500 dark:text-stone-400">
                        No transactions found.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <TransactionTagsModal ref="tagModal" :tags="props.tags" />
</template>


