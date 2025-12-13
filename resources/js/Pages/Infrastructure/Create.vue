<template>
    <AppLayout title="Add Infrastructure">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400">
                        Provisioning wizard
                    </p>
                    <h1 class="text-3xl font-semibold text-stone-900 dark:text-white">
                        Launch new infrastructure
                    </h1>
                    <p class="mt-2 text-sm text-stone-500 dark:text-stone-400 max-w-2xl">
                        Choose a provider, shape the server, and stage DNS updates before pushing changes to the public authority.
                    </p>
                </div>
                <SporkButton secondary @click="() => router.visit(route('servers.index'))">
                    Cancel
                </SporkButton>
            </div>

            <nav class="flex flex-wrap gap-3">
                <button
                    v-for="(step, index) in steps"
                    :key="step.key"
                    type="button"
                    class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide px-3 py-2 rounded-full border transition"
                    :class="index === currentStepIndex ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-stone-300 dark:border-stone-700 text-stone-500 dark:text-stone-400'"
                    :disabled="index > furthestStep"
                    @click="goToStep(index)"
                >
                    <span class="w-5 h-5 flex items-center justify-center rounded-full border" :class="index === currentStepIndex ? 'border-indigo-500' : 'border-stone-400 dark:border-stone-600'">
                        {{ index + 1 }}
                    </span>
                    {{ step.label }}
                </button>
            </nav>

            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-lg p-6 space-y-6">
                <section v-if="currentStep.key === 'provider'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Select a compute provider credential. Only providers you own are listed.
                    </p>
                    <div v-if="!computeProviders.length" class="p-4 border border-dashed border-amber-400 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-sm text-amber-800 dark:text-amber-100">
                        No compute providers available. Add a credential (e.g. DigitalOcean) to continue.
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <button
                            v-for="provider in computeProviders"
                            :key="provider.id"
                            type="button"
                            class="p-4 border rounded-lg text-left transition flex flex-col gap-2"
                            :class="form.providerCredentialId === provider.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/40' : 'border-stone-200 dark:border-stone-700 hover:border-indigo-200'"
                            @click="selectProvider(provider.id)"
                        >
                            <div class="text-sm font-semibold text-stone-900 dark:text-white">
                                {{ provider.name }}
                            </div>
                            <div class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                {{ provider.provider }}
                            </div>
                            <div class="text-xs text-stone-500 dark:text-stone-400">
                                Capabilities: {{ provider.capabilities.join(', ') }}
                            </div>
                        </button>
                    </div>
                </section>

                <section v-else-if="currentStep.key === 'server'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Describe the server you need. Options are fetched from the selected provider when available.
                    </p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Server name</label>
                                <SporkButton secondary xsmall @click="regenerateServerName">Randomize</SporkButton>
                            </div>
                            <SporkInput v-model="form.server.name" class="mt-1" placeholder="infra-indigo-web" />
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Region</label>
                            <SporkSelect v-model="form.server.region" class="mt-1">
                                <template #options>
                                    <option value="" disabled>Select region</option>
                                    <option v-for="region in currentOptions.regions" :key="region.slug" :value="region.slug">
                                        {{ region.name ?? region.slug }}
                                    </option>
                                </template>
                            </SporkSelect>
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Size</label>
                            <SporkSelect v-model="form.server.size" class="mt-1">
                                <template #options>
                                    <option value="" disabled>Select size</option>
                                    <option v-for="size in currentOptions.sizes" :key="size.slug" :value="size.slug">
                                        {{ size.label || size.description || size.slug }}
                                    </option>
                                </template>
                            </SporkSelect>
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Image</label>
                            <SporkInput v-model="form.server.image" class="mt-1" placeholder="ubuntu-22-04-x64" />
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">SSH keys</label>
                            <Multiselect
                                v-model="form.server.ssh_key_ids"
                                :options="sshKeyOptions"
                                label-key="label"
                                value-key="value"
                                :multiple="true"
                                placeholder="Select SSH keys"
                            />
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Tags</label>
                            <SporkInput v-model="tagInput" class="mt-1" placeholder="Comma separated tags" @blur="applyTags" />
                        </div>
                    </div>
                </section>

                <section v-else-if="currentStep.key === 'domain'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Choose how this server should be exposed publicly. You can skip this step and link domains later.
                    </p>
                    <div class="grid gap-2">
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                            <input type="radio" class="text-indigo-600 focus:ring-indigo-500" value="link" v-model="form.domainAction">
                            Link to an existing domain
                        </label>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                            <input type="radio" class="text-indigo-600 focus:ring-indigo-500" value="create" v-model="form.domainAction">
                            Create a new domain/zone
                        </label>
                        <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                            <input type="radio" class="text-indigo-600 focus:ring-indigo-500" value="none" v-model="form.domainAction">
                            I’ll handle DNS later
                        </label>
                    </div>

                    <div v-if="form.domainAction === 'link'" class="space-y-3 border rounded-lg border-stone-200 dark:border-stone-700 p-4">
                        <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Domain</label>
                        <Multiselect
                            v-model="form.existingDomainId"
                            :options="domainOptions"
                            label-key="label"
                            value-key="value"
                            placeholder="Select domain"
                        />
                    </div>

                    <div v-else-if="form.domainAction === 'create'" class="space-y-4 border rounded-lg border-stone-200 dark:border-stone-700 p-4">
                        <div>
                            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Domain name</label>
                            <SporkInput v-model="form.newDomainName" class="mt-1" placeholder="example.com" />
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">DNS provider</label>
                            <SporkSelect v-model="form.dnsProviderCredentialId">
                                <template #options>
                                    <option value="" disabled>Select provider</option>
                                    <option v-for="provider in dnsProviders" :key="provider.id" :value="provider.id">
                                        {{ provider.name }}
                                    </option>
                                </template>
                            </SporkSelect>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                                    DNS records to publish
                                </p>
                                <SporkButton secondary xsmall @click="addDnsRecord">Add record</SporkButton>
                            </div>
                            <div v-if="!form.dnsRecords.length" class="text-sm text-stone-500 dark:text-stone-400">
                                No records configured. Add at least one record that points traffic to the new server.
                            </div>
                            <div v-for="record in form.dnsRecords" :key="record.id" class="grid gap-3 md:grid-cols-4 items-end border border-stone-200 dark:border-stone-700 rounded-lg p-3">
                                <div>
                                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Type</label>
                                    <SporkInput v-model="record.type" class="mt-1" placeholder="A" />
                                </div>
                                <div>
                                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Name</label>
                                    <SporkInput v-model="record.name" class="mt-1" placeholder="@" />
                                </div>
                                <div>
                                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Value</label>
                                    <SporkInput v-model="record.value" class="mt-1" :disabled="record.use_server_ip" placeholder="1.2.3.4" />
                                    <label class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400 mt-1">
                                        <input type="checkbox" v-model="record.use_server_ip" class="text-indigo-600 focus:ring-indigo-500">
                                        Use server IP
                                    </label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Proxied</label>
                                    <input type="checkbox" v-model="record.proxied" class="text-indigo-600 focus:ring-indigo-500">
                                    <SporkButton secondary xsmall class="ml-auto" @click="removeDnsRecord(record.id)">Remove</SporkButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-else-if="currentStep.key === 'review'" class="space-y-4">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        Review the requested resources before launching.
                    </p>
                    <div class="border border-stone-200 dark:border-stone-700 rounded-lg divide-y divide-stone-200 dark:divide-stone-700">
                        <div class="p-4">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Provider</p>
                            <p class="text-sm text-stone-900 dark:text-stone-100">{{ selectedProvider?.name }} · {{ selectedProvider?.provider }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Server plan</p>
                            <p class="text-sm text-stone-900 dark:text-stone-100">{{ form.server.region }} · {{ form.server.size }} · {{ form.server.image }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Domain</p>
                            <p class="text-sm text-stone-900 dark:text-stone-100">
                                <span v-if="form.domainAction === 'link'">Link existing domain {{ selectedDomain?.name ?? '(not selected)' }}</span>
                                <span v-else-if="form.domainAction === 'create'">Create {{ form.newDomainName }} via {{ selectedDnsProvider?.name ?? 'N/A' }}</span>
                                <span v-else>No DNS actions</span>
                            </p>
                        </div>
                        <div class="p-4" v-if="form.domainAction === 'create'">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">DNS records</p>
                            <ul class="text-sm text-stone-900 dark:text-stone-100 list-disc list-inside">
                                <li v-for="record in form.dnsRecords" :key="record.id">
                                    {{ record.type }} · {{ record.name }} → {{ record.use_server_ip ? 'server IP' : record.value || 'TBD' }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div v-if="submissionError" class="p-4 border border-red-200 bg-red-50 rounded-lg text-sm text-red-700 dark:border-red-800 dark:bg-red-900/40 dark:text-red-200">
                        {{ submissionError }}
                    </div>
                    <div v-if="provisionStatus" class="p-4 border border-stone-200 dark:border-stone-700 rounded-lg bg-stone-50 dark:bg-stone-900/40 text-sm text-stone-700 dark:text-stone-200">
                        Provisioning status: <span class="font-semibold">{{ provisionStatus.status }}</span>
                    </div>
                </section>
            </div>

            <div class="flex items-center justify-between border-t border-stone-200 dark:border-stone-800 pt-6">
                <SporkButton secondary :disabled="currentStepIndex === 0 || isSubmitting" @click="prevStep">
                    Back
                </SporkButton>
                <SporkButton primary :disabled="!canContinue || isSubmitting" @click="handlePrimaryAction">
                    <span v-if="!isSubmitting">
                        {{ currentStepIndex === steps.length - 1 ? 'Launch provisioning' : 'Next step' }}
                    </span>
                    <span v-else>Submitting...</span>
                </SporkButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from "@/Layouts/AppLayout.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkSelect from "@/Components/Spork/SporkSelect.vue";
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


