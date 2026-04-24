<template>
    <AppLayout title="Credentials">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-stone-800 dark:text-stone-200">
                Credentials
            </h2>
        </template>
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <GlassSurface class="overflow-hidden">
                <crud-view
                    :form="form"
                    singular="Credential"
                    @destroy="onDelete"
                    @index="({ page, limit, ...args}) => fetch({ page, limit, ...args })"
                    @execute="onExecute"
                    @save="save"
                    :save="save"
                    :data="data"
                    :paginator="pagination"
                >
                    <template #modal-title>Create a credential</template>
                    <template v-slot:data="{ data }">
                        <div class="flex flex-col">
                            <div class="text-left text-base font-semibold text-stone-900 dark:text-stone-50">{{ data.name }}</div>
                            <div class="flex flex-wrap gap-2">
                                <span class="text-xs text-stone-500 dark:text-stone-400">{{ data.type }} / {{ data.service }}</span>
                            </div>
                        </div>
                    </template>
                    <template #no-data>No credentials</template>

                    <template #form>
                        <div class="grid grid-cols-6 gap-4">
                            <div class="col-span-6">
                                <glass-field label="Name">
                                    <glass-input v-model="form.name" name="name" />
                                </glass-field>
                            </div>

                            <div class="col-span-6">
                                <glass-field label="Type">
                                    <glass-select v-model="form.type" name="type">
                                        <option value="">Select one</option>
                                        <option v-for="type in ['domain','registrar', 'ssh','development']" :key="type" :value="type">{{ type }}</option>
                                    </glass-select>
                                </glass-field>
                            </div>

                            <div class="col-span-6">
                                <glass-field label="Service">
                                    <glass-select v-model="form.service" name="service">
                                        <option value="">Select one</option>
                                        <option v-for="service in ['namecheap', 'forge', 'cloudflare', 'digitalocean']" :key="service" :value="service">{{ service }}</option>
                                    </glass-select>
                                </glass-field>
                            </div>

                            <div class="col-span-6">
                                <glass-field label="API key / Access Token">
                                    <glass-input v-model="form.access_token" type="password" name="access_token" autocomplete="off" />
                                </glass-field>
                            </div>

                            <template v-if="form.service === 'namecheap'">
                                <div class="col-span-6">
                                    <glass-field label="API User">
                                        <glass-input v-model="form.settings.api_user" name="api_user" autocomplete="off" />
                                    </glass-field>
                                </div>
                                <div class="col-span-6">
                                    <glass-field label="Username">
                                        <glass-input v-model="form.settings.username" name="username" autocomplete="off" />
                                    </glass-field>
                                </div>
                                <div class="col-span-6">
                                    <glass-field label="Client IP">
                                        <glass-input v-model="form.settings.client_ip" name="client_ip" autocomplete="off" />
                                    </glass-field>
                                </div>
                            </template>

                            <template v-if="form.service === 'cloudflare'">
                                <div class="col-span-6">
                                    <glass-field label="Account Email">
                                        <glass-input v-model="form.settings.email" name="account_email" autocomplete="off" />
                                    </glass-field>
                                </div>
                                <div class="col-span-6">
                                    <glass-field label="Account ID">
                                        <glass-input v-model="form.settings.account_id" name="account_id" autocomplete="off" />
                                    </glass-field>
                                </div>
                            </template>

                            <template v-if="form.type === 'ssh'">
                                <div class="col-span-6">
                                    <glass-field label="SSH Private Key">
                                        <glass-input ref="private_key" type="file" name="private_key" @change="onFileUploadForPrivateKey" />
                                    </glass-field>
                                </div>
                                <div class="col-span-6">
                                    <glass-field label="SSH Public Key">
                                        <glass-input ref="public_key" type="file" name="public_key" @change="onFileUploadForPublicKey" />
                                    </glass-field>
                                </div>
                            </template>
                        </div>
                    </template>
                </crud-view>
            </GlassSurface>
        </div>
    </AppLayout>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import AppLayout from '@/Layouts/AppLayout.vue';
import CrudView from "@/Components/Spork/CrudView.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassSelect from "@/Components/Glass/GlassSelect.vue";
import GlassField from "@/Components/Glass/GlassField.vue";
import { buildUrl } from "@kbco/query-builder";

export default {
    components: {
        GlassSurface,
        GlassInput,
        GlassSelect,
        GlassField,
        CrudView,
        AppLayout,
    },
    setup() {
        return {
            createOpen: ref(false),
            form: ref({
                name: '',
                type: '',
                service: '',
                api_key: '',
                secret_key: '',
                access_token: '',
                refresh_token: '',
                settings: {},
            }),
            private_key: ref(null),
            public_key: ref(null),
            data: ref([]),
            pagination: ref({}),
        };
    },
    watch: {
        date(to) {
            this.form.remind_at = dayjs(to).startOf('day').utc().format("YYYY-MM-DD HH:mm:ss");
        },
    },
    methods: {
        hasErrors(error) {
            if (!this.form.errors) return '';
            return this.form.errors[error] ?? null;
        },
        async save(form) {
            if (!form.id) {
                await axios.post('/api/crud/credentials', form);
            } else {
                console.log('No edit method defined');
            }
        },
        async onDelete() {
            await axios.delete('/api/crud/credentials/' + this.form.id);
        },
        async onExecute({ actionToRun, selectedItems }) {
            try {
                await this.$store.dispatch('executeAction', {
                    url: actionToRun.url,
                    data: { selectedItems },
                });
            } catch (e) {
                console.log(e.message, 'error');
            }
        },
        async fetch({ page, limit, ...args }) {
            try {
                const { data: { data, ...pagination } } = await axios.get(buildUrl(
                    '/api/crud/credentials', {
                        page, limit,
                        ...args,
                        include: [],
                    },
                ));

                this.data = data;
                this.pagination = pagination;
            } catch (e) {
                console.error(e.message);
            }
        },
        onFileUploadForPrivateKey(event) {
            const fr = new FileReader();
            fr.onload = () => {
                this.form.settings.private_key = fr.result;
            };
            fr.readAsText(event.target.files[0]);
        },
        onFileUploadForPublicKey(event) {
            const fr = new FileReader();
            fr.onload = () => {
                this.form.settings.pub_key = fr.result;
            };
            fr.readAsText(event.target.files[0]);
        },
    },
};
</script>
