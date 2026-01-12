<script setup>
import dayjs from 'dayjs';
import Pill from '@/Components/Spork/Atoms/Pill.vue';
import TagPills from '@/Components/Spork/Molecules/Tags/TagPills.vue';
import TransactionPrivacyDetailRow from '@/Components/Spork/Finance/Transactions/TransactionPrivacyDetailRow.vue';

const props = defineProps({
    transaction: {
        type: Object,
        required: true,
    },
    onEditTags: {
        type: Function,
        default: null,
    },
});

const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
const dateFormat = (date) => dayjs(date).format('MMM DD, YYYY');
const privacyDetailsFor = (transaction) => transaction?.privacy_transactions ?? [];
const hasPrivacyDetails = (transaction) => privacyDetailsFor(transaction).length > 0;
</script>

<template>
    <tr
        :class="(hasPrivacyDetails(transaction) ? 'bg-stone-50/20 dark:bg-stone-950/40' : '')"
    >
        <td class="px-4 py-3 text-stone-900 dark:text-white">
            <div class="min-w-0 ml-5" :class="hasPrivacyDetails(transaction) ? '' : ''">
                <div class="font-medium truncate">
                    {{ transaction.name }}
                </div>
                <div class="mt-1 flex flex-wrap gap-2 items-center -mx-2">
                    <TagPills :tags="transaction.tags ?? []" :item-class="'mr-0'" />
                </div>
            </div>
        </td>
        <td class="px-4 py-3 text-stone-500 dark:text-stone-400">{{ transaction.account?.name }}</td>
        <td
            class="px-4 py-3 text-right font-semibold"
            :class="transaction.amount > 0 ? 'text-rose-500' : 'text-emerald-600 dark:text-emerald-400'"
        >
            {{ currency(transaction.amount) }}
        </td>
        <td class="px-4 py-3 text-stone-500 dark:text-stone-400">{{ dateFormat(transaction.date) }}</td>
        <td class="px-4 py-3 text-right">
            <button
                v-if="props.onEditTags"
                type="button"
                class="px-2 py-1 text-xs rounded-md border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
                @click="props.onEditTags(transaction)"
            >
                Edit tags
            </button>
        </td>
    </tr>

    <TransactionPrivacyDetailRow
        v-for="detail in privacyDetailsFor(transaction)"
        :key="`privacy-${transaction.id}-${detail.id}`"
        :transaction-id="transaction.id"
        :detail="detail"
    />
</template>


