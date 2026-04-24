<template>
  <AppLayout title="Infrastructure">
    <div class="space-y-8 px-4 py-6 sm:px-6 lg:px-8">
      <section class="space-y-4">
        <GlassCard
          title="Infrastructure"
          subtitle="Inventory and health, at a glance."
        >
          <template #actions>
            <GlassButton variant="secondary" :icon-left="DocumentDuplicateIcon">Export</GlassButton>
            <GlassButton :href="route('infrastructure.create')" :icon-left="PlusIcon">Add</GlassButton>
          </template>

          <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <GlassMetricCard
              v-for="stat in overviewStats"
              :key="stat.id"
              :label="stat.label"
              :value="stat.value"
              :description="stat.meta"
            />
          </div>
        </GlassCard>

        <QuickActions :actions="quickActionItems" compact @select="handleQuickAction" />
      </section>

      <InfrastructureInventoryTabs
        :servers="filteredServers"
        :domains="filteredDomains"
        :dns-zones="filteredDnsZones"
        :contacts="filteredContacts"
        :active-tab="filters.activeTab"
        :link-state="linkState"
        @change-tab="setActiveTab"
        @link="handleLinkRequest"
        @view="handleViewRequest"
      />

      <section class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
          <ProviderFilterChips :providers="providerFilters" />

          <GlassCard title="Search inventory">
            <div class="flex flex-wrap items-center gap-3">
              <GlassInput
                v-model="searchTerm"
                class="min-w-[220px] flex-1"
                placeholder="Search inventory…"
                type="search"
              />
              <p class="text-xs text-stone-500 dark:text-stone-400">{{ searchSubtitle }}</p>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
              <GlassMetricCard
                v-for="spotlight in spotlights"
                :key="spotlight.id"
                :label="spotlight.label"
                :value="spotlight.value"
                :description="spotlight.description"
              />
            </div>
          </GlassCard>
        </div>

        <RecentActivityFeed :items="activityFeed" />
      </section>
    </div>

    <GlassModal :open="openLinkServer" title="Link a server via SSH" size="lg" @close="openLinkServer = false">
      <p class="text-sm text-stone-600 dark:text-stone-300">
        Add this key to <code class="rounded bg-stone-100 px-1 py-0.5 font-mono text-xs dark:bg-stone-900">~/.ssh/authorized_keys</code>, then leave this open while we verify.
      </p>
      <textarea
        readonly
        rows="6"
        class="mt-4 block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 font-mono text-xs text-stone-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100"
        :value="sshCredential?.settings?.pub_key ?? ''"
      />
      <template #footer>
        <GlassButton variant="secondary" @click="openLinkServer = false">Cancel</GlassButton>
      </template>
    </GlassModal>

    <GlassModal :open="!!linkingContext" :title="linkingTitle" size="lg" @close="closeLinkingModal">
      <p class="mb-4 text-sm text-stone-600 dark:text-stone-300">{{ linkingDescription }}</p>

      <div v-if="isServerLink" class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
          Domains to associate
        </p>
        <Multiselect
          v-model="linkForm.domainIds"
          :options="domainOptions"
          multiple
          label-key="label"
          value-key="value"
        />
      </div>

      <div v-else-if="isDomainLink" class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
            Server destination
          </p>
          <Multiselect
            v-model="linkForm.serverId"
            :options="serverOptions"
            label-key="label"
            value-key="value"
          />
        </div>
        <div class="space-y-2">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
            DNS zone
          </p>
          <Multiselect
            v-model="linkForm.dnsZoneId"
            :options="dnsZoneOptions"
            label-key="label"
            value-key="value"
          />
        </div>
      </div>

      <p v-else class="text-sm text-stone-600 dark:text-stone-300">
        This action opens a specialized tool in the next steps of the redesign.
      </p>

      <template #footer>
        <GlassButton variant="secondary" @click="closeLinkingModal">Cancel</GlassButton>
        <GlassButton :disabled="linkModalDisabled" @click="handleLinkSave">Save link</GlassButton>
      </template>
    </GlassModal>

    <GlassModal :open="!!activeDnsZone" :title="`Manage DNS zone · ${dnsZoneName}`" size="xl" @close="closeDnsZoneModal">
      <p class="mb-4 text-sm text-stone-600 dark:text-stone-300">
        Edit records for this zone. Changes queue for approval.
      </p>

      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-stone-500 dark:text-stone-400">
          Provider: {{ activeDnsZone?.provider ?? 'Unknown' }} · Account: {{ activeDnsZone?.account ?? 'N/A' }}
        </p>
        <GlassButton size="sm" @click="startDnsZoneRecordCreate">Add record</GlassButton>
      </div>

      <DnsRecordTable :records="dnsZoneRecords" @edit="startDnsZoneRecordEdit" @delete="handleDnsZoneRecordDelete" />

      <div
        v-if="dnsZoneChanges.length"
        class="mt-4 rounded-lg border border-dashed border-amber-400 bg-amber-50/60 p-4 dark:bg-amber-500/10"
      >
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-200">
          Pending apply
        </p>
        <ul class="space-y-1 text-sm text-amber-800 dark:text-amber-100">
          <li v-for="change in dnsZoneChanges" :key="change.id">{{ change.description }}</li>
        </ul>
      </div>

      <template #footer>
        <GlassButton variant="secondary" @click="closeDnsZoneModal">Close</GlassButton>
      </template>
    </GlassModal>

    <GlassModal :open="dnsZoneFormOpen" :title="editingDnsZoneRecord ? 'Edit DNS record' : 'Create DNS record'" size="lg" @close="closeDnsZoneForm">
      <DnsRecordForm :record="editingDnsZoneRecord" @save="handleDnsZoneRecordSave" @cancel="closeDnsZoneForm" />
    </GlassModal>

    <GlassModal :open="bulkWizardOpen" title="Bulk operation wizard" size="xl" @close="closeBulkWizard">
      <BulkOperationWizard :operations="bulkOperations" @submit="handleBulkOperationSubmit" @cancel="closeBulkWizard" />

      <div
        v-if="queuedBulkJobs.length"
        class="mt-6 rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 p-4"
      >
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
          Recently queued jobs
        </p>
        <ul class="space-y-1 text-sm text-stone-700 dark:text-stone-200">
          <li v-for="job in queuedBulkJobs" :key="job.id">
            {{ job.operation }} → {{ job.scope }} ({{ job.filter || 'all' }}) at {{ dayjs(job.created_at).format('MMM D, h:mm A') }}
          </li>
        </ul>
      </div>
    </GlassModal>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { DocumentDuplicateIcon, PlusIcon } from '@heroicons/vue/24/outline';

import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassMetricCard from '@/Components/Glass/GlassMetricCard.vue';
import QuickActions from '@/Components/Infrastructure/QuickActions.vue';
import ProviderFilterChips from '@/Components/Infrastructure/ProviderFilterChips.vue';
import RecentActivityFeed from '@/Components/Infrastructure/RecentActivityFeed.vue';
import InfrastructureInventoryTabs from '@/Components/Infrastructure/InfrastructureInventoryTabs.vue';
import DnsRecordTable from '@/Components/Infrastructure/DnsRecordTable.vue';
import DnsRecordForm from '@/Components/Infrastructure/DnsRecordForm.vue';
import BulkOperationWizard from '@/Components/Infrastructure/BulkOperationWizard.vue';
import Multiselect from '@/Components/Multiselect.vue';
import { useInfrastructureStore } from '@/composables/useInfrastructureStore';

const props = defineProps({
  servers: { type: Array, default: () => [] },
  domains: { type: Array, default: () => [] },
  dnsZones: { type: Array, default: () => [] },
  contacts: { type: Array, default: () => [] },
  providerStats: { type: Array, default: () => [] },
  recentActivity: { type: Array, default: () => [] },
  quickActions: { type: Array, default: () => [] },
  pagination: { type: Object, default: null },
  sshCredential: { type: Object, default: null },
  providers: { type: Array, default: () => [] },
});

const openLinkServer = ref(false);

const { filters, setSearch, setActiveTab } = useInfrastructureStore();

const searchTerm = computed({
  get: () => filters.search,
  set: (value) => setSearch(value),
});

const quickActionItems = computed(() => props.quickActions ?? []);

const servers = computed(() => props.servers ?? []);
const domains = computed(() => props.domains ?? []);
const dnsZones = computed(() => props.dnsZones ?? []);
const contacts = computed(() => props.contacts ?? []);
const totalServers = computed(() => props.pagination?.total ?? servers.value.length);
const unhealthyServers = computed(() =>
  servers.value.filter((server) => ['error', 'offline', 'degraded'].includes(server.status)).length,
);

const linkedDomainCount = computed(() => domains.value.filter((domain) => Boolean(domain.server_id)).length);

const expiringDomains = computed(() =>
  domains.value.filter((domain) => {
    if (!domain?.expires_at) return false;
    return dayjs(domain.expires_at).diff(dayjs(), 'day') <= 30;
  }).length,
);

const overviewStats = computed(() => [
  {
    id: 'servers',
    label: 'Servers monitored',
    value: totalServers.value,
    meta: unhealthyServers.value ? `${unhealthyServers.value} need attention` : 'All green',
  },
  {
    id: 'domains',
    label: 'Domains in inventory',
    value: domains.value.length,
    meta: linkedDomainCount.value ? `${linkedDomainCount.value} linked to servers` : 'Link domains to servers',
  },
  {
    id: 'dns',
    label: 'DNS zones tracked',
    value: dnsZones.value.length,
    meta: 'Cloudflare + Namecheap + DigitalOcean',
  },
  {
    id: 'contacts',
    label: 'Contacts synced',
    value: contacts.value.length,
    meta: 'Registrar & Cloudflare roles',
  },
]);

const spotlights = computed(() => [
  { id: 'expiring', label: 'Domains expiring soon', value: expiringDomains.value, description: 'Due in 30 days' },
  {
    id: 'unlinked',
    label: 'Domains without DNS zone',
    value: Math.max(domains.value.length - linkedDomainCount.value, 0),
    description: 'Needs a zone',
  },
  {
    id: 'unhealthy',
    label: 'Servers needing attention',
    value: unhealthyServers.value,
    description: 'Status not green',
  },
]);

const providerFilters = computed(() => {
  if (props.providerStats?.length) return props.providerStats;

  return [
    { slug: 'namecheap', name: 'Namecheap', icon: 'GlobeAltIcon', count: domains.value.filter((domain) => domain.provider === 'namecheap').length },
    { slug: 'digitalocean', name: 'DigitalOcean', icon: 'ServerIcon', count: servers.value.filter((server) => server.provider === 'digitalocean').length },
    { slug: 'cloudflare', name: 'Cloudflare', icon: 'BoltIcon', count: dnsZones.value.length },
  ];
});

const matchesSearchTerm = (fields) => {
  if (!filters.search) return true;
  const term = filters.search.toLowerCase();
  return fields.filter(Boolean).some((field) => field.toString().toLowerCase().includes(term));
};

const matchesProviderFilters = (candidates) => {
  if (!filters.selectedProviders.length) return true;
  const normalized = candidates.filter(Boolean).map((candidate) => candidate.toString().toLowerCase());
  return filters.selectedProviders.some((provider) => normalized.includes(provider.toLowerCase()));
};

const filteredServers = computed(() =>
  servers.value.filter((server) => {
    const fields = [server.name, server.ip_address, server.os, server.status, server.internal_url];
    const providers = [server.provider, server.provider_slug, server.provider_label, server.credential?.type, server.credential?.provider];
    return matchesSearchTerm(fields) && matchesProviderFilters(providers);
  }),
);

const filteredDomains = computed(() =>
  domains.value.filter((domain) => {
    const fields = [domain.name, domain.provider, domain.registrar, domain.contact_email];
    const providers = [domain.provider, domain.registrar, domain.dns_provider];
    return matchesSearchTerm(fields) && matchesProviderFilters(providers);
  }),
);

const filteredDnsZones = computed(() =>
  dnsZones.value.filter((zone) => {
    const fields = [zone.name, zone.account, zone.provider];
    const providers = [zone.provider, zone.cloudflare_account, zone.owner];
    return matchesSearchTerm(fields) && matchesProviderFilters(providers);
  }),
);

const filteredContacts = computed(() =>
  contacts.value.filter((contact) => {
    const fields = [contact.name, contact.email, contact.role, contact.provider];
    const providers = [contact.provider];
    return matchesSearchTerm(fields) && matchesProviderFilters(providers);
  }),
);

const activityFeed = computed(() => {
  if (props.recentActivity?.length) return props.recentActivity;

  return servers.value.slice(0, 5).map((server) => ({
    id: `server-${server.id}`,
    title: `${server.name} heartbeat`,
    description: `Status ${server.status ?? 'unknown'} · ${server.services?.map((service) => service.service).join(', ') || 'no services detected'}`,
    timestamp: server.updated_at ?? server.last_seen_at ?? new Date().toISOString(),
    meta: [server.ip_address].filter(Boolean),
  }));
});

const searchSubtitle = computed(() => (!searchTerm.value ? 'Using current filters' : `Matching “${searchTerm.value}”`));

const linkState = reactive({
  servers: {},
  domains: {},
  dns: {},
});

const linkingContext = ref(null);
const linkForm = reactive({
  domainIds: [],
  serverId: null,
  dnsZoneId: null,
});

const activeDnsZone = ref(null);
const dnsZoneRecords = ref([]);
const dnsZoneChanges = ref([]);
const dnsZoneFormOpen = ref(false);
const editingDnsZoneRecord = ref(null);
const bulkWizardOpen = ref(false);
const queuedBulkJobs = ref([]);

const bulkOperations = [
  {
    id: 'update-contact',
    label: 'Update contact role',
    description: 'Assign registrant/admin/tech contacts across domains.',
    fields: [
      { id: 'role', label: 'Role', component: GlassInput, props: { placeholder: 'registrant' } },
      { id: 'name', label: 'Contact name', component: GlassInput, props: { placeholder: 'Jane Doe' } },
      { id: 'email', label: 'Email', component: GlassInput, props: { placeholder: 'ops@example.com', type: 'email' } },
    ],
  },
  {
    id: 'toggle-cloudflare',
    label: 'Toggle Cloudflare feature',
    description: 'Enable or disable proxy/caching features.',
    fields: [
      { id: 'feature', label: 'Feature key', component: GlassInput, props: { placeholder: 'proxy' } },
      { id: 'state', label: 'Desired state', component: GlassInput, props: { placeholder: 'enable/disable' } },
    ],
  },
  {
    id: 'update-nameservers',
    label: 'Update nameservers',
    description: 'Push new authoritative name servers to registrars.',
    fields: [
      { id: 'ns1', label: 'Nameserver #1', component: GlassInput, props: { placeholder: 'ns1.example.com' } },
      { id: 'ns2', label: 'Nameserver #2', component: GlassInput, props: { placeholder: 'ns2.example.com' } },
    ],
  },
];

const domainOptions = computed(() => domains.value.map((domain) => ({ label: domain.name, value: domain.id })));
const serverOptions = computed(() => servers.value.map((server) => ({ label: server.name, value: server.id })));
const dnsZoneOptions = computed(() => dnsZones.value.map((zone) => ({ label: zone.name, value: zone.id })));

const domainLookup = computed(() => {
  const map = new Map();
  domains.value.forEach((domain) => map.set(domain.id, domain));
  return map;
});

const serverLookup = computed(() => {
  const map = new Map();
  servers.value.forEach((server) => map.set(server.id, server));
  return map;
});

const dnsZoneLookup = computed(() => {
  const map = new Map();
  dnsZones.value.forEach((zone) => map.set(zone.id, zone));
  return map;
});

const getServerLinkedDomainIds = (server) => {
  const existing = (server.domains ?? []).map((domain) => domain.id);
  const staged = linkState.servers[server.id]?.map((domain) => domain.id) ?? [];
  return Array.from(new Set([...existing, ...staged].filter(Boolean)));
};

const resolveDomainLink = (domain) => {
  if (linkState.domains[domain.id]) return linkState.domains[domain.id];
  if (domain.server) return domain.server;
  if (domain.server_id) return { id: domain.server_id, name: `Server #${domain.server_id}` };
  return null;
};

const resolveDomainDns = (domain) => {
  if (linkState.dns[domain.id]) return linkState.dns[domain.id];
  if (domain.dns_zone) return domain.dns_zone;
  if (domain.dns_zone_id) return { id: domain.dns_zone_id, name: `Zone #${domain.dns_zone_id}` };
  return null;
};

watch(
  linkingContext,
  (context) => {
    if (!context) {
      linkForm.domainIds = [];
      linkForm.serverId = null;
      linkForm.dnsZoneId = null;
      return;
    }

    if (context.type === 'server') {
      linkForm.domainIds = getServerLinkedDomainIds(context.record);
      return;
    }

    if (context.type === 'domain') {
      linkForm.serverId = resolveDomainLink(context.record)?.id ?? null;
      linkForm.dnsZoneId = resolveDomainDns(context.record)?.id ?? null;
    }
  },
  { immediate: true },
);

const isServerLink = computed(() => linkingContext.value?.type === 'server');
const isDomainLink = computed(() => linkingContext.value?.type === 'domain');

const linkingTitle = computed(() => {
  if (!linkingContext.value) return 'Link infrastructure';
  if (isServerLink.value) return `Link domains to ${linkingContext.value.record?.name ?? 'server'}`;
  if (isDomainLink.value) return `Link ${linkingContext.value.record?.name ?? 'domain'}`;
  return 'Link infrastructure';
});

const linkingDescription = computed(() => {
  if (isServerLink.value) return 'Select domains that should route to this server.';
  if (isDomainLink.value) return 'Choose the server and DNS zone for this domain.';
  return 'This action uses a dedicated workflow.';
});

const linkModalDisabled = computed(() => {
  if (isDomainLink.value) return !linkForm.serverId && !linkForm.dnsZoneId;
  return false;
});

const closeLinkingModal = () => {
  linkingContext.value = null;
};

const handleViewRequest = (payload) => {
  if (payload.type === 'server') {
    router.visit(`/-/infrastructure/servers/${payload.record.id}`);
    return;
  }
  if (payload.type === 'domain') {
    router.visit(`/-/domains/${payload.record.id}`);
  }
};

const handleLinkSave = () => {
  if (!linkingContext.value) return;

  if (isServerLink.value) {
    const selectedDomains = linkForm.domainIds
      .map((id) => domainLookup.value.get(id))
      .filter(Boolean)
      .map((domain) => ({ id: domain.id, name: domain.name }));

    linkState.servers[linkingContext.value.record.id] = selectedDomains;

    selectedDomains.forEach((domain) => {
      linkState.domains[domain.id] = { id: linkingContext.value.record.id, name: linkingContext.value.record.name };
    });
  } else if (isDomainLink.value) {
    const serverRecord = serverLookup.value.get(linkForm.serverId);
    const dnsZoneRecord = dnsZoneLookup.value.get(linkForm.dnsZoneId);

    if (serverRecord) {
      linkState.domains[linkingContext.value.record.id] = { id: serverRecord.id, name: serverRecord.name };
      const existing = linkState.servers[serverRecord.id] ?? [];
      const withoutCurrent = existing.filter((domain) => domain.id !== linkingContext.value.record.id);
      linkState.servers[serverRecord.id] = [
        ...withoutCurrent,
        { id: linkingContext.value.record.id, name: linkingContext.value.record.name },
      ];
    }

    if (dnsZoneRecord) {
      linkState.dns[linkingContext.value.record.id] = { id: dnsZoneRecord.id, name: dnsZoneRecord.name };
    }
  }

  linkingContext.value = null;
};

const dnsZoneName = computed(() => activeDnsZone.value?.name ?? 'DNS zone');

const openDnsZoneModal = (zone) => {
  activeDnsZone.value = zone;
  dnsZoneRecords.value = [...(zone.records ?? [])];
  dnsZoneChanges.value = [];
};

const closeDnsZoneModal = () => {
  activeDnsZone.value = null;
  dnsZoneRecords.value = [];
  dnsZoneChanges.value = [];
};

const trackDnsZoneChange = (description) => {
  dnsZoneChanges.value.push({
    id: `${Date.now()}-${dnsZoneChanges.value.length}`,
    description,
  });
};

const startDnsZoneRecordCreate = () => {
  editingDnsZoneRecord.value = null;
  dnsZoneFormOpen.value = true;
};

const startDnsZoneRecordEdit = (record) => {
  editingDnsZoneRecord.value = { ...record };
  dnsZoneFormOpen.value = true;
};

const closeDnsZoneForm = () => {
  dnsZoneFormOpen.value = false;
};

const handleDnsZoneRecordSave = (record) => {
  const existingIndex = dnsZoneRecords.value.findIndex((item) => item.id === record.id);
  const entry = {
    ...record,
    id: record.id ?? `${Date.now()}-${dnsZoneRecords.value.length}`,
  };

  if (existingIndex >= 0) {
    dnsZoneRecords.value.splice(existingIndex, 1, entry);
    trackDnsZoneChange(`Updated ${entry.type} record for ${entry.name}`);
  } else {
    dnsZoneRecords.value.push(entry);
    trackDnsZoneChange(`Created ${entry.type} record for ${entry.name}`);
  }

  closeDnsZoneForm();
};

const handleDnsZoneRecordDelete = (record) => {
  dnsZoneRecords.value = dnsZoneRecords.value.filter((item) => item.id !== record.id);
  trackDnsZoneChange(`Deleted ${record.type} record for ${record.name}`);
};

const handleBulkOperationSubmit = (payload) => {
  queuedBulkJobs.value.push({
    id: `${Date.now()}-${queuedBulkJobs.value.length}`,
    ...payload,
    created_at: new Date().toISOString(),
  });
  bulkWizardOpen.value = false;
};

const closeBulkWizard = () => {
  bulkWizardOpen.value = false;
};

const handleQuickAction = (actionId) => {
  if (actionId === 'connect-host') {
    router.visit(route('infrastructure.connect-host'));
    return;
  }
  if (actionId === 'link-server') {
    openLinkServer.value = true;
    return;
  }
  if (actionId === 'import-domains') return;
  if (actionId === 'bulk-edit') {
    bulkWizardOpen.value = true;
  }
};

const handleLinkRequest = (payload) => {
  if (payload.type === 'dns') {
    openDnsZoneModal(payload.record);
    return;
  }
  linkingContext.value = payload;
};
</script>
