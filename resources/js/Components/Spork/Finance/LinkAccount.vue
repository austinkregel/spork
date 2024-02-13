<template>
    <div>
        <div class="flex flex-wrap items-center justify-between mb-4">
            <h4 class="text-xl">Accounts</h4>
            <button @click="() => linkAccount()" class="text-white p-2 rounded shadow bg-blue-500 dark:bg-blue-700">
                Link new account
            </button>
        </div>
        <div v-if="accounts.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div v-for="account in accounts" class="p-4 dark:bg-stone-950 dark:text-white rounded">
                <div class="text-2xl">{{ account.name }}</div>
                <div>
                    <span class="text-xl">${{ account.available.toLocaleString() }}</span>
                    <span class="text-sm text-stone-300">/ ${{ account.balance.toLocaleString() }}</span>
                </div>
                <div class="text-xs text-stone-400">from {{account.credential.name}}</div>
            </div>
        </div>
        <div v-else class="shadow p-4 rounded dark:bg-stone-700 bg-white">
            <div class="text-center italic">
                No accounts connected...
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['accounts'],
        data() {
            return {
                handler: null,
            }
        },
        methods: {
            date(date) {
                return dayjs(date);
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

                        await this.$store.dispatch('refreshAccountsFor', { access_token_id: token.id })
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
                            console.log(err.message || err.error_code, this.$toasted);
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
    }
</script>
