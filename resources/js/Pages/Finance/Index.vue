<script setup>
import dayjs from 'dayjs';
import { computed } from "vue";
import { usePage, Link } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import {
  TagIcon,
  ServerIcon,
  LinkIcon,
    UserIcon,
  BuildingLibraryIcon,
  WalletIcon
} from "@heroicons/vue/24/outline";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import LinkAccount from "@/Components/Spork/Finance/LinkAccount.vue";
import SporkTable from "@/Components/Spork/Molecules/SporkTable.vue";
const page = usePage();

const accounts = computed(() => page.props.accounts)

const transactionHeaders = [
    {
        name: 'Name',
        accessor:'name'
    },
    {
        name: 'Amount',
        accessor: 'amount'
    },
    {
        name: 'Date',
        accessor: (value) => value?.date ? dayjs(value.date) : null
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
      />
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
