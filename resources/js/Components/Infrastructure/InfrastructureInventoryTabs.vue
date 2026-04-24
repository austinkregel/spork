<template>
    <div class="space-y-4">
        <div class="flex flex-wrap gap-2" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.id"
                :class="[
                    'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-semibold transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950',
                    activeTab === tab.id
                        ? 'border-indigo-500 bg-indigo-500 text-white shadow-sm dark:bg-indigo-600'
                        : 'border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass text-stone-700 dark:text-stone-200 hover:bg-stone-100/60 dark:hover:bg-stone-800/40',
                ]"
                @click="() => emit('change-tab', tab.id)"
            >
                <span>{{ tab.label }}</span>
                <span class="text-xs font-normal opacity-80">{{ tab.count }}</span>
            </button>
        </div>

        <InfrastructureDataTable
            v-if="activeTab === 'servers'"
            title="Servers"
            description="Compute hosts with their metadata, provider, and linked domains."
            :columns="serverColumns"
            :rows="servers"
            empty-message="No servers match the current filters."
        >
            <template #column-name="{ row }">
                <div class="flex flex-col">
                    <Link :href="`/-/infrastructure/servers/${row.id}`" class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">
                        {{ row.name }}
                    </Link>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ row.ip_address ?? 'No IP on file' }}</p>
                </div>
            </template>

            <template #column-status="{ row }">
                <div class="flex flex-wrap items-center gap-2">
                    <Status :status="row.status" />
                    <span v-if="row.os" class="text-xs text-stone-500 dark:text-stone-400">{{ row.os }}</span>
                </div>
            </template>

            <template #column-provider="{ row }">
                <span class="inline-flex items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-200">
                    <DynamicIcon v-if="row.provider_icon" :icon-name="row.provider_icon" class="w-4 h-4" />
                    {{ row.provider_label ?? row.provider ?? '—' }}
                </span>
            </template>

            <template #column-links="{ row }">
                <div class="flex flex-wrap gap-1">
                    <GlassPill
                        v-for="domain in resolveServerLinks(row)"
                        :key="domain.id ?? domain"
                        size="sm"
                    >
                        {{ domain.name ?? domain }}
                    </GlassPill>
                    <span v-if="!resolveServerLinks(row).length" class="text-xs text-stone-500 dark:text-stone-400">
                        Not linked yet
                    </span>
                </div>
            </template>

            <template #actions="{ row }">
                <div class="flex gap-2 justify-end">
                    <GlassButton variant="secondary" size="sm" @click="() => emit('view', { type: 'server', record: row })">
                        View
                    </GlassButton>
                    <GlassButton size="sm" @click="() => emit('link', { type: 'server', record: row })">
                        Link domains
                    </GlassButton>
                </div>
            </template>
        </InfrastructureDataTable>

        <InfrastructureDataTable
            v-else-if="activeTab === 'domains'"
            title="Domains"
            description="Registrar inventory with linkage to DNS zones and servers."
            :columns="domainColumns"
            :rows="domains"
            empty-message="No domains match the current filters."
        >
            <template #column-name="{ row }">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-stone-800 dark:text-stone-100">{{ row.name }}</span>
                    <p class="text-xs text-stone-500 dark:text-stone-400">
                        Expires {{ row.expires_at ? formatDate(row.expires_at) : 'N/A' }}
                    </p>
                </div>
            </template>

            <template #column-provider="{ row }">
                <span class="text-sm text-stone-700 dark:text-stone-200">{{ row.provider_label ?? row.provider ?? '—' }}</span>
            </template>

            <template #column-links="{ row }">
                <div class="flex flex-wrap gap-2 text-xs">
                    <GlassPill size="sm">
                        Server: {{ resolveDomainLink(row)?.name ?? 'Unlinked' }}
                    </GlassPill>
                    <GlassPill size="sm">
                        DNS: {{ resolveDomainDns(row)?.name ?? row.dns_provider ?? 'Unknown' }}
                    </GlassPill>
                </div>
            </template>

            <template #actions="{ row }">
                <div class="flex gap-2 justify-end">
                    <GlassButton variant="secondary" size="sm" @click="() => emit('view', { type: 'domain', record: row })">
                        View
                    </GlassButton>
                    <GlassButton size="sm" @click="() => emit('link', { type: 'domain', record: row })">
                        Link assets
                    </GlassButton>
                </div>
            </template>
        </InfrastructureDataTable>

        <InfrastructureDataTable
            v-else-if="activeTab === 'dns'"
            title="DNS zones"
            description="Cloudflare, Namecheap, and DigitalOcean DNS zones with record counts."
            :columns="dnsColumns"
            :rows="dnsZones"
            empty-message="No DNS zones available."
        >
            <template #column-name="{ row }">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-stone-800 dark:text-stone-100">{{ row.name }}</span>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ row.account ?? row.provider ?? '—' }}</p>
                </div>
            </template>

            <template #column-records="{ row }">
                <span class="text-sm text-stone-700 dark:text-stone-200">{{ row.record_count ?? '—' }}</span>
            </template>

            <template #column-status="{ row }">
                <GlassPill :tone="row.cloudflare_status === 'active' ? 'success' : 'neutral'" size="sm" dot>
                    {{ row.cloudflare_status ?? 'Unknown' }}
                </GlassPill>
            </template>

            <template #actions="{ row }">
                <GlassButton size="sm" @click="() => emit('link', { type: 'dns', record: row })">
                    Manage records
                </GlassButton>
            </template>
        </InfrastructureDataTable>

        <InfrastructureDataTable
            v-else
            title="Contacts"
            description="Registrar and Cloudflare contacts for compliance roles."
            :columns="contactColumns"
            :rows="contacts"
            empty-message="No contacts to show."
        >
            <template #column-name="{ row }">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-stone-800 dark:text-stone-100">{{ row.name }}</span>
                    <span class="text-xs text-stone-500 dark:text-stone-400">{{ row.email ?? row.phone ?? 'No contact info' }}</span>
                </div>
            </template>

            <template #column-role="{ row }">
                <GlassPill size="sm">{{ row.role ?? 'Unassigned' }}</GlassPill>
            </template>

            <template #column-provider="{ row }">
                <span class="text-sm text-stone-700 dark:text-stone-200">{{ row.provider ?? '—' }}</span>
            </template>

            <template #actions="{ row }">
                <GlassButton size="sm" @click="() => emit('link', { type: 'contact', record: row })">
                    Assign roles
                </GlassButton>
            </template>
        </InfrastructureDataTable>
    </div>
</template>

<script setup>
import InfrastructureDataTable from '@/Components/Infrastructure/InfrastructureDataTable.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import Status from '@/Components/Spork/Atoms/Status.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const emit = defineEmits(['change-tab', 'link', 'view']);

const props = defineProps({
    servers: {
        type: Array,
        default: () => [],
    },
    domains: {
        type: Array,
        default: () => [],
    },
    dnsZones: {
        type: Array,
        default: () => [],
    },
    contacts: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: 'servers',
    },
    linkState: {
        type: Object,
        default: () => ({
            servers: {},
            domains: {},
            dns: {},
        }),
    },
});

const tabs = computed(() => [
    { id: 'servers', label: 'Servers', count: props.servers.length },
    { id: 'domains', label: 'Domains', count: props.domains.length },
    { id: 'dns', label: 'DNS Zones', count: props.dnsZones.length },
    { id: 'contacts', label: 'Contacts', count: props.contacts.length },
]);

const activeTab = computed(() => props.activeTab ?? 'servers');

const serverColumns = [
    { key: 'name', label: 'Server' },
    { key: 'provider', label: 'Provider' },
    { key: 'status', label: 'Health' },
    { key: 'links', label: 'Linked domains' },
];

const domainColumns = [
    { key: 'name', label: 'Domain' },
    { key: 'provider', label: 'Registrar' },
    { key: 'links', label: 'Links' },
];

const dnsColumns = [
    { key: 'name', label: 'Zone' },
    { key: 'records', label: 'Records', accessor: 'record_count' },
    { key: 'status', label: 'Cloudflare status' },
];

const contactColumns = [
    { key: 'name', label: 'Contact' },
    { key: 'role', label: 'Role' },
    { key: 'provider', label: 'Provider' },
];

const resolveServerLinks = (server) => {
    const existing = server.domains ?? [];
    const staged = props.linkState.servers?.[server.id] ?? [];
    return [...existing, ...staged];
};

const resolveDomainLink = (domain) => {
    if (props.linkState.domains?.[domain.id]) {
        return props.linkState.domains[domain.id];
    }

    if (domain.server) {
        return domain.server;
    }

    if (domain.server_id) {
        return { id: domain.server_id, name: `Server #${domain.server_id}` };
    }

    return null;
};

const resolveDomainDns = (domain) => {
    if (props.linkState.dns?.[domain.id]) {
        return props.linkState.dns[domain.id];
    }

    if (domain.dns_zone) {
        return domain.dns_zone;
    }

    if (domain.dns_zone_id) {
        return { id: domain.dns_zone_id, name: `Zone #${domain.dns_zone_id}` };
    }

    return null;
};

const formatDate = (value) => {
    if (!value) {
        return null;
    }

    return dayjs(value).format('MMM D, YYYY');
};
</script>

