<template>
    <AppLayout :title="`Domain · ${domain.name}`">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-3 lg:gap-6">
                <aside class="mb-8 space-y-6 lg:mb-0">
                    <GlassCard>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400">
                                Domain
                            </p>
                            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
                                {{ domain.name }}
                            </h1>
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                {{ domain.provider ?? 'Registrar unknown' }}
                            </p>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Status</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-200">{{ domain.status ?? 'Active' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Expires</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-200">{{ formattedExpiry }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Auto renew</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-200">{{ domain.auto_renew ? 'Enabled' : 'Disabled' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">DNS Provider</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-200">{{ domain.dns_provider ?? 'Unknown' }}</dd>
                            </div>
                        </dl>

                        <p class="mt-3 text-xs text-stone-500 dark:text-stone-400">
                            Managed by {{ domain.contact_email ?? 'unspecified contact' }}
                        </p>
                    </GlassCard>

                    <GlassCard title="Contact roles">
                        <ContactRoleManager :contacts="contacts" @update="handleContactUpdate" />
                    </GlassCard>
                </aside>

                <div class="space-y-6 lg:col-span-2">
                    <GlassCard
                        title="Edge configuration"
                        subtitle="Cloudflare &amp; DNS"
                    >
                        <template #actions>
                            <GlassButton size="sm">Manage on infrastructure hub</GlassButton>
                        </template>

                        <div class="grid gap-4 md:grid-cols-2">
                            <CloudflareFeatureCard
                                v-for="(feature, index) in cloudflareFeatures"
                                :key="feature.key"
                                :feature="feature"
                                @toggle="(value) => toggleFeature(index, value)"
                            />
                        </div>
                    </GlassCard>

                    <GlassCard
                        :title="`${records.length} records synced`"
                        subtitle="DNS records"
                    >
                        <template #actions>
                            <GlassButton size="sm" @click="startRecordCreate">Add record</GlassButton>
                        </template>

                        <DnsRecordTable :records="records" @edit="startRecordEdit" @delete="handleRecordDelete" />

                        <div
                            v-if="pendingChanges.length"
                            class="mt-4 rounded-lg border border-dashed border-amber-400/60 bg-amber-50/60 p-4 dark:bg-amber-500/10"
                        >
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-200">
                                Pending changes
                            </p>
                            <ul class="space-y-1 text-sm text-amber-800 dark:text-amber-100">
                                <li v-for="change in pendingChanges" :key="change.id">{{ change.description }}</li>
                            </ul>
                        </div>
                    </GlassCard>
                </div>
            </div>
        </div>

        <GlassModal
            :open="recordFormOpen"
            :title="editingRecord ? 'Edit DNS record' : 'Create DNS record'"
            size="lg"
            @close="closeRecordForm"
        >
            <DnsRecordForm :record="editingRecord" @save="handleRecordSave" @cancel="closeRecordForm" />
        </GlassModal>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassModal from "@/Components/Glass/GlassModal.vue";
import CloudflareFeatureCard from "@/Components/Infrastructure/CloudflareFeatureCard.vue";
import DnsRecordTable from "@/Components/Infrastructure/DnsRecordTable.vue";
import DnsRecordForm from "@/Components/Infrastructure/DnsRecordForm.vue";
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
