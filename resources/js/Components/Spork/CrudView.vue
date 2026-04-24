<template>
    <div class="flex h-full w-full flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div v-if="title" class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
                    {{ singular }}
                </p>
                <h2 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
                    {{ title }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <GlassButton v-if="singular !== 'credential'" @click="openCreateModal">
                    Create New {{ singular }}
                </GlassButton>
                <GlassButton v-else @click="openCredentialModal">
                    Create New {{ singular }}
                </GlassButton>
            </div>
        </div>

        <SporkTable :headers="[]" :data="data">
            <template #table-top>
                <div class="relative z-0 flex w-full flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            class="h-4 w-4 cursor-pointer rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600"
                            :checked="data.length > 0 && selectedItems.length === data.length"
                            @click="selectAll"
                        >
                        <span v-if="selectedItems.length > 0" class="text-sm text-stone-700 dark:text-stone-300">
                            {{ selectedItems.length }} selected
                        </span>
                    </div>

                    <div class="relative flex items-center gap-3">
                        <div
                            v-if="description?.actions?.length > 0 && selectedItems.length > 0"
                            class="flex items-center gap-2 rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] px-3 py-1.5 text-sm text-stone-700 dark:text-stone-200"
                        >
                            <select
                                v-model="actionToRun"
                                class="bg-transparent text-sm focus:outline-none"
                            >
                                <option
                                    v-for="action in description?.actions ?? []"
                                    :key="action.slug ?? action.name"
                                    :value="action"
                                >
                                    {{ action.name }} ({{ selectedItems.length }})
                                </option>
                            </select>

                            <button type="button" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm" @click.prevent="executeActionOrOpenDialog">
                                <PlayIcon v-if="!executing || !applyTagModalOpen" class="h-5 w-5" aria-hidden="true" />
                                <ArrowPathIcon v-else class="h-5 w-5 animate-spin" aria-hidden="true" />
                            </button>
                        </div>

                        <GlassIconButton
                            v-if="description.permissions.delete"
                            variant="ghost"
                            size="sm"
                            label="Delete selected"
                            @click="$emit('destroyMany', selectedItems)"
                        >
                            <DynamicIcon icon-name="TrashIcon" class="h-4 w-4 text-red-500" />
                        </GlassIconButton>

                        <GlassButton variant="secondary" size="sm" @click="filtersOpen = !filtersOpen">
                            Filters
                            <ChevronDownIcon class="h-3 w-3" aria-hidden="true" />
                        </GlassButton>

                        <div
                            v-if="filtersOpen"
                            class="absolute right-0 top-full mt-2 w-64 rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] backdrop-blur-glass shadow-lg"
                        >
                            <div class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
                                Filters
                            </div>
                            <div class="space-y-3 p-4">
                                <GlassField label="Items per page">
                                    <GlassSelect @change="(e) => router.reload({ search: { limit: e.target.value } })">
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="100">100</option>
                                    </GlassSelect>
                                </GlassField>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #datum="{ datum }">
                <div class="flex w-full items-center rounded-md px-2 py-3 transition-colors motion-reduce:transition-none hover:bg-stone-100/40 dark:hover:bg-stone-800/40">
                    <div class="mx-4 w-6">
                        <input
                            v-model="selectedItems"
                            type="checkbox"
                            class="h-3 w-3 cursor-pointer rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600"
                            :value="datum"
                        >
                    </div>
                    <div class="flex-1 text-stone-900 dark:text-stone-50">
                        <slot v-if="datum" :data="datum" name="data" :open-modal="() => createOpen = true" />
                    </div>
                    <div v-if="description.permissions.destroy" class="flex w-8 items-center">
                        <button type="button" aria-label="Delete" class="text-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded-sm" @click.prevent="$emit('destroy', datum)">
                            <TrashIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </template>

            <template #no-data>
                <div class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400">
                    No data present in table
                </div>
            </template>

            <template #table-bottom>
                <div class="flex w-full flex-wrap items-center justify-between gap-3 text-sm text-stone-600 dark:text-stone-300">
                    <GlassButton
                        variant="secondary"
                        size="sm"
                        :disabled="!hasPreviousPage"
                        :href="paginator.prev_page_url ?? undefined"
                    >
                        Previous
                    </GlassButton>
                    <div class="text-xs">
                        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} -
                        {{ Math.min(currentPage * itemsPerPage, paginator?.total ?? 0) }}
                        of {{ paginator?.total ?? 0 }}
                    </div>
                    <GlassButton
                        variant="secondary"
                        size="sm"
                        :disabled="!hasNextPage"
                        :href="paginator.next_page_url ?? undefined"
                    >
                        Next
                    </GlassButton>
                </div>
            </template>
        </SporkTable>

        <GlassModal :open="createOpen" size="md" @close="close">
            <template #title>
                <slot name="modal-title">Create</slot>
            </template>
            <slot name="form" :open-modal="() => createOpen = true" />
            <template #footer>
                <GlassButton variant="secondary" size="sm" @click="createOpen = false">Close</GlassButton>
                <GlassButton size="sm" @click="$emit('save', form, () => createOpen = !createOpen)">Save</GlassButton>
            </template>
        </GlassModal>

        <ApplyTagModal
            :show="applyTagModalOpen && actionToRun && actionToRun.slug === 'apply-tag'"
            :type="description.model"
            :identifiers="selectedItems"
            :name="description.name"
            @close="applyTagModalOpen = false"
        />

        <GlassModal
            :open="executing && actionToRun && actionToRun.fields && actionToRun.slug !== 'apply-tag'"
            :title="actionToRun?.name ?? 'Run action'"
            size="md"
            @close="executing = false"
        >
            <div class="space-y-3">
                <div v-for="(field, fieldName) in actionToRun?.fields ?? {}" :key="fieldName" class="space-y-1">
                    <div class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ fieldName }}</div>
                    <SporkDynamicInput
                        v-model="form[fieldName]"
                        :type="field.type"
                        :autofocus="field?.autofocus ?? false"
                        :disabled-input="field?.disabled ?? false"
                        :editable-label="field?.editableLabel ?? false"
                        :error="hasErrors(fieldName)"
                        :options="field?.options ?? []"
                    />
                </div>
            </div>
            <template #footer>
                <GlassButton variant="secondary" size="sm" @click="executing = false">Cancel</GlassButton>
                <GlassButton size="sm" @click="$emit('execute', { actionToRun, selectedItems })">Apply</GlassButton>
            </template>
        </GlassModal>

        <GlassModal :open="credentialModal" title="Link credential" size="md" @close="credentialModal = false">
            <div class="flex flex-col gap-4">
                <GlassField label="Credential Name" :error="errors?.name">
                    <GlassInput v-model="valuesToSend.name" name="credential" :invalid="!!errors?.name" />
                </GlassField>

                <GlassField label="Credential Type" :error="errors?.type">
                    <GlassSelect v-model="credentialType" :invalid="!!errors?.type">
                        <option value="development">Development</option>
                        <option value="server">Servers</option>
                        <option value="domain">Domain</option>
                        <option value="registrar">Registrar</option>
                        <option value="finance">Finance</option>
                        <option value="ssh">SSH</option>
                        <option value="email">Email</option>
                        <option value="source">Source</option>
                    </GlassSelect>
                </GlassField>

                <GlassField label="Service" :error="valuesToSend.errors?.service">
                    <GlassSelect v-model="credentialService" :invalid="!!valuesToSend.errors?.service">
                        <option value="cloudflare">Cloudflare (global)</option>
                        <option value="namecheap">Namecheap (global)</option>
                        <option value="enom">Tucows/Enom (global)</option>
                        <option value="digitalocean">DigitalOcean</option>
                        <option value="imap">IMAP Email</option>
                        <option value="http_api">HTTP API</option>
                        <option value="plaid">Plaid</option>
                        <option value="privacy">Privacy</option>
                    </GlassSelect>
                </GlassField>

                <div v-for="(fieldMapping, i) in credentialForm" :key="i">
                    <SporkDynamicInput
                        v-model="credentialForm[i]"
                        :error="hasErrors(fieldMapping.name)"
                        :disabled-input="false"
                    />
                </div>

                <div v-if="Object.keys(valuesToSend.errors || {}).length" class="space-y-1 text-xs text-red-500 dark:text-red-400">
                    <div v-for="(message, field) in valuesToSend.errors" :key="field">
                        <span class="font-medium">{{ field }}:</span>
                        <span>{{ Array.isArray(message) ? message[0] : message }}</span>
                    </div>
                </div>
            </div>
            <template #footer>
                <GlassButton variant="secondary" size="sm" @click="credentialModal = false">Cancel</GlassButton>
                <GlassButton type="submit" size="sm" @click="saveCredentialType">Save</GlassButton>
            </template>
        </GlassModal>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { router, useForm } from '@inertiajs/vue3';
import { PlayIcon, ArrowPathIcon, ChevronDownIcon, TrashIcon } from "@heroicons/vue/24/outline";
import SporkTable from "@/Components/Spork/SporkTable.vue";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";
import ApplyTagModal from "@/Components/Spork/Molecules/ApplyTagModal.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassIconButton from "@/Components/Glass/GlassIconButton.vue";
import GlassModal from "@/Components/Glass/GlassModal.vue";
import GlassField from "@/Components/Glass/GlassField.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassSelect from "@/Components/Glass/GlassSelect.vue";

const props = defineProps({
    form: { type: Object, default: null },
    title: { type: String, default: '' },
    singular: { type: String, default: 'singular' },
    data: { type: Array, default: () => [] },
    paginator: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
    description: {
        default: () => ({
            actions: [],
            query_actions: [],
            fillable: [],
            fields: [],
            required: [],
            sorts: [],
            tags: [],
        }),
    },
    plural: { type: String, default: '' },
    apiLink: { type: String, default: '/api/crud/models' },
});

const emit = defineEmits(['index', 'destroy', 'save', 'execute', 'destroyMany', 'clearForm']);

const createOpen = ref(false);
const filtersOpen = ref(false);
const selectedItems = ref([]);
const itemsPerPage = computed(() => props.paginator.per_page ?? 15);
const actionToRun = ref(null);
const executing = ref(false);
const credentialModal = ref(false);

const fieldMappings = {
    namecheap: ['api_user', 'username', 'client_ip', 'access_token'],
    cloudflare: ['email', 'account_id', 'api_key'],
    enom: ['uid', 'pw'],
    digitalocean: ['api_key'],
    imap: ['username', 'password', 'host', 'port', 'encryption'],
    http_api: ['url', 'body'],
    plaid: ['access_token'],
    privacy: ['api_key'],
};

const openCreateModal = () => {
    emit('clearForm');
    createOpen.value = true;
};

const credentialService = ref(null);
const credentialType = ref(null);
const credentialForm = ref(null);

const valuesToSend = useForm({
    name: '',
    type: '',
    service: '',
    api_key: '',
    secret_key: '',
    access_token: '',
    refresh_token: '',
    settings: {},
});

const errors = valuesToSend?.errors ?? {};

const applyTagModalOpen = ref(false);

watch(() => actionToRun.value, (newVal) => {
    if (newVal?.fields) {
        Object.keys(newVal.fields).forEach((field) => {
            if (props.form) props.form[field] = '';
        });
        applyTagModalOpen.value = newVal.slug === 'apply-tag';
    }
});

watch(() => credentialService.value, (newVal) => {
    if (!(newVal in fieldMappings)) {
        throw new Error(newVal + " does not exist in the field mappings");
    }
    credentialForm.value = fieldMappings[newVal].map(fieldName => ({ name: fieldName, value: '' }));
    valuesToSend.name = newVal;
});

const hasPreviousPage = computed(() => props.paginator.prev_page_url !== null);
const hasNextPage = computed(() => props.paginator.next_page_url !== null);
const currentPage = computed(() => props.paginator.current_page);

const saveCredentialType = () => {
    if (!credentialForm?.value) return;

    const credentials = [...credentialForm.value].reduce((carry, input) => ({
        ...carry,
        [input.name]: input.value,
    }), {});

    for (let key in credentials) {
        if (key in valuesToSend) {
            valuesToSend[key] = credentials[key];
            delete credentials[key];
        }
    }

    valuesToSend.type = credentialType.value;
    valuesToSend.service = credentialService.value;
    valuesToSend.settings = {
        ...(valuesToSend.settings ? valuesToSend.settings : {}),
        ...credentials,
    };

    valuesToSend.post('/api/credentials');
};

const hasErrors = (error) => errors?.[error] ?? '';

const selectAll = (event) => {
    selectedItems.value = event.target.checked ? [...props.data] : [];
};

const close = () => {
    createOpen.value = false;
};

const executeActionOrOpenDialog = () => {
    if (actionToRun.value?.slug === 'apply-tag') {
        applyTagModalOpen.value = true;
        return;
    }
    executing.value = true;

    emit('execute', {
        selectedItems: selectedItems.value,
        actionToRun,
        next: () => {
            selectedItems.value = [];
            executing.value = false;
        },
    });
};

const openCredentialModal = () => {
    credentialModal.value = true;
};
</script>
