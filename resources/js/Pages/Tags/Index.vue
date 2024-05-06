<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import {
  TagIcon,
  ServerIcon,
  LinkIcon,
    UserIcon,
  BuildingLibraryIcon,
  WalletIcon,
  BoltIcon
} from "@heroicons/vue/24/outline";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkSelect from "@/Components/Spork/SporkSelect.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";
const page = usePage();

const { tags } = defineProps({
  // pagination
  tags: Object,
})

const { data, ...pagination } = tags;
const form = ref({
  name: '',
  conditions: [],
})

const comparators = [
  {
    "value": "EQUAL",
    "name": "equal to"
  },
  {
    "value": "NOT_EQUAL",
    "name": "not equal to"
  },
  {
    "value": "LIKE",
    "name": "like"
  },
  {
    "value": "NOTLIKE",
    "name": "not like"
  },
  {
    "value": "IN",
    "name": "in"
  },
  {
    "value": "NOTIN",
    "name": "not in"
  },
  {
    "value": "STARTS_WITH",
    "name": "starts with"
  },
  {
    "value": "ENDS_WITH",
    "name": "ends with"
  },
  {
    "value": "LESS_THAN",
    "name": "less than"
  },
  {
    "value": "LESS_THAN_EQUAL",
    "name": "less than equal"
  },
  {
    "value": "GREATER_THAN",
    "name": "greater than"
  },
  {
    "value": "GREATER_THAN_EQUAL",
    "name": "greater than equal"
  }
];

const events = [
  'App\\Events\\Models\\Finance\\TransactionCreated',
  'App\\Events\\Models\\MessageCreated',

  'App\\Events\\Finance\\BudgetSpendExceededLimit',
  'App\\Events\\Finance\\BudgetReset',

];
const round = ( value) => {
    return Math.round(value * 100) / 100;
}
const parameters = [
    // New Email
  {
    value: 'email.from',
    name: "Email Sender"
  },
  {
    value: 'email.subject',
    name: "Email Subject"
  },
    // New Transaction
  {
    "value": "transaction.name",
    "name": "Transaction Name"
  },
  {
    "value": "transaction.amount",
    "name": "Transaction Amount"
  },
    // Recent article
  {
    "value": "article.title",
    "name": "Article Title"
  },
];

const addCondition = () => {
  form.value.conditions.push({
    value: '',
    comparator: 'LIKE',
    parameter: 'name',
  })
};
const deleteCondition= () => {}
</script>

<template>
  <AppLayout title="Profile">
    <div>
      <div class="text-2xl my-8 mx-4">Tags and how they're used in your app</div>
      <div class="mx-4 my-2">
        <SporkInput v-model="form.name" type="input" placeholder="Laundry or Bills" />
      </div>
      <div class="mx-4 my-2 bg-stone-800 px-4 rounded-lg">

        <div class="w-full py-4 ">
          <div class="block uppercase tracking-wide text-xs font-bold mb-2">
            Conditions
            <div class="text-xs font-normal normal-case">
              Adding conditions to groups allows our system to automatically apply these groups to your transactions.
              Not adding any conditions will automatically apply the group to all transactions.
            </div>
          </div>

          <div class="flex w-full mt-4" v-for="condition in form.conditions">
            <div class="flex-1">
              <div class="block uppercase tracking-wide text-xs mb-2 font-semibold">
                Parameter
                <div class="text-xs font-normal tracking-tight">
                  The field who's value we will compare against
                </div>
              </div>
              <SporkSelect v-model="condition.parameter">
                <option v-for="parameter in parameters" :value="parameter.value">{{ parameter.name }}</option>
              </SporkSelect>
            </div>
            <div class="flex-1 ml-4">
              <div class="block uppercase tracking-wide text-xs mb-2 font-semibold">
                Comparator
                <div class="text-xs font-normal tracking-tight">
                  How we compare the parameter to the value
                </div>
              </div>

              <SporkSelect v-model="condition.comparator">
                <option v-for="comparator in comparators" :value="comparator.value">{{ comparator.name }}</option>
              </SporkSelect>
            </div>
            <div class="flex-1 ml-4">
              <div class="block uppercase tracking-wide text-xs mb-2 font-semibold">
                Value
                <div class="text-xs font-normal tracking-tight">
                  What we are comparing the transaction to
                </div>
              </div>
              <SporkInput v-model="condition.value" type="text" placeholder="STEAMGAMES.COM"/>
            </div>

            <div class="w-6 ml-2 flex justify-center items-end mb-1">
              <button class="text-red-600 h-6" @click.prevent="() => deleteCondition(condition)">
                <svg class="w-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
              </button>
            </div>
          </div>

          <button @click="addCondition" class="mt-4 px-2 py-1 text-sm focus:outline-none rounded-lg flex items-center hover:shadow">
            <span class="ml-2">Add condition</span>
          </button>
        </div>
      </div>
      <div class="gap-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 mx-4">
        <div v-for="tag in data" class="flex flex-col border border-slate-600 rounded-lg py-2">
          <div class="flex flex-wrap gap-4 items-center my-2 mx-6">
            <WalletIcon class="w-8 h-8 text-blue-400" v-if="tag.type === 'finance'"/>
            <ServerIcon class="w-8 h-8 text-amber-200" v-else-if="tag.type === 'server'"/>
            <BoltIcon class="w-8 h-8 text-green-500" v-else-if="tag.type === 'automatic'" />
            <TagIcon class="w-8 h-8 text-green-400" v-else />
            <span class="text-2xl">{{ tag.name?.en }}</span>
          </div>
          <div class="flex flex-wrap items-center gap-4 my-2 mx-6">
            <div class="flex gap-2">
              <ServerIcon class="w-6 h-6" />
              {{ tag.servers_count }}
            </div>
            <div class="flex gap-2">
              <LinkIcon class="w-6 h-6" />
              {{ tag.domains_count }}
            </div>
            <div class="flex gap-2">
              <UserIcon class="w-6 h-6" />
              {{ tag.people_count }}
            </div>
            <div class="flex gap-2">
              <WalletIcon class="w-6 h-6" />
                {{ tag.transactions_count }}
                ${{ round(tag.transactions_sum_amount) }}
            </div>
            <div class="flex gap-2">
              <DynamicIcon icon-name="EmailIcon" class="w-6 h-6" />
              {{ tag.messages_count }}
            </div>
          </div>
          <div class="mx-4">
            <div class="text-xs tracking-tight">
              <div v-for="condition in tag.conditions" :key="condition.id">
                <span class="tracking-wide" :title=" condition.parameter" v-text="condition.parameter"></span>
                {{ condition.comparator }} {{ condition.value }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>

</style>
