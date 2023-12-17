<style scoped>

</style>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between mb-4">
            <h4 class="text-xl">Accounts</h4>
            <div class="flex flex-wrap justify-between">
                <button @click.prevent="open = true">
                    Create a manual account
                </button>
                <button @click="() => linkAccount()" class="text-white p-2 rounded shadow ml-4 bg-blue-500 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Link through Plaid
                </button>
            </div>
        </div>

        <div class="shadow p-4 rounded dark:bg-stone-600 bg-white">
            <div v-if="$store.getters.features?.finance" class="-mt-4">
                <div v-for="token in $store.getters.features?.finance" :key="'token'+token.id" class="flex flex-wrap mt-4">
                    <div class="text-stone-800 dark:text-stone-200 text-lg font-medium">
                        {{ token.name }}
                    </div>
                    <div class="flex w-full text-stone-500 dark:text-stone-300" v-if="token?.accounts?.length > 0">
                        <div class=" text-sm">{{ token.accounts.map(a => a.name).join(', ')}}</div>
                    </div>
                    <div class="flex w-full text-stone-500 dark:text-stone-400 text-sm italic" v-else>No accounts linked</div>
                </div>
            </div>
            <div v-else class="text-center italic">
                No accounts connected...
            </div>
        </div>
        <!-- This example requires Tailwind CSS v2.0+ -->
        <div v-if="open" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-stone-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-stone-600 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="text-left">
                            <h3 class="text-lg leading-6 font-medium text-stone-900 dark:text-stone-100" id="modal-title">
                                Name
                            </h3>
                            <div class="mt-2">
                                <input v-model="form.name" placeholder="Subscriptions" type="text" class="dark:placeholder-stone-300 dark:bg-stone-500 block max-w-lg w-full shadow-sm dark:text-stone-200 dark:border-stone-600 focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-stone-300 rounded-md"/>
                            </div>
                        </div>
                    </div>
                    <div class="my-4">
                        <div class="text-left">
                            <h3 class="text-lg leading-6 font-medium text-stone-900 dark:text-stone-100" id="modal-title">
                                Type
                            </h3>
                            <div class="mt-2">
                                <select v-model="form.type" class="block max-w-lg dark:bg-stone-500 w-full shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-stone-300 dark:border-stone-600 rounded-md text-white">
                                    <option value="checking">Checking</option>
                                    <option value="savings">Savings</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-left">
                            <h3 class="text-lg leading-6 font-medium text-stone-900 dark:text-stone-100">
                                Balance
                            </h3>
                            <div class="mt-2">
                                <input v-model="form.balance" placeholder="Subscriptions" type="text" class="dark:placeholder-stone-300 dark:bg-stone-500 block max-w-lg w-full shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:border-stone-600 border-stone-300 rounded-md"/>
                            </div>
                        </div>
                    </div>
                    <div class="my-4">
                        <div class="text-left">
                            <h3 class="text-lg leading-6 font-medium text-stone-900 dark:text-stone-100" id="modal-title">
                                Finance Account (Link)
                            </h3>
                            <div class="mt-2">
                                <select v-model="form.feature_list_id" class="block max-w-lg w-full dark:bg-stone-500 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-stone-300 dark:border-stone-600 rounded-md">
                                    <option disabled="true" value="-1">Select an account</option>
                                    <option v-for="account in $store.getters.features.finance" :key="account.account_id" :value="account.id">{{ account.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button
                            @click="createAccount"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white dark:bg-blue-600 dark:text-stone-50 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm"
                        >
                            Creat
                            <span class="mr-2" v-if="loading">ing</span>
                            <span class="mr-2" v-else>e</span>
                            New Account
                        </button>
                        <button @click="open = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-stone-300 shadow-sm px-4 py-2 bg-white dark:bg-stone-500 text-base font-medium text-stone-700 dark:text-stone-50 hover:bg-stone-50 dark:border-stone-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    data() {
        return {
            handler: null,
            open: false,
            loading: false,
            form: {
                name: '',
                type: 'checking',
                balance: 0.0,
                feature_list_id: null
            }
        }
    },
    methods: {
        date(date) {
            return dayjs(date);
        },
        async createAccount() {
            this.loading = true;
            await this.$store.dispatch('createAccount', this.form);
            await this.$store.dispatch('fetchFeatures');
            this.loading = false;
        },
        async setupPlaid(accessToken = null) {
            if (this.handler) {
                return;
            }
            const fetchLinkToken = async () => {
                const { data } = await axios.post(!accessToken ? '/api/plaid/create-link-token' : '/api/plaid/update-access-token',  accessToken ? {
                    access_token: accessToken?.id
                } : {});
                return data.link_token;
            };

            const configs = {
                token: await fetchLinkToken(),
                onSuccess: async (public_token, {institution: {institution_id}}) => {
                    if (accessToken) {
                        return;
                    }

                    const { data: token } = await axios.post('/api/plaid/exchange-token', {
                        public_token: public_token,
                        institution: institution_id
                    });

                    await this.$store.dispatch('fetchFeatures')
                },
                onExit: async function (err, metadata) {
                    if (err != null && err.error_code === 'INVALID_LINK_TOKEN') {
                        this.handler.destroy();
                        this.handler = Plaid.create({
                            ...configs,
                            token: await fetchLinkToken(),
                        });

                    }
                    if (err != null) {
                        return;
                    }
                },
            };
            this.handler = Plaid.create(configs)
        },
        async linkAccount() {
            await this.setupPlaid();
            this.handler.open();
        }
    },
    computed: {
        accessTokens() {
            return this.$store.getters.accessTokens
        }
    },
    mounted() {
    }
}
</script>
