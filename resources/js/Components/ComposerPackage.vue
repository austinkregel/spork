<template>
    <GlassSurface class="flex flex-col">
        <div class="flex h-full w-full flex-col gap-2 p-6">
            <p class="text-lg font-semibold text-stone-900 dark:text-stone-50">{{ props.composerPackage.name }}</p>
            <p class="text-sm text-stone-500 dark:text-stone-300">{{ props.composerPackage.description }}</p>
        </div>
        <div class="flex w-full justify-between border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-6 py-3 text-xs text-stone-500 dark:text-stone-400">
            <p>{{ props.composerPackage.version }}</p>
            <p>{{ downloadsOrDate }}</p>
        </div>
        <div class="flex w-full justify-end gap-2 border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-50/40 dark:bg-stone-900/40 p-3">
            <GlassButton
                v-if="isInstalled"
                variant="destructive"
                size="sm"
                :disabled="uninstalling || isEnabled || systemPackages.includes(props.composerPackage.name)"
                @click="uninstallPackage"
            >
                <template v-if="!uninstalling">Uninstall</template>
                <span v-else class="flex items-center gap-2">
                    <Spinner /> Uninstalling
                </span>
            </GlassButton>
            <GlassButton
                v-if="isInstalled && isEnabled"
                variant="secondary"
                size="sm"
                :disabled="systemPackages.includes(props.composerPackage.name)"
                @click="disablePackage"
            >
                <template v-if="!disabling">Disable</template>
                <span v-else class="flex items-center gap-2">
                    <Spinner /> Disabling
                </span>
            </GlassButton>
            <GlassButton
                v-if="isInstalled && !isEnabled && !systemPackages.includes(props.composerPackage.name)"
                size="sm"
                @click="enablePackage"
            >
                <template v-if="!enabling">Enable</template>
                <span v-else class="flex items-center gap-2">
                    <Spinner /> Enabling
                </span>
            </GlassButton>
            <GlassButton
                v-if="!isInstalled"
                size="sm"
                :disabled="installing"
                @click="installPackage"
            >
                <template v-if="!installing">Install</template>
                <span v-else class="flex items-center gap-2">
                    <Spinner /> Installing
                </span>
            </GlassButton>
        </div>

        <GlassModal
            :open="!!jobId || enabling || installing || uninstalling"
            :title="`Log of ${props.composerPackage.name}`"
            size="lg"
            @close="closeModal"
        >
            <div class="grid grid-cols-1">
                <VueTerm
                    v-if="jobId"
                    :job-id="jobId"
                    :event-handler="adminEventHandler"
                />
            </div>
            <template #footer>
                <GlassButton :disabled="installing" @click="jobId = null">Close</GlassButton>
            </template>
        </GlassModal>

        <GlassModal
            :open="showConfiguration"
            :title="`Configure ${props.composerPackage.name}`"
            size="lg"
            @close="showConfiguration = false"
        >
            <form class="flex w-full flex-col gap-4" @submit.prevent="_realEnablePackage">
                <GlassField label="Client Id" :error="enableServiceForm.errors.client_id">
                    <GlassInput
                        v-model="enableServiceForm.client_id"
                        placeholder="420"
                        :invalid="!!enableServiceForm.errors.client_id"
                    />
                </GlassField>
                <GlassField label="Client Secret" :error="enableServiceForm.errors.client_secret">
                    <GlassInput
                        v-model="enableServiceForm.client_secret"
                        name="__client_secret"
                        placeholder="af0020a8efa29feb34a3b2a93201a0f840a0282"
                        :invalid="!!enableServiceForm.errors.client_secret"
                    />
                </GlassField>
                <GlassField label="Redirect URL" :error="enableServiceForm.errors.redirect">
                    <GlassInput
                        v-model="enableServiceForm.redirect"
                        placeholder="https://aut.hair/callback/service"
                        :invalid="!!enableServiceForm.errors.redirect"
                    />
                </GlassField>
            </form>
            <template #footer>
                <GlassButton variant="secondary" :disabled="installing" @click="showConfiguration = false">Close</GlassButton>
                <GlassButton :disabled="enableServiceForm.processing" @click="_realEnablePackage">Save</GlassButton>
            </template>
        </GlassModal>
    </GlassSurface>
</template>

<script setup>
import { computed, ref, h } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from 'axios';
import VueTerm from '@/Components/VueTerm.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const Spinner = {
    render: () => h('svg', {
        class: 'h-4 w-4 motion-safe:animate-spin text-current',
        xmlns: 'http://www.w3.org/2000/svg',
        fill: 'none',
        viewBox: '0 0 24 24',
        'aria-hidden': 'true',
    }, [
        h('circle', { class: 'opacity-25', cx: 12, cy: 12, r: 10, stroke: 'currentColor', 'stroke-width': 4 }),
        h('path', { class: 'opacity-75', fill: 'currentColor', d: 'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z' }),
    ]),
};

const props = defineProps({
    composerPackage: { type: Object },
    eventHandler: { type: Function },
    enabled: { type: Object },
});

const disabling = ref(false);
const enabling = ref(false);
const installing = ref(false);
const uninstalling = ref(false);
const jobId = ref(null);
const systemPackages = ref(['socialiteproviders/manager']);
const showConfiguration = ref(false);
const enableServiceForm = useForm('enable service', {
    client_id: '',
    client_secret: '',
    redirect: '',
    name: '',
});

const isInstalled = computed(() => props.composerPackage?.installed);
const downloadsOrDate = computed(() => props.composerPackage?.time
    ?? (props.composerPackage?.downloads?.toLocaleString() + ' downloads'));

const isEnabled = computed(() => Object.keys(props.composerPackage?.drivers ?? {}).filter((key) => {
    const driverName = props.composerPackage.drivers?.[key];
    return Object.prototype.hasOwnProperty.call(props.enabled, driverName);
}).length > 0);

function closeModal() {
    jobId.value = null;
    showConfiguration.value = false;
    window.document.dispatchEvent(new Event('updatePackages'));
}

function installPackage() {
    installing.value = true;
    axios.post('/api/install', { name: props.composerPackage.name })
        .then(({ data }) => {
            showConfiguration.value = false;
            jobId.value = data.id;
        })
        .finally(() => {
            installing.value = false;
            window.document.dispatchEvent(new Event('updatePackages'));
        });
}

function uninstallPackage() {
    uninstalling.value = true;
    axios.post('/api/uninstall', { name: props.composerPackage.name })
        .then(({ data }) => { jobId.value = data.id; })
        .finally(() => {
            uninstalling.value = false;
            window.document.dispatchEvent(new Event('updatePackages'));
        });
}

function disablePackage() {
    disabling.value = true;
    axios.post('/api/disable', { name: props.composerPackage.name })
        .then(({ data }) => {
            showConfiguration.value = false;
            jobId.value = data.id;
            window.document.dispatchEvent(new Event('updatePackages'));
        })
        .finally(() => {
            disabling.value = false;
            window.document.dispatchEvent(new Event('updatePackages'));
        });
}

function enablePackage() {
    showConfiguration.value = !showConfiguration.value;
}

function _realEnablePackage() {
    enabling.value = true;
    enableServiceForm.name = props.composerPackage.name;
    return enableServiceForm.post('/api/enable', {
        onSuccess(data) {
            enableServiceForm.reset();
            jobId.value = data.id;
            showConfiguration.value = false;
            enabling.value = false;
            window.document.dispatchEvent(new Event('updatePackages'));
        },
    });
}

function adminEventHandler() {
    if (typeof props.eventHandler === 'function') {
        props.eventHandler(...arguments);
    }
}
</script>
