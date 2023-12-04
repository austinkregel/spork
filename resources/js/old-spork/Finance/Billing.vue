<template>
    <div class="flex flex-wrap mt-4">
        <div class="w-full py-2 px-4 text-2xl font-bold text-gray-800 dark:text-gray-200 flex items-center justify-between">
            <span>Budgets Dashboard</span>
            <span>
                <feature-required feature="budgets" allow-more-than-one="true" />
            </span>
        </div>
        <div class="w-full pb-4 px-4 text-base font-base text-gray-500 dark:text-gray-300">Greetings! It's 2021-01-01. You should expect $420.20 to be withdrawn today.</div>
        <!-- The budgeting information -->
        <div class="w-full flex flex-wrap py-4 m-4 bg-white dark:bg-gray-600 rounded-lg shadow">
            <div class="w-full flex mx-4 rounded-full overflow-none bg-gray-200 my-2 text-white font-bold items-center">
                <div  v-for="(account, i) in $store.getters.allAccountsFromFeatures" :key="account.account_id+'progress'" :style="'width: '+Math.max(1, ((account.balance ?? 0)/(balance ?? 1)) * 100)+'%; '+(i === 0 ? 'border-radius: 50px 0 0 50px;': '')+' background: #' + account.account_id.substr(0, 6)" class="p-4 text-center"></div>

                <div :style="'width: '+((reservedAmount/balance) * 100)+'%;border-radius: 0 50px 50px 0;'" class="bg-yellow-500 p-4 text-center"></div>
            </div>
            <div class="w-full flex flex-col mx-4 gap-2 mt-2 pl-4">
                <div v-for="account in $store.getters.allAccountsFromFeatures" class="flex items-center gap-2" :key="account.account_id">
                    <span  :style="'background: #' + account.account_id.substr(0, 6)" class="p-2 w-4 h-4 rounded-full"></span>{{account.name}} (${{ account.balance ?? 0 }}/{{account.available ?? 0}})
                </div>

                <div class="flex items-center gap-2"><span class="bg-yellow-500 p-2 w-4 h-4 rounded-full"></span>Reserved for budgets/savings (${{ reservedAmount }})</div>
            </div>
        </div>

        <div class="w-full px-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-50">
                Month to date
            </h3>

            <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div v-for="item in stats" :key="item.name" class="px-4 py-5 bg-white dark:bg-gray-600 shadow rounded-lg overflow-hidden sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-300 truncate">
                    {{ item.name }}
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ item.stat }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="w-1/3">
            <div class="m-4 text-xl font-medium">Paid Bills</div>
            <div class="bg-white dark:bg-gray-600 shadow overflow-hidden sm:rounded-md m-4 px-4 py-2 flex flex-col max-h-4xl divide-y divide-gray-200 items-center">
                <div class="flex w-full items-center gap-2" v-for="transaction in paidBudgets" :key="transaction">
                    <div class="w-8">
                        <check-icon class="text-green-500 fill-current"></check-icon>
                    </div>

                    <div class="flex flex-wrap w-full items-center py-2">
                        <div class="w-full flex justify-between items-center">
                            <div class="font-medium">{{ transaction.name }}</div>
                            <div class="text-right text-gray-700 dark:text-gray-200 font-bold">${{ transaction.amount}}</div>
                        </div>

                        <div class="w-full flex justify-between items-center text-sm text-gray-600 dark:text-gray-300">
                            <div class="text-gray-500 dark:text-gray-300">{{ transaction.category }}</div>
                            <div class="text-right">{{ transaction.date }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="paidBudgets.length === 0">
                    <div class="text-center text-gray-500 dark:text-gray-300">No paid budgets</div>
                </div>
            </div>
        </div>
        <div class="w-1/3">
            <div class="m-4 text-xl font-medium">Pending Bills</div>
            <div class="bg-white dark:bg-gray-600 shadow overflow-hidden sm:rounded-md m-4 px-4 py-2 flex flex-col max-h-4xl divide-y divide-gray-200 items-center">
                <div class="flex w-full items-center gap-2" v-for="transaction in pendingBudgets" :key="transaction">
                    <div class="w-8">
                        <refresh-icon class="text-yellow-500 fill-current"></refresh-icon>
                    </div>

                    <div class="flex flex-wrap w-full items-center py-2">
                        <div class="w-full flex justify-between items-center">
                            <div class="font-medium">{{ transaction.name }}</div>
                            <div class="text-right text-gray-700 dark:text-gray-200 font-bold">${{ transaction.amount}}</div>
                        </div>

                        <div class="w-full flex justify-between items-center text-sm text-gray-600 dark:text-gray-300">
                            <div class="text-gray-500 dark:text-gray-300">{{ transaction.category }}</div>
                            <div class="text-right">{{ transaction.date }}</div>
                        </div>
                    </div>
                </div>
                <div v-if="pendingBudgets.length === 0">
                    <div class="text-center text-gray-500 dark:text-gray-300">No pending budgets</div>
                </div>
            </div>
        </div>
        <div class="w-1/3">
            <div class="m-4 text-xl font-medium">Future Bills</div>
            <div class="bg-white dark:bg-gray-600 shadow overflow-hidden sm:rounded-md m-4 px-4 py-2 flex flex-col max-h-4xl divide-y divide-gray-200 items-center">
               <div class="flex w-full items-center gap-2" v-for="transaction in futureBudgets" :key="transaction">
                    <div class="w-8">
                        <clock-icon class="text-yellow-600 fill-current"></clock-icon>
                    </div>

                    <div class="flex flex-wrap w-full items-center py-2">
                        <div class="w-full flex justify-between items-center">
                            <div class="font-medium">{{ transaction.name }}</div>
                            <div class="text-right text-gray-700 dark:text-gray-200 font-bold">${{ transaction.settings.amount}}</div>
                        </div>

                        <div class="w-full flex justify-between items-center text-sm text-gray-600 dark:text-gray-300">
                            <div class="text-gray-500 dark:text-gray-300">{{ transaction.tags?.map(tag => tag?.en ?? '')?.join(', ') }}</div>
                            <div class="text-right">{{ transaction.repeatable }}</div>
                        </div>
                    </div>
                </div>
                <div v-if="futureBudgets.length === 0">
                    <div class="text-center text-gray-500 dark:text-gray-300">No future budgets</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {
    CheckCircleIcon,
    ChevronRightIcon,
    MailIcon,
    CheckIcon,
    RefreshIcon,
    ClockIcon,
    UsersIcon,
    MailOpenIcon,
    CursorClickIcon,
} from '@heroicons/vue/solid'
import CategoryIcon from "@components/CategoryIcon";

export default {
    name: "FinanceDashboard",
    components: {
        CheckCircleIcon,
        ChevronRightIcon,
        MailIcon,
        CategoryIcon,
        CheckIcon,
        RefreshIcon,
        ClockIcon
    },
    setup() {
        return {
            dayjs,
        }
    },
    methods: {
        transactionIsInBudgets(transaction) {
            return this.budgets.map(budget => budget.name).includes(transaction.name)
        },
        budgetInTransactions(budget) {
            return this.transactions.filter(transaction => transaction.name === budget.name).length > 0
        },
    },
    computed: {
        // Paid budgets
        paidBudgets() {
            return this.transactions.filter(transaction => this.transactionIsInBudgets(transaction) && !transaction.pending)
        },
        // Budgets that are past due, but not paid or pending
        pendingBudgets() {
            return this.transactions.filter(transaction => this.transactionIsInBudgets(transaction) && transaction.pending);
        },
        futureBudgets() {
            return this.budgets?.filter(budget => !this.budgetInTransactions(budget)) ?? [];
        },
        paidAmount() {
            return this.paidBudgets.map(transaction => transaction.amount).reduce((a, b) => a + b, 0);
        },
        reservedAmount() {
            return this.pendingBudgets.map(transaction => transaction.amount ?? 0).reduce((a, b) => Number(a) + Number(b), 0) + this.futureBudgets.map(budget => budget.settings?.amount ?? 0).reduce((a, b) => Number(a) + Number(b), 0);
        },
        availableAmount() {
            return this.$store.getters.allAccountsFromFeatures?.map(account => account.available ?? 0).reduce((sum, amount) => sum + amount, 0) ?? 0;
        },
        balance() {
            return this.$store.getters.allAccountsFromFeatures?.map(account => account.balance ?? 0).reduce((sum, amount) => sum + amount, 0) ?? 0;
        },
        stats() {
            return [
                { id: 1, name: 'Total Balance', stat: '$'+Number(this.balance).toLocaleString(), icon: UsersIcon },
                { id: 2, name: 'Budgets that need to be paid', stat: '$' + this.reservedAmount, icon: MailOpenIcon },
                { id: 3, name: 'Available to spend', stat: '$'+Number(this.availableAmount).toLocaleString(), icon: CursorClickIcon },
            ];
        },
        budgets() {
            return this.$store.getters?.features?.budgets ?? [];
        },
        transactions() {
            return this.$store.getters.transactions;
        }
    },
    mounted() {
        this.$store.dispatch('getTransactions', this.$store.getters.accounts)
    }
}
</script>
