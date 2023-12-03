<template>
    <div class="m-4">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-50">Accounts</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-200">
                        This is where you can manage your accounts. CSVs must have the following columns: <code>account_id, and type</code>, the rest are technically optional.
                    </p>
                </div>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div v-for="(tokens, $i) in [$store.getters.manualFinance].filter(v => v)" :key="$i" class="mx-4">
                    <div class="w-full text-gray-600 uppercase">{{ tokens.name }}</div>
                    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" v-if="tokens?.accounts?.length > 0">
                        <div v-for="item in tokens?.accounts" :key="item.id" class="relative pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden bg-white dark:bg-gray-600">
                            <dt>
                                <div class="absolute bg-blue-500 rounded-md p-3">
                                    <building-office-icon class="h-6 w-6 text-white" aria-hidden="true" />
                                </div>
                                <p class="ml-16 text-sm font-medium text-gray-500 truncate">{{ item.name }}</p>
                            </dt>
                            <dd class="ml-16 pb-6 flex items-baseline sm:pb-7">
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ item.balance }}
                                </p>
                                <div class="absolute bottom-0 inset-x-0 bg-gray-5000 px-4 py-4 sm:px-6 flex justify-between items-center">
                                    <div class="text-sm">
                                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                                            View<span class="sr-only"> {{ item.name }} transactions</span></a
                                        >
                                    </div>
                                    <div>
                                        <button @click.prevent="() => deleteAccount(item)">
                                            <trash-icon class="h-6 w-6 text-gray-400 hover:text-red-500" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                            </dd>
                        </div>
                    </dl>
                    <div v-else class="p-4 bg-white dark:bg-gray-600 italic text-gray-600 dark:text-gray-200 shadow rounded mt-2">
                        No accounts found.
                    </div>
                </div>

                <HeaderMapping
                    :value="account_mapping"
                    @input="(newMapping) => mapping = newMapping.value"
                    @save="(d) => saveMapping(d)"
                    label="Update your Accounts with a CSV"
                >

                    <div class="my-4">
                        <div class="text-left">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="account_selector">
                                Finance Account (Link)
                            </label>
                            <div class="mt-2">
                                <select v-model="link_id" class="placeholder-gray-400 block max-w-lg w-full shadow-sm bg-white dark:bg-gray-500 dark:border-gray-500 focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300 rounded-md">
                                    <option :value="null" disabled="true">Select a bank link</option>
                                    <option v-for="account in $store.getters.features.finance" :key="account.account_id" :value="account.id">{{ account.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </HeaderMapping>

                <div class="mx-4">
                    <link-account />
                </div>
            </div>
        </div>

        <div class="hidden sm:block" aria-hidden="true">
            <div class="py-5">
                <div class="border-t border-gray-200 dark:border-gray-600" />
            </div>
        </div>
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-50">Transactions</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        You can upload your transactions here.
                    </p>
                </div>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <HeaderMapping
                    :value="transaction_mapping"
                    @input="(newMapping) => mapping = newMapping.value"
                    @save="(d) => saveTransaction(d)"
                    label="Update your account with transactions from a CSV"
                >
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="account_selector">
                            Account associated with these transactions
                        </label>
                        <select v-model="account_id" id="account_selector" class="placeholder-gray-400 mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-500 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <optgroup v-for="token in ($store.getters.features?.finance ?? [])" :label="token.name">
                                <option :value="null" disabled="true">Select an account</option>
                                <option v-for="account in token.accounts" :key="account.id" :value="account.account_id">{{ account.name }} &mdash; {{account?.feature?.name}}</option>
                            </optgroup>
                        </select>
                        <div class="mt-4">
                            <label>
                                <input v-model="invert_values" type="checkbox"> Invert Values (useful for USAA)
                            </label>
                        </div>
                    </div>

                </HeaderMapping>
            </div>
        </div>
    </div>
</template>

<script>
import {HomeIcon, TrashIcon, BuildingOfficeIcon } from "@heroicons/vue/24/outline";

import HeaderMapping from "@/Components/HeaderMapping.vue";
import LinkAccount from "@/Components/LinkAccount.vue";

export default {
    name: "Finance.Settings",
    components: {
        HeaderMapping,
        BuildingOfficeIcon,
        TrashIcon,
        LinkAccount,
    },
    data() {
        return {
            account_mapping: [
                {name: 'account_id', key: 'account_id', value: ''},
                {name: 'mask', key: 'mask', value: ''},
                {name: 'name', key: 'name', value: ''},
                {name: 'official_name', key: 'official_name', value: ''},
                {name: 'balance', key: 'balance', value: ''},
                {name: 'available', key: 'available', value: ''},
                {name: 'subtype', key: 'subtype', value: ''},
                {name: 'type', key: 'type', value: ''},
            ],
            transaction_mapping: [
                {name: 'Transaction ID', key: 'transaction_id', value: ''},
                {name: 'Name', key: 'name', value: ''},
                {name: 'Ammount', key: 'amount', value: ''},
                {name: 'Date', key: 'date', value: ''},
                {name: 'Pending', key: 'pending', value: ''},
                {name: 'type', key: 'type', value: ''},
            ],
            invert_values: false,
            account_id: null,
            link_id: null,
            open: false,
        };
    },
    methods: {
        async saveTransaction(d) {
            console.log('saving transactions')
            const manualAccount = this.$store.getters.manualFinance;

            if (!manualAccount) {
                console.error("NO manual feature list set up for handling manual accounts.")
                return;
            }

            var formData = new FormData();
            var imagefile = d.file.target;
            formData.append("image", imagefile.files[0]);
            formData.append("mapping", JSON.stringify(d.mapping));
            formData.append("account_id", this.account_id)
            formData.append('invert_values', this.invert_values);

            const { data } = await axios.post('/api/upload-transactions', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })

            await this.$store.dispatch('getFeatureLists', {
                include: 'accounts'
            });

        },
        async saveMapping(d) {
            var formData = new FormData();
            var imagefile = d.file.target;
            formData.append("image", imagefile.files[0]);
            formData.append("mapping", JSON.stringify(d.mapping));
            formData.append("feature_list_id", this.link_id)
            const { data } = await axios.post('/api/upload-accounts', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })

            await this.$store.dispatch('getFeatureLists', {
                include: 'accounts'
            });

        },
        deleteAccount(account) {
            axios.delete('/api/account/' + account.id).then(() => {
                this.$store.dispatch('getFeatureLists', {
                    include: 'accounts'
                });
            });
        }
    },
    computed: {
        routes() {
            return [
                {
                    name: 'Home',
                    href: '#',
                    icon: HomeIcon,
                    current: false,
                },
            ]
        }
    },
    mounted() {

    }
}

</script>

<style scoped>

</style>
