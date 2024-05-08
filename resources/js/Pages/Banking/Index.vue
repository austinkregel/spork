<script setup>
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc.js';
import { computed } from "vue";
import { usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import LinkAccount from "@/Components/Spork/Finance/LinkAccount.vue";
import SporkTable from "@/Components/Spork/Molecules/SporkTable.vue";
const page = usePage();
dayjs.extend(utc);
const accounts = computed(() => page.props.accounts)

const transactionHeaders = [
    {
        name: 'Name',
        accessor:'name'
    },
    {
        name: 'Amount',
        accessor: value => value?.amount ? value.amount.toLocaleString('en-US', {style: 'currency', currency: 'USD'}) : null
    },
    {
        name: 'Date',
        accessor: (value) => value?.date ? dayjs.utc(value.date).format("MMM DD, YYYY") : null
    },
    {
        name : 'Tags',
        accessor: value => value?.tags?.map(tag => tag.name.en)?.join(', ')
    }
]

</script>

<template>
  <AppLayout title="Profile">
    <div>
      <div class="text-2xl my-4 w-full flex flex-col px-4">
        Banking
        <span class="text-xs">Link your account, and tag your transactions</span>
      </div>

      <div class="px-4 ">
        <LinkAccount :accounts="accounts" />
      </div>
      <SporkTable
        :headers="transactionHeaders"
        :items="page.props.transactions.data"
        header="All your transactions"
        description="Transactions"
      >
        <template #context-items="{ item }">
          <div class="p-2 flex-col flex gap-1">
            <div class="text-xs">
              <span class="uppercase font-bold tracking-widest text-stone-900 dark:text-stone-300">for:</span>
              {{  item.name }}
            </div>
            <div>Link to more by name</div>
            <div>Link to more by date</div>

            <div v-for="tag in item.tags" :key="tag">Others with tag: {{ tag.name.en }} </div>
          </div>
        </template>
      </SporkTable>
        <div class="flow-root">
            <div class="flex justify-between mx-8 -mt-4">

            <Link class="text-white border px-4 py-2 rounded"
                  :class="[page.props?.transactions?.prev_page_url ? 'border-stone-300 dark:border-stone-600' : 'border-stone-300 dark:border-stone-700 bg-stone-200 dark:bg-stone-800/70 text-stone-100/50']"
                  :disabled="!page.props?.transactions?.prev_page_url"
                  :href="page.props?.transactions?.prev_page_url"
            >
                Previous</Link>
            <Link class="text-white border px-4 py-2 rounded"
                  :class="[page.props?.transactions?.next_page_url ? 'border-stone-300 dark:border-stone-600' : 'border-stone-300 dark:border-stone-700 bg-stone-200 dark:bg-stone-800/70 text-stone-100/50']"
                  :disabled="!page.props?.transactions?.next_page_url"
                  :href="page.props?.transactions?.next_page_url"
            >
                Next</Link>
        </div>
        </div>
    </div>
  </AppLayout>
</template>
