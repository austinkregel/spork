<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormSection from '@/Components/FormSection.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassField from '@/Components/Glass/GlassField.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

const createApiTokenForm = useForm({
    name: '',
    permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});

const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = () => {
    createApiTokenForm.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => {
            displayingToken.value = true;
            createApiTokenForm.reset();
        },
    });
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    managingPermissionsFor.value = token;
};

const updateApiToken = () => {
    updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (managingPermissionsFor.value = null),
    });
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (apiTokenBeingDeleted.value = null),
    });
};
</script>

<template>
    <div>
        <!-- Generate API Token -->
        <FormSection @submitted="createApiToken">
            <template #title>
                Create API Token
            </template>

            <template #description>
                API tokens allow third-party services to authenticate with our application on your behalf.
            </template>

            <template #form>
                <div class="col-span-6 sm:col-span-4">
                    <GlassField v-slot="{ id, describedby, invalid }" label="Name" :error="createApiTokenForm.errors.name">
                        <GlassInput
                            :id="id"
                            v-model="createApiTokenForm.name"
                            :invalid="invalid"
                            :describedby="describedby"
                            autofocus
                        />
                    </GlassField>
                </div>

                <div v-if="availablePermissions.length > 0" class="col-span-6">
                    <div class="block text-sm font-medium text-stone-700 dark:text-stone-200">Permissions</div>

                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="permission in availablePermissions" :key="permission">
                            <label class="flex items-center">
                                <Checkbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                                <span class="ml-2 text-sm text-stone-600 dark:text-stone-400">{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </template>

            <template #actions>
                <ActionMessage :on="createApiTokenForm.recentlySuccessful" class="mr-3">
                    Created.
                </ActionMessage>

                <GlassButton type="submit" :disabled="createApiTokenForm.processing">
                    Create
                </GlassButton>
            </template>
        </FormSection>

        <div v-if="tokens.length > 0">
            <SectionBorder />

            <!-- Manage API Tokens -->
            <div class="mt-10 sm:mt-0">
                <ActionSection>
                    <template #title>
                        Manage API Tokens
                    </template>

                    <template #description>
                        You may delete any of your existing tokens if they are no longer needed.
                    </template>

                    <!-- API Token List -->
                    <template #content>
                        <div class="space-y-6">
                            <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between">
                                <div class="break-all dark:text-white">
                                    {{ token.name }}
                                </div>

                                <div class="flex items-center ml-2">
                                    <div v-if="token.last_used_ago" class="text-sm text-stone-400">
                                        Last used {{ token.last_used_ago }}
                                    </div>

                                    <button
                                        v-if="availablePermissions.length > 0"
                                        class="cursor-pointer ml-6 text-sm text-stone-400 underline"
                                        @click="manageApiTokenPermissions(token)"
                                    >
                                        Permissions
                                    </button>

                                    <button class="cursor-pointer ml-6 text-sm text-red-500" @click="confirmApiTokenDeletion(token)">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </ActionSection>
            </div>
        </div>

        <GlassModal :open="displayingToken" title="API Token" size="md" @close="displayingToken = false">
            <p>Please copy your new API token. For your security, it won't be shown again.</p>
            <div
                v-if="$page.props.jetstream.flash.token"
                class="mt-4 break-all rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/70 dark:bg-stone-900/70 px-4 py-2 font-mono text-sm text-stone-700 dark:text-stone-200"
            >
                {{ $page.props.jetstream.flash.token }}
            </div>
            <template #footer>
                <GlassButton variant="secondary" @click="displayingToken = false">Close</GlassButton>
            </template>
        </GlassModal>

        <GlassModal :open="managingPermissionsFor != null" title="API Token Permissions" size="md" @close="managingPermissionsFor = null">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label v-for="permission in availablePermissions" :key="permission" class="flex items-center">
                    <Checkbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
                    <span class="ml-2 text-sm text-stone-600 dark:text-stone-400">{{ permission }}</span>
                </label>
            </div>
            <template #footer>
                <GlassButton variant="secondary" @click="managingPermissionsFor = null">Cancel</GlassButton>
                <GlassButton :disabled="updateApiTokenForm.processing" @click="updateApiToken">Save</GlassButton>
            </template>
        </GlassModal>

        <GlassModal :open="apiTokenBeingDeleted != null" title="Delete API Token" size="sm" @close="apiTokenBeingDeleted = null">
            <p class="text-sm text-stone-700 dark:text-stone-200">Are you sure you would like to delete this API token?</p>
            <template #footer>
                <GlassButton variant="secondary" @click="apiTokenBeingDeleted = null">Cancel</GlassButton>
                <GlassButton variant="destructive" :disabled="deleteApiTokenForm.processing" @click="deleteApiToken">Delete</GlassButton>
            </template>
        </GlassModal>
    </div>
</template>
