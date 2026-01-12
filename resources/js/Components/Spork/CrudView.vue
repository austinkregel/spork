<template>
    <div class="w-full h-full flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-1" v-if="title">
                <p class="text-sm uppercase tracking-wide text-stone-500 dark:text-stone-400">
                    {{ singular }}
                </p>
                <h2 class="text-2xl font-semibold text-stone-900 dark:text-white">
                    {{ title }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <SporkButton v-if="singular !== 'credential'" @click="openCreateModal">
                    Create New {{ singular }}
                </SporkButton>

                <SporkButton v-else @click="openCredentialModal">
                    Create New {{ singular }}
                </SporkButton>
            </div>
        </div>

        <SporkTable :headers="[]" :data="data">
            <template #table-top>
                <div class="relative z-0 w-full flex flex-wrap items-center justify-between gap-4 rounded-xl border border-stone-200 dark:border-stone-800 bg-white/90 dark:bg-stone-900/80 px-4 py-3 shadow-sm">
                    <div class="flex gap-3 items-center">
                        <input
                            type="checkbox"
                            class="h-4 w-4 cursor-pointer rounded border-stone-300 dark:border-stone-700 text-indigo-600 focus:ring-indigo-500"
                            :checked="data.length > 0 && selectedItems.length === data.length"
                            @click="selectAll"
                        />

                        <span v-if="selectedItems.length > 0" class="text-sm text-stone-700 dark:text-stone-300">
                            {{ selectedItems.length }} selected
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            v-if="description?.actions?.length > 0 && selectedItems.length > 0"
                            class="flex items-center gap-2 rounded-full border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-900/70 px-3 py-1.5 text-sm text-stone-700 dark:text-stone-200"
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

                            <button type="button" class="text-indigo-600 dark:text-indigo-300 hover:text-indigo-500" @click.prevent="executeActionOrOpenDialog">
                                <PlayIcon v-if="!executing || !applyTagModalOpen" class="w-5 h-5" />
                                <ArrowPathIcon v-else class="w-5 h-5 animate-spin" />
                            </button>
                        </div>

                        <button
                            v-if="description.permissions.delete"
                            class="inline-flex items-center justify-center rounded-full border border-stone-300 dark:border-stone-700 p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10"
                            @click="$emit('destroyMany', selectedItems)"
                        >
                            <DynamicIcon icon-name="TrashIcon" class="w-4 h-4" />
                        </button>

                        <button
                            @click="filtersOpen = !filtersOpen"
                            class="inline-flex items-center gap-1 rounded-full border border-stone-300 dark:border-stone-700 px-3 py-1.5 text-xs font-semibold text-stone-600 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800"
                        >
                            Filters
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div
                            v-if="filtersOpen"
                            class="absolute right-0 top-full mt-2 w-64 rounded-xl border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900 shadow-xl"
                        >
                            <div class="border-b border-stone-200 dark:border-stone-800 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                Filters
                            </div>
                            <div class="p-4 space-y-3">
                                <label class="text-xs font-medium text-stone-500 dark:text-stone-300">
                                    Items per page
                                    <select
                                        class="mt-1 w-full rounded-md border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        @change="(e) => router.reload({ search: { limit: e.target.value } })"
                                    >
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="100">100</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

                <template #datum="{ datum }">
                    <div class="flex items-center w-full px-2 py-3 rounded-xl hover:bg-stone-50 dark:hover:bg-stone-900/40 transition">
                        <div class="w-6 mx-4">
                            <input
                                type="checkbox"
                                class="h-3 w-3 cursor-pointer rounded border-stone-300 dark:border-stone-700 text-indigo-600 focus:ring-indigo-500"
                                v-model="selectedItems"
                                :value="datum"
                            />
                        </div>
                        <div class="flex-1 dark:text-stone-50 text-black">
                            <slot v-if="datum" class="flex-1" :data="datum" name="data" :open-modal="() => createOpen = true"></slot>
                        </div>
                        <div v-if="description.permissions.destroy" class="flex items-center w-8">
                            <button type="button" @click.prevent="$emit('destroy', datum)">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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
                    <div class="w-full flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 dark:border-stone-800 bg-white/80 dark:bg-stone-900/80 px-4 py-3 text-sm text-stone-600 dark:text-stone-300">
                        <Link
                            :href="paginator.prev_page_url ?? '#'"
                            :disabled="!hasPreviousPage"
                            :class="[
                                'inline-flex items-center rounded-full border px-3 py-1 font-medium',
                                !hasPreviousPage
                                    ? 'border-stone-200 dark:border-stone-700 text-stone-400 cursor-not-allowed'
                                    : 'border-stone-300 dark:border-stone-600 text-stone-700 dark:text-stone-200 hover:border-indigo-400 hover:text-indigo-600',
                            ]"
                        >
                            Previous
                        </Link>
                        <div class="text-xs">
                            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} -
                            {{ Math.min(currentPage * itemsPerPage, paginator?.total ?? 0) }}
                            of {{ paginator?.total ?? 0 }}
                        </div>
                        <Link
                            :href="paginator.next_page_url ?? '#'"
                            :disabled="!hasNextPage"
                            :class="[
                                'inline-flex items-center rounded-full border px-3 py-1 font-medium',
                                !hasNextPage
                                    ? 'border-stone-200 dark:border-stone-700 text-stone-400 cursor-not-allowed'
                                    : 'border-stone-300 dark:border-stone-600 text-stone-700 dark:text-stone-200 hover:border-indigo-400 hover:text-indigo-600',
                            ]"
                        >
                            Next
                        </Link>
                    </div>
                </template>
            </SporkTable>
        </div>
    <Modal :show="createOpen" @close="close">
        <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-700 px-6 py-4">
            <slot name="modal-title">Create Modal</slot>
            <button @click="close" class="focus:outline-none text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                <DynamicIcon icon-name="XMarkIcon" class="w-5 h-5" />
            </button>
        </div>
        <div class="flex flex-col gap-4 px-6 py-4">
            <slot name="form" :open-modal="() => createOpen = true"></slot>
            <div class="flex justify-end gap-3">
                <SporkButton
                    @click.prevent="async () => {
                        createOpen = false;
                    }"
                >
                    Close
                </SporkButton>
                <SporkButton
                    primary
                    @click.prevent="async () => {
                        $emit('save', form, () => createOpen = !createOpen);
                    }"
                >
                    Save
                </SporkButton>
            </div>
        </div>
    </Modal>

    <ApplyTagModal
        :show="applyTagModalOpen && actionToRun && actionToRun.slug === 'apply-tag'"
        :type="description.model"
        @close="() => applyTagModalOpen = false"
        :identifiers="selectedItems"
        :name="description.name"
    >
    </ApplyTagModal>

    <Modal :show="executing && actionToRun && actionToRun.fields && actionToRun.slug !== 'apply-tag'">
        <div class="flex flex-col gap-4 px-6 py-4">
            <div v-for="(field, fieldName) in actionToRun.fields" :key="fieldName" class="space-y-1">
                <div class="text-sm font-medium text-stone-600 dark:text-stone-200">
                    {{ fieldName }}
                </div>
                <SporkDynamicInput
                    v-model="form[fieldName]"
                    :type="field.type"
                    :autofocus="field?.autofocus ?? false"
                    :disabled-input="field?.disabled ?? false"
                    :editable-label="field?.editableLabel ?? false"
                    :error="hasErrors(fieldName)"
                    :options="field?.options ?? []"
                >
                </SporkDynamicInput>
            </div>

            <div class="flex justify-end">
                <SporkButton primary @click.prevent="$emit('execute', { actionToRun, selectedItems })">
                    Apply
                </SporkButton>
            </div>
        </div>
    </Modal>

    <Modal :show="credentialModal">
        <div class="flex flex-col gap-4 px-6 py-4">
            <div class="flex justify-between items-center border-b border-stone-200 dark:border-stone-700 pb-2">
                <div class="text-lg font-semibold text-stone-900 dark:text-white">
                    Link credential
                </div>
                <button @click="credentialModal = false" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                    <DynamicIcon icon-name="XMarkIcon" class="w-5 h-5" />
                </button>
            </div>
            <div class="flex flex-col gap-4">
                <label class="text-sm font-medium text-stone-600 dark:text-stone-200">
                    Credential Name
                    <SporkInput
                        name="credential"
                        v-model="valuesToSend.name"
                        class="mt-1"
                    />
                    <span v-if="errors?.name" class="text-xs text-red-500 dark:text-red-400">
                        {{ valuesToSend.errors?.name }}
                    </span>
                </label>
                <label class="text-sm font-medium text-stone-600 dark:text-stone-200">
                    Credential Type
                    <SporkSelect v-model="credentialType" class="mt-1">
                        <template #options>
                            <option value="development">Development</option>
                            <option value="server">Servers</option>
                            <option value="domain">Domain</option>
                            <option value="registrar">Registrar</option>
                            <option value="finance">Finance</option>
                            <option value="ssh">SSH</option>
                            <option value="email">Email</option>
                            <option value="source">Source</option>
                        </template>
                    </SporkSelect>
                    <span v-if="errors?.type" class="text-xs text-red-500 dark:text-red-400">
                        {{ valuesToSend.errors?.type }}
                    </span>
                </label>
                <label class="text-sm font-medium text-stone-600 dark:text-stone-200">
                    Service
                    <SporkSelect v-model="credentialService" class="mt-1">
                        <template #options>
                            <option value="cloudflare">Cloudflare (global)</option>
                            <option value="namecheap">Namecheap (global)</option>
                            <option value="enom">Tucows/Enom (global)</option>
                            <option value="digitalocean">DigitalOcean</option>
                            <option value="imap">IMAP Email</option>
                            <option value="http_api">HTTP API</option>
                            <option value="plaid">Plaid</option>
                            <option value="privacy">Privacy</option>
                        </template>
                    </SporkSelect>
                    <span v-if="valuesToSend.errors?.service" class="text-xs text-red-500 dark:text-red-400">
                        {{ valuesToSend.errors?.service }}
                    </span>
                </label>

                <div v-for="(fieldMapping, i) in credentialForm" :key="i">
                    <SporkDynamicInput
                        v-model="credentialForm[i]"
                        :error="hasErrors(fieldMapping.name)"
                        :disabled-input="false"
                    />
                </div>
                <div v-if="Object.keys(valuesToSend.errors || {}).length" class="text-xs text-red-500 dark:text-red-400 space-y-1">
                    <div v-for="(message, field) in valuesToSend.errors" :key="field">
                        <span class="font-medium">{{ field }}:</span>
                        <span>{{ Array.isArray(message) ? message[0] : message }}</span>
                    </div>
                </div>
                <div class="flex justify-end">
                    <SporkButton type="submit" primary @click.prevent="saveCredentialType">
                        Save
                    </SporkButton>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import {ref, computed, watch} from 'vue';
import axios from 'axios';
import { PlayIcon, ArrowPathIcon } from "@heroicons/vue/24/outline";
import SporkButton from './SporkButton.vue';
import {router, Link, useForm, usePage} from '@inertiajs/vue3';
import SporkTable from "@/Components/Spork/SporkTable.vue";
import Modal from "@/Components/Modal.vue";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";
import ApplyTagModal from "@/Components/Spork/Molecules/ApplyTagModal.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkSelect from "@/Components/Spork/SporkSelect.vue";
const {
  form,
  title,
  singular,
  save,
  upload,
  data,
  paginator,
  settings,
  description,
    apiLink,
} = defineProps({
  form: {
    type: Object,
    default: null,
  },
  title: {
    type: String,
    default: '',
  },
  singular: {
    type: String,
    default: 'singular',
  },
  // getters
  data: {
    type: Array,
    default: () => [],
  },
  paginator: {
    type: Object,
    default: () => ({}),
  },
  settings: {
    type: Object,
    default: () => ({})
  },
  description: {
    default :  () => ({
        actions: [],
          query_actions: [],
          fillable: [],
          fields: [],
          required: [],
          sorts: [],
        tags: [],
    })
  },
    plural: {
        type: String,
        default: ''
    },
    apiLink: {
        type: String,
        default: '/api/crud/models'
    }
})
const $emit = defineEmits([
    'index',
    'destroy',
    'save',
    'execute',
    'destroyMany',
])

const createOpen = ref(false);
const filtersOpen = ref(false);
const selectedItems = ref([]);
const itemsPerPage = computed(() => paginator.per_page ?? 15);
const actionToRun = ref(null);
const searchQuery = ref(localStorage.getItem('searchQuery') ? localStorage.getItem('searchQuery') : '');
const debounceSearch = ref(null);
const executing = ref(false);
const credentialModal = ref(false);
const selectedTagToApply = ref(null);

const fieldMappings = {
    namecheap: [
        'api_user',
        'username',
        'client_ip',
        'access_token',
    ],
    cloudflare: [
        'email',
        'account_id',
        'api_key',
    ],
    enom: ['uid','pw'],
    digitalocean: [
        'api_key',
    ],
    imap: [
        'username',
        'password',
        'host',
        'port',
        'encryption',
    ],
    http_api: [
        'url',
        'body',
    ],
    plaid: [
        'access_token',
    ],
    privacy: [
        'api_key',
    ]
}

const openCreateModal = () => {
    $emit('clearForm');
    createOpen.value = true;
}
const page = usePage();
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
})

const errors = valuesToSend?.errors ?? []

watch(
    // This watches for a selected action, so we can set up the form if there are any needed fields
    () => actionToRun.value,
    (newVal) => {
        if (newVal?.fields) {
            form.value = {};
            Object.keys(newVal.fields).forEach((field) => {
                form[field] = '';
            })

            applyTagModalOpen.value = newVal.slug === 'apply-tag';
        }
    }
)
watch(
    () => credentialService.value,
    (newVal) => {

        if (!(newVal in fieldMappings)) {
            throw new Error(newVal + " does not exist in the field mappings" )
        }

        credentialForm.value = fieldMappings[newVal].map(fieldName => ({
            name: fieldName,
            value: '',
        }))
        valuesToSend.name = newVal;
    }
)

const hasPreviousPage = computed(() => {
  return paginator.prev_page_url !== null;
});

const hasNextPage = computed(() => {
  return paginator.next_page_url !== null;
});
const total = computed(() => paginator.total)
const currentPage = computed(() => {
  return paginator.current_page;
});
const lastPage = computed(() => Math.max(paginator.total / paginator.per_page, 1));

const saveCredentialType = () => {
    if (!credentialForm?.value) {
        return;
    }

    const credentials = [...credentialForm.value].reduce((carry, input) => ({
        ...carry,
        [input.name]: input.value,
    }), {});

    // Since our actual credentials objects treat some parts of the credential as a property on the object, and others as a property in the settings
    // we need to adjust what we send to account for that.
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

    valuesToSend.post('/api/credentials')
    // router.reload({
    //     only: ['data', 'paginator']
    // })
};
const hasErrors = (error) => {
  if (!errors) {
    return '';
  }

  return errors[error];
};
const selectAll = (event) => {
  if (event.target.checked) {
    selectedItems.value = [...data];
  } else {
    selectedItems.value = [];
  }
}
const close = () => {
  createOpen.value = false;
}
const executeActionOrOpenDialog = async () => {
    if (actionToRun.value?.slug === 'apply-tag') {
        applyTagModalOpen.value = true;
        return;
    }
    executing.value = true;

  $emit('execute', {
      selectedItems: selectedItems.value,
      actionToRun,
      next: () => {
          selectedItems.value = [];
          executing.value = false;
      }
  })
}

const applyTagModalOpen = ref(false);
const applyTag = async () => {
    if (!selectedTagToApply.value) {
        return;
    }

    await Promise.all(selectedItems.value.map(async (item) => {
        await axios.post(`${apiLink}/${item.id}/tags`, {
            tags: [selectedTagToApply.value]
        })
    }))

    router.reload({
        only: ['data', 'paginator', 'description']
    })
}
// const searchQuery = (newVal, oldVal) => {
//     if (debounceSearch !== null) {
//         clearTimeout(this.debounceSearch)
//     }
//     debounceSearch.value = setTimeout(() => {
//         localStorage.setItem('searchQuery', newVal);
//         this.$emit('index', {
//             page: 1,
//             limit: 15,
//             filter: {
//                 q: newVal
//             }
//         });
//
//     }, 400);
// }

const openCredentialModal = () => {
    credentialModal.value = true;
}
</script>
