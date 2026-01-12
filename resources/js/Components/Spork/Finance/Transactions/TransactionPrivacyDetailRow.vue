<script setup>
import dayjs from 'dayjs';
import TagPills from '@/Components/Spork/Molecules/Tags/TagPills.vue';

const props = defineProps({
    transactionId: {
        type: [String, Number],
        required: true,
    },
    detail: {
        type: Object,
        required: true,
    },
});

const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
const privacyAmount = (detail) => Number((detail?.amount_cents ?? 0) / 100);
const dateFormat = (date) => dayjs(date).format('MMM DD, YYYY');
</script>

<template>
    <tr
        :key="`privacy-${transactionId}-${detail.id}`"
        class="bg-indigo-50/25 dark:bg-indigo-500/5"
    >
        <td class="py-2 text-stone-700 dark:text-stone-200 border-l-4 border-indigo-300 dark:border-indigo-500/40">
            <div class="flex items-start">
                <div class="relative w-8 shrink-0">
                    <span class="absolute left-1/2 top-2 -translate-x-1/2 h-2 w-2 rounded-full bg-stone-300 dark:bg-stone-700" />
                </div>
                <div class="min-w-0">
                    <div class="font-medium truncate">
                        {{ detail.memo || detail.descriptor || 'Privacy transaction' }}
                    </div>
                    <div class="text-xs text-stone-500 dark:text-stone-400">
                        <span v-if="detail.status">{{ detail.status }}</span>
                        <span v-if="detail.result" class="ml-2">{{ detail.result }}</span>
                        <span v-if="detail.mcc" class="ml-2">MCC {{ detail.mcc }}</span>
                        <span v-if="detail.pivot?.match_method" class="ml-2">({{ detail.pivot.match_method }})</span>
                    </div>
                    <div v-if="(detail?.tags ?? []).length" class="mt-1">
                        <TagPills :tags="detail?.tags ?? []" :item-class="'mr-0'" />
                    </div>
                </div>
            </div>
        </td>
        <td class="px-4 py-2 text-stone-500 dark:text-stone-400" />
        <td class="px-4 py-2 text-right font-semibold text-stone-700 dark:text-stone-200">
            {{ currency(privacyAmount(detail)) }}
        </td>
        <td class="px-4 py-2 text-stone-500 dark:text-stone-400">
            {{ dateFormat(detail.date_authorized) }}
        </td>
    </tr>
</template>


