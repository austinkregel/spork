<template>
    <AppLayout title="Add Infrastructure">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard
                title="Launch new infrastructure"
                subtitle="Provisioning wizard"
            >
                <template #actions>
                    <GlassButton variant="secondary" @click="router.visit(route('infrastructure.servers.index'))">
                        Cancel
                    </GlassButton>
                </template>
                <p class="max-w-2xl text-sm text-stone-500 dark:text-stone-400">
                    Choose a provider, shape the server, and stage DNS updates before pushing changes to the public authority.
                </p>
            </GlassCard>

            <nav class="flex flex-wrap gap-2" aria-label="Wizard steps">
                <button
                    v-for="(step, index) in steps"
                    :key="step.key"
                    type="button"
                    :class="[
                        'inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-wide transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950',
                        index === currentStepIndex
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300'
                            : 'border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-200',
                        index > furthestStep ? 'cursor-not-allowed opacity-60' : '',
                    ]"
                    :aria-current="index === currentStepIndex ? 'step' : undefined"
                    :disabled="index > furthestStep"
                    @click="goToStep(index)"
                >
                    <span :class="['flex h-5 w-5 items-center justify-center rounded-full border', index === currentStepIndex ? 'border-indigo-500' : 'border-stone-400 dark:border-stone-600']">
                        {{ index + 1 }}
                    </span>
                    {{ step.label }}
                </button>
            </nav>

            <GlassSurface class="p-6">
                <section v-if="currentStep.key === 'provider'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Select a compute provider credential. Only providers you own are listed.
                    </p>
                    <div
                        v-if="!computeProviders.length"
                        class="rounded-lg border border-dashed border-amber-400/60 bg-amber-50/60 p-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-100"
                    >
                        No compute providers available. Add a credential (e.g. DigitalOcean) to continue.
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <button
                            v-for="provider in computeProviders"
                            :key="provider.id"
                            type="button"
                            :class="[
                                'flex flex-col gap-2 rounded-lg border p-4 text-left transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950',
                                form.providerCredentialId === provider.id
                                    ? 'border-indigo-500 bg-indigo-50/70 dark:bg-indigo-500/10'
                                    : 'border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] hover:border-indigo-300 dark:hover:border-indigo-500/40',
                            ]"
                            :aria-pressed="form.providerCredentialId === provider.id"
                            @click="selectProvider(provider.id)"
                        >
                            <span class="text-sm font-semibold text-stone-900 dark:text-stone-50">
                                {{ provider.name }}
                            </span>
                            <span class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                {{ provider.provider }}
                            </span>
                            <span class="text-xs text-stone-500 dark:text-stone-400">
                                Capabilities: {{ provider.capabilities.join(', ') }}
                            </span>
                        </button>
                    </div>
                </section>

                <section v-else-if="currentStep.key === 'server'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Describe the server you need. Options are fetched from the selected provider when available.
                    </p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="server-name" class="block text-sm font-medium text-stone-700 dark:text-stone-200">Server name</label>
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400"
                                    @click="regenerateServerName"
                                >
                                    Randomize
                                </button>
                            </div>
                            <GlassInput id="server-name" v-model="form.server.name" placeholder="infra-indigo-web" />
                        </div>
                        <GlassField label="Region">
                            <GlassSelect v-model="form.server.region">
                                <option value="" disabled>Select region</option>
                                <option v-for="region in currentOptions.regions" :key="region.slug" :value="region.slug">
                                    {{ region.name ?? region.slug }}
                                </option>
                            </GlassSelect>
                        </GlassField>
                        <GlassField label="Size">
                            <GlassSelect v-model="form.server.size">
                                <option value="" disabled>Select size</option>
                                <option v-for="size in currentOptions.sizes" :key="size.slug" :value="size.slug">
                                    {{ size.label || size.description || size.slug }}
                                </option>
                            </GlassSelect>
                        </GlassField>
                        <GlassField label="Image">
                            <GlassInput v-model="form.server.image" placeholder="ubuntu-22-04-x64" />
                        </GlassField>
                        <GlassField label="SSH keys">
                            <Multiselect
                                v-model="form.server.ssh_key_ids"
                                :options="sshKeyOptions"
                                label-key="label"
                                value-key="value"
                                :multiple="true"
                                placeholder="Select SSH keys"
                            />
                        </GlassField>
                        <GlassField label="Tags" hint="Comma separated tags">
                            <GlassInput v-model="tagInput" placeholder="web, prod" @blur="applyTags" />
                        </GlassField>
                    </div>
                </section>

                <section v-else-if="currentStep.key === 'domain'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Choose how this server should be exposed publicly. You can skip this step and link domains later.
                    </p>
                    <fieldset class="grid gap-2">
                        <legend class="sr-only">Domain action</legend>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                            <input v-model="form.domainAction" type="radio" value="link" class="text-indigo-600 focus:ring-indigo-500">
                            Link to an existing domain
                        </label>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                            <input v-model="form.domainAction" type="radio" value="create" class="text-indigo-600 focus:ring-indigo-500">
                            Create a new domain/zone
                        </label>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                            <input v-model="form.domainAction" type="radio" value="none" class="text-indigo-600 focus:ring-indigo-500">
                            I'll handle DNS later
                        </label>
                    </fieldset>

                    <div
                        v-if="form.domainAction === 'link'"
                        class="space-y-3 rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] p-4"
                    >
                        <GlassField label="Domain">
                            <Multiselect
                                v-model="form.existingDomainId"
                                :options="domainOptions"
                                label-key="label"
                                value-key="value"
                                placeholder="Select domain"
                            />
                        </GlassField>
                    </div>

                    <div
                        v-else-if="form.domainAction === 'create'"
                        class="space-y-4 rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] p-4"
                    >
                        <GlassField label="Domain name">
                            <GlassInput v-model="form.newDomainName" placeholder="example.com" />
                        </GlassField>
                        <GlassField label="DNS provider">
                            <GlassSelect v-model="form.dnsProviderCredentialId">
                                <option value="" disabled>Select provider</option>
                                <option v-for="provider in dnsProviders" :key="provider.id" :value="provider.id">
                                    {{ provider.name }}
                                </option>
                            </GlassSelect>
                        </GlassField>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    DNS records to publish
                                </p>
                                <GlassButton variant="secondary" size="sm" @click="addDnsRecord">Add record</GlassButton>
                            </div>
                            <p v-if="!form.dnsRecords.length" class="text-sm text-stone-500 dark:text-stone-400">
                                No records configured. Add at least one record that points traffic to the new server.
                            </p>
                            <div
                                v-for="record in form.dnsRecords"
                                :key="record.id"
                                class="grid items-end gap-3 rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] p-3 md:grid-cols-4"
                            >
                                <GlassField label="Type">
                                    <GlassInput v-model="record.type" placeholder="A" />
                                </GlassField>
                                <GlassField label="Name">
                                    <GlassInput v-model="record.name" placeholder="@" />
                                </GlassField>
                                <div class="space-y-1.5">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Value</label>
                                    <GlassInput v-model="record.value" :disabled="record.use_server_ip" placeholder="1.2.3.4" />
                                    <label class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                                        <input v-model="record.use_server_ip" type="checkbox" class="text-indigo-600 focus:ring-indigo-500">
                                        Use server IP
                                    </label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                        <input v-model="record.proxied" type="checkbox" class="text-indigo-600 focus:ring-indigo-500">
                                        Proxied
                                    </label>
                                    <GlassButton variant="ghost" size="sm" class="ml-auto" @click="removeDnsRecord(record.id)">
                                        Remove
                                    </GlassButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-else-if="currentStep.key === 'review'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Review the requested resources before launching.
                    </p>
                    <div class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]">
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Provider</p>
                            <p class="text-sm text-stone-900 dark:text-stone-100">{{ selectedProvider?.name }} · {{ selectedProvider?.provider }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Server plan</p>
                            <p class="text-sm text-stone-900 dark:text-stone-100">{{ form.server.region }} · {{ form.server.size }} · {{ form.server.image }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Domain</p>
                            <p class="text-sm text-stone-900 dark:text-stone-100">
                                <span v-if="form.domainAction === 'link'">Link existing domain {{ selectedDomain?.name ?? '(not selected)' }}</span>
                                <span v-else-if="form.domainAction === 'create'">Create {{ form.newDomainName }} via {{ selectedDnsProvider?.name ?? 'N/A' }}</span>
                                <span v-else>No DNS actions</span>
                            </p>
                        </div>
                        <div v-if="form.domainAction === 'create'" class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">DNS records</p>
                            <ul class="list-inside list-disc text-sm text-stone-900 dark:text-stone-100">
                                <li v-for="record in form.dnsRecords" :key="record.id">
                                    {{ record.type }} · {{ record.name }} → {{ record.use_server_ip ? 'server IP' : record.value || 'TBD' }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div
                        v-if="submissionError"
                        class="rounded-lg border border-red-300 bg-red-50/70 p-4 text-sm text-red-700 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200"
                    >
                        {{ submissionError }}
                    </div>
                    <div
                        v-if="provisionStatus"
                        class="rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-50/60 dark:bg-stone-900/40 p-4 text-sm text-stone-700 dark:text-stone-200"
                    >
                        Provisioning status: <span class="font-semibold">{{ provisionStatus.status }}</span>
                    </div>
                </section>
            </GlassSurface>

            <div class="flex items-center justify-between border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] pt-6">
                <GlassButton variant="secondary" :disabled="currentStepIndex === 0 || isSubmitting" @click="prevStep">
                    Back
                </GlassButton>
                <GlassButton :disabled="!canContinue || isSubmitting" @click="handlePrimaryAction">
                    <span v-if="!isSubmitting">
                        {{ currentStepIndex === steps.length - 1 ? 'Launch provisioning' : 'Next step' }}
                    </span>
                    <span v-else>Submitting...</span>
                </GlassButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from "@/Layouts/AppLayout.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassField from "@/Components/Glass/GlassField.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassSelect from "@/Components/Glass/GlassSelect.vue";
import Multiselect from "@/Components/Multiselect.vue";
import { router } from '@inertiajs/vue3';

const props = defineProps({
    providers: {
        type: Array,
        default: () => [],
    },
    domains: {
        type: Array,
        default: () => [],
    },
    sshKeys: {
        type: Array,
        default: () => [],
    },
});

const steps = [
    { key: 'provider', label: 'Provider' },
    { key: 'server', label: 'Server spec' },
    { key: 'domain', label: 'Domain & DNS' },
    { key: 'review', label: 'Review' },
];

const currentStepIndex = ref(0);
const furthestStep = ref(0);

const emptyRecord = () => ({
    id: crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).substring(2),
    type: 'A',
    name: '@',
    value: '',
    proxied: true,
    use_server_ip: true,
});

const defaultDomainAction = computed(() => (props.domains.length ? 'link' : 'create'));

const form = reactive({
    providerCredentialId: null,
    server: {
        name: '',
        region: '',
        size: '',
        image: 'ubuntu-22-04-x64',
        tags: [],
        ssh_key_ids: [],
        user_data: '',
    },
    domainAction: defaultDomainAction.value,
    existingDomainId: null,
    dnsProviderCredentialId: null,
    newDomainName: '',
    dnsRecords: [emptyRecord()],
});

const providerOptions = reactive({});
const loadingOptions = ref(false);
const isSubmitting = ref(false);
const submissionError = ref(null);
const provisionStatus = ref(null);
let pollingInterval = null;

const computeProviders = computed(() => props.providers.filter((provider) => provider.capabilities?.includes('compute')));
const dnsProviders = computed(() => props.providers.filter((provider) => provider.capabilities?.includes('dns')));
const sshKeys = computed(() => props.sshKeys ?? []);
const sshKeyOptions = computed(() => sshKeys.value.map((key) => ({
    label: `${key.name} (${key.resolved_key_id ?? key.id})`,
    value: key.resolved_key_id ?? key.id,
})));
const defaultSshKeyIds = computed(() => sshKeys.value
    .map((key) => key.resolved_key_id ?? key.id)
    .filter((value) => value !== null && value !== undefined && `${value}`.length > 0)
);

const domainOptions = computed(() => props.domains.map((domain) => ({
    label: domain.name,
    value: domain.id,
})));

const selectedProvider = computed(() => computeProviders.value.find((provider) => provider.id === form.providerCredentialId));
const selectedDomain = computed(() => props.domains.find((domain) => domain.id === form.existingDomainId));
const selectedDnsProvider = computed(() => dnsProviders.value.find((provider) => provider.id === form.dnsProviderCredentialId));

const currentStep = computed(() => steps[currentStepIndex.value]);

const currentOptions = computed(() => providerOptions[form.providerCredentialId] ?? {
    regions: [],
    sizes: [],
});

const canContinue = computed(() => {
    switch (currentStep.value.key) {
        case 'provider':
            return !!form.providerCredentialId;
        case 'server':
            return !!form.server.region && !!form.server.size;
        case 'domain':
            if (form.domainAction === 'link') {
                return !!form.existingDomainId;
            }
            if (form.domainAction === 'create') {
                return !!form.newDomainName && !!form.dnsProviderCredentialId;
            }
            return true;
        case 'review':
            return true;
        default:
            return false;
    }
});

const tagInput = ref('');

const goToStep = (index) => {
    if (index > furthestStep.value) {
        return;
    }
    currentStepIndex.value = index;
};

const nextStep = () => {
    if (!canContinue.value) {
        return;
    }
    const nextIndex = Math.min(currentStepIndex.value + 1, steps.length - 1);
    currentStepIndex.value = nextIndex;
    furthestStep.value = Math.max(furthestStep.value, nextIndex);
};

const prevStep = () => {
    currentStepIndex.value = Math.max(currentStepIndex.value - 1, 0);
};

const selectProvider = (id) => {
    form.providerCredentialId = id;
};

const fetchProviderOptions = async (credentialId) => {
    if (!credentialId) {
        return;
    }

    loadingOptions.value = true;
    try {
        const { data } = await axios.get(route('infrastructure.providers.options', credentialId));
        providerOptions[credentialId] = {
            regions: data.regions ?? [],
            sizes: data.sizes ?? [],
        };

        if (!form.server.region && providerOptions[credentialId].regions.length) {
            form.server.region = providerOptions[credentialId].regions[0].slug ?? providerOptions[credentialId].regions[0].name;
        }

        if (!form.server.size && providerOptions[credentialId].sizes.length) {
            form.server.size = providerOptions[credentialId].sizes[0].slug ?? providerOptions[credentialId].sizes[0].id;
        }
    } catch (error) {
        console.error(error);
    } finally {
        loadingOptions.value = false;
    }
};

const applyTags = () => {
    form.server.tags = tagInput.value
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean);
};

const addDnsRecord = () => {
    form.dnsRecords.push(emptyRecord());
};

const removeDnsRecord = (id) => {
    form.dnsRecords = form.dnsRecords.filter((record) => record.id !== id);
};

const formPayload = computed(() => ({
    provider_credential_id: form.providerCredentialId,
    server: {
        name: form.server.name || `provision-${Date.now()}`,
        region: form.server.region,
        size: form.server.size,
        image: form.server.image,
        ssh_key_ids: form.server.ssh_key_ids,
        tags: form.server.tags,
        user_data: form.server.user_data,
    },
    domain_action: form.domainAction,
    existing_domain_id: form.domainAction === 'link' ? form.existingDomainId : null,
    dns_provider_credential_id: form.domainAction !== 'none' ? (form.dnsProviderCredentialId || selectedDomain.value?.credential_id || null) : null,
    new_domain: form.domainAction === 'create' ? { name: form.newDomainName } : null,
    records: form.domainAction === 'none' ? [] : form.dnsRecords.map((record) => ({
        type: record.type,
        name: record.name,
        value: record.value,
        proxied: record.proxied,
        use_server_ip: record.use_server_ip,
    })),
}));

const handlePrimaryAction = () => {
    if (currentStepIndex.value === steps.length - 1) {
        submit();
        return;
    }

    nextStep();
};

const submit = async () => {
    isSubmitting.value = true;
    submissionError.value = null;

    try {
        const { data } = await axios.post('/api/infrastructure/provision', formPayload.value);
        provisionStatus.value = data.data ?? data;
        furthestStep.value = steps.length - 1;
        currentStepIndex.value = steps.length - 1;
        startPolling(provisionStatus.value.id);
    } catch (error) {
        submissionError.value = error.response?.data?.message ?? 'Failed to submit provisioning request.';
    } finally {
        isSubmitting.value = false;
    }
};

const startPolling = (id) => {
    stopPolling();

    pollingInterval = setInterval(async () => {
        try {
            const { data } = await axios.get(`/api/infrastructure/provision/${id}`);
            provisionStatus.value = data.data ?? data;

            if (!['pending', 'provisioning'].includes(provisionStatus.value.status)) {
                stopPolling();
            }
        } catch (error) {
            console.error('Polling failed', error);
            stopPolling();
        }
    }, 5000);
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

const randomWord = () => {
    const segments = ['infra', 'ops', 'east', 'west', 'blue', 'green', 'alpha', 'beta', 'gamma', 'delta', 'omega', 'nova'];
    return segments[Math.floor(Math.random() * segments.length)];
};

const generateServerName = () => {
    const parts = [randomWord(), randomWord(), Math.floor(Math.random() * 900 + 100)];
    return parts.join('-');
};

const regenerateServerName = () => {
    form.server.name = generateServerName();
};

const initialize = () => {
    form.providerCredentialId = computeProviders.value[0]?.id ?? null;
    form.domainAction = defaultDomainAction.value;
    form.dnsProviderCredentialId = dnsProviders.value[0]?.id ?? null;
    form.existingDomainId = props.domains[0]?.id ?? null;
    const defaultIds = defaultSshKeyIds.value;
    form.server.ssh_key_ids = [...defaultIds];
    form.server.name = generateServerName();

    if (form.providerCredentialId) {
        fetchProviderOptions(form.providerCredentialId);
    }
};

onMounted(() => {
    initialize();
});

watch(() => form.providerCredentialId, (credentialId) => {
    if (credentialId && !providerOptions[credentialId]) {
        fetchProviderOptions(credentialId);
    }
});

watch(() => form.domainAction, (action) => {
    if (action === 'create' && !form.dnsProviderCredentialId && dnsProviders.value.length) {
        form.dnsProviderCredentialId = dnsProviders.value[0].id;
    }
});
</script>
