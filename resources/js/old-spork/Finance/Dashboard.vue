<template>
    <div class="flex flex-wrap mt-4">
        <div class="w-full py-2 px-4 text-2xl font-bold text-stone-800 dark:text-stone-200">Banking Dashboard</div>
        <div class="w-full pb-8 px-4 text-base font-base text-stone-500 dark:text-stone-300">Welcome Back, {{$store.getters.user.name}}!</div>

        <div class="grid grid-cols-4 gap-4 w-full px-4" v-if="$store.getters.allAccountsFromFeatures">
            <div class="min-w-1/4 flex-1" v-for="(account, i) in $store.getters.allAccountsFromFeatures?.slice(0, 8)" :key="'dashboard'+i">
                <div class="bg-white dark:bg-stone-600 shadow rounded-xl flex flex-col w-full p-4">
                    <div class="text-stone-600 dark:text-stone-300 dark:text-stone-300">{{account.name}}</div>

                    <div class="flex flex-col my-8 ">
                        <div class="text-3xl font-medium text-stone-900 dark:text-stone-100">
                            ${{account?.available?.toFixed(2)?.toLocaleString() ?? 0}}
                            <span class="text-base font-base">/ {{account?.balance?.toFixed(2)}}</span>
                        </div>
                        <div class="text-sm font-thin">available / balance</div>
                    </div>

                    <div class="w-full flex flex-wrap">
                        <time :datetime="account.updated_at" class="text-stone-500 dark:text-stone-400 text-sm">{{ dayjs(account.updated_at).format('lll') }}</time>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="$store.getters.allAccountsFromFeatures?.length > 8" class="text-right w-full pt-4 px-4">
            <a href="/finance/accounts">
                View all accounts
            </a>
        </div>

        <div class="w-1/2" v-if="$store.getters.allAccountsFromFeatures">
            <div class="m-4 text-xl font-medium">My Transactions</div>
            <div class="bg-white dark:bg-stone-600 shadow overflow-hidden sm:rounded-md m-4">
                <ul v-if="$store.getters.transactions.length > 0" role="list" class="divide-y divide-stone-200 h-96 overflow-y-scroll">
                    <li v-for="transaction in $store.getters.transactions" :key="transaction.transaction_id" class="flex justify-between px-4 py-2">
                        <div class="flex flex-col">
                            <div class="font-medium text-lg">
                                {{ transaction.name }}
                            </div>
                            <div class="text-sm text-stone-500  dark:text-stone-400">
                                {{ transaction.date }} - {{ transaction?.account?.name }}
                            </div>
                        </div>
                        <div>
                            <div class="text-lg font-bold" v-if="typeof transaction?.amount === 'number'">
                                ${{ transaction?.amount?.toFixed(2) }}
                            </div>
                        </div>
                    </li>
                </ul>
                <div v-else>
                    <div class="p-4">
                        <div class="text-sm italic text-stone-500 dark:text-stone-200">
                            No transactions found.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-1/2" v-if="!$store.getters.features?.finance">
            <div class="m-4 text-xl font-medium">You haven't set up finance</div>
            <div class="bg-white dark:bg-stone-600 shadow overflow-hidden sm:rounded-md m-4 p-4">
                To get started, you need to add a bank account. You can do this by navigating to your <router-link to="/finance/settings" class="underline text-blue-500 dark:text-blue-300">settings and linking through Plaid</router-link>
            </div>
        </div>

    </div>
</template>

<script>
import { CheckCircleIcon, ChevronRightIcon, MailIcon } from '@heroicons/vue/solid'
import CategoryIcon from "@components/CategoryIcon";

export default {
    name: "FinanceDashboard",
    components: {
        CheckCircleIcon,
        ChevronRightIcon,
        MailIcon,
        CategoryIcon,
    },
    setup() {
        return {
            dayjs
        }
    },
    async mounted() {
        await this.$store.dispatch('getFeatureLists', {
            feature: 'finance'
        })
        await this.$store.dispatch('getTransactions', this.$store.getters.accounts)
    }
}
</script>
