<script setup>
import dayjs from 'dayjs';
import { computed } from "vue";
import { usePage, } from '@inertiajs/vue3';
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
        name: 'name',
        accessor:'name'
    },
    {
        name: 'amount',
        accessor: 'amount'
    },
    {
        name: 'date',
        accessor: (value) => dayjs(value.date)
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
    :items="page.props.transactions"
    header="All your transactions"
    description="Trans"
    />
      <pre>{{ page.props.transactions }}</pre>
    </div>
  </AppLayout>
</template>
