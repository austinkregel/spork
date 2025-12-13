<template>
    <AppLayout :title="`Domain · ${domain.name}`">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-3 lg:gap-6">
                <aside class="space-y-6 mb-8 lg:mb-0">
                    <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                                Domain
                            </p>
                            <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">
                                {{ domain.name }}
                            </h1>
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                {{ domain.provider ?? 'Registrar unknown' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    Status
                                </p>
                                <p class="text-stone-800 dark:text-stone-200 font-semibold">
                                    {{ domain.status ?? 'Active' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    Expires
                                </p>
                                <p class="text-stone-800 dark:text-stone-200 font-semibold">
                                    {{ formattedExpiry }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    Auto renew
                                </p>
                                <p class="text-stone-800 dark:text-stone-200 font-semibold">
                                    {{ domain.auto_renew ? 'Enabled' : 'Disabled' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    DNS Provider
                                </p>
                                <p class="text-stone-800 dark:text-stone-200 font-semibold">
                                    {{ domain.dns_provider ?? 'Unknown' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-xs text-stone-500 dark:text-stone-400">
                            Managed by {{ domain.contact_email ?? 'unspecified contact' }}
                        </div>
                    </section>

                    <section class="border border-dashed border-stone-300 dark:border-stone-700 rounded-lg p-6 bg-stone-50 dark:bg-stone-900/40 space-y-4">
                        <p class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                            Contact roles
                        </p>
                        <ContactRoleManager :contacts="contacts" @update="handleContactUpdate" />
                    </section>
                </aside>

                <div class="lg:col-span-2 space-y-6">
                    <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-4">
                        <header class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                                    Cloudflare & DNS
                                </p>
                                <h2 class="text-xl font-semibold text-stone-900 dark:text-white">
                                    Edge configuration
                                </h2>
                            </div>
                            <SporkButton primary xsmall>
                                Manage on infrastructure hub
                            </SporkButton>
                        </header>

                        <div class="grid gap-4 md:grid-cols-2">
                            <CloudflareFeatureCard
                                v-for="(feature, index) in cloudflareFeatures"
                                :key="feature.key"
                                :feature="feature"
                                @toggle="(value) => toggleFeature(index, value)"
                            />
                        </div>
                    </section>

                    <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 shadow-sm">
                        <header class="px-4 py-4 border-b border-stone-200 dark:border-stone-800 flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                                    DNS records
                                </p>
                                <h2 class="text-xl font-semibold text-stone-900 dark:text-white">
                                    {{ records.length }} records synced
                                </h2>
                            </div>
                            <SporkButton primary xsmall @click="startRecordCreate">
                                Add record
                            </SporkButton>
                        </header>

                        <div class="p-4 space-y-4">
                            <DnsRecordTable :records="records" @edit="startRecordEdit" @delete="handleRecordDelete" />

                            <div v-if="pendingChanges.length" class="border border-dashed border-amber-400 rounded-lg p-4 bg-amber-50 dark:bg-amber-900/30">
                                <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-200 font-semibold mb-2">
                                    Pending changes
                                </p>
                                <ul class="text-sm text-amber-800 dark:text-amber-100 space-y-1">
                                    <li v-for="change in pendingChanges" :key="change.id">
                                        {{ change.description }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <DialogModal :show="recordFormOpen" max-width="3xl" @close="closeRecordForm">
            <template #title>
                {{ editingRecord ? 'Edit DNS record' : 'Create DNS record' }}
            </template>

            <template #content>
                <DnsRecordForm :record="editingRecord" @save="handleRecordSave" @cancel="closeRecordForm" />
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import CloudflareFeatureCard from "@/Components/Infrastructure/CloudflareFeatureCard.vue";
import DnsRecordTable from "@/Components/Infrastructure/DnsRecordTable.vue";
import DnsRecordForm from "@/Components/Infrastructure/DnsRecordForm.vue";
import DialogModal from "@/Components/DialogModal.vue";
import ContactRoleManager from "@/Components/Infrastructure/ContactRoleManager.vue";
import { computed, ref } from "vue";
import dayjs from "dayjs";

const props = defineProps({
    domain: {
        type: Object,
        required: true,
    },
});

const domain = props.domain;

const formattedExpiry = computed(() => domain.expires_at ? dayjs(domain.expires_at).format('MMM D, YYYY') : 'Unknown');

const contacts = ref([...(domain.contacts ?? [])]);

const featureStates = ref([
    {
        key: 'proxy',
        label: 'Proxy routing',
        enabled: domain.cloudflare_proxy ?? false,
        description: 'Route traffic through Cloudflare to hide origin IP.',
    },
    {
        key: 'https',
        label: 'Automatic HTTPS',
        enabled: domain.cloudflare_https ?? true,
        description: 'Force HTTPS rewrites at the edge.',
    },
    {
        key: 'waf',
        label: 'WAF',
        enabled: domain.cloudflare_waf ?? false,
        description: 'Web Application Firewall ruleset.',
    },
    {
        key: 'cache',
        label: 'Caching',
        enabled: domain.cloudflare_cache ?? true,
        description: 'Cache static assets on Cloudflare POPs.',
    },
]);

const cloudflareFeatures = computed(() => featureStates.value);
const records = ref([...(domain.records ?? [])]);
const pendingChanges = ref([]);
const recordFormOpen = ref(false);
const editingRecord = ref(null);

const addChange = (description) => {
    pendingChanges.value.push({
        id: `${Date.now()}-${pendingChanges.value.length}`,
        description,
    });
};

const toggleFeature = (index, value) => {
    featureStates.value[index].enabled = value;
    addChange(`${featureStates.value[index].label} ${value ? 'enabled' : 'disabled'}`);
};

const startRecordCreate = () => {
    editingRecord.value = null;
    recordFormOpen.value = true;
};

const startRecordEdit = (record) => {
    editingRecord.value = { ...record };
    recordFormOpen.value = true;
};

const closeRecordForm = () => {
    recordFormOpen.value = false;
};

const handleRecordSave = (record) => {
    const existingIndex = records.value.findIndex((item) => item.id === record.id);
    const entry = {
        ...record,
        id: record.id ?? `${Date.now()}-${records.value.length}`,
    };

    if (existingIndex >= 0) {
        records.value.splice(existingIndex, 1, entry);
        addChange(`Updated ${entry.type} record for ${entry.name}`);
    } else {
        records.value.push(entry);
        addChange(`Created ${entry.type} record for ${entry.name}`);
    }

    recordFormOpen.value = false;
};

const handleRecordDelete = (record) => {
    records.value = records.value.filter((item) => item.id !== record.id);
    addChange(`Deleted ${record.type} record for ${record.name}`);
};

const handleContactUpdate = (contact) => {
    const index = contacts.value.findIndex((item) => item.id === contact.id);
    if (index >= 0) {
        contacts.value.splice(index, 1, contact);
    } else {
        contacts.value.push(contact);
    }

    addChange(`Updated ${contact.role} contact ${contact.name || contact.email || ''}`);
};
</script>

