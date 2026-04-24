<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            Delete Account
        </template>

        <template #description>
            Permanently delete your account.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-stone-600 dark:text-stone-400">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
            </div>

            <div class="mt-5">
                <GlassButton variant="destructive" @click="confirmUserDeletion">
                    Delete Account
                </GlassButton>
            </div>

            <GlassModal :open="confirmingUserDeletion" title="Delete Account" size="md" @close="closeModal">
                <p class="text-sm text-stone-700 dark:text-stone-200">
                    Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                </p>

                <div class="mt-4">
                    <GlassInput
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-3/4"
                        placeholder="Password"
                        autocomplete="current-password"
                        :invalid="!!form.errors.password"
                        @enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="mt-2 text-xs text-red-500 dark:text-red-400">{{ form.errors.password }}</p>
                </div>

                <template #footer>
                    <GlassButton variant="secondary" @click="closeModal">Cancel</GlassButton>
                    <GlassButton variant="destructive" :disabled="form.processing" @click="deleteUser">
                        Delete Account
                    </GlassButton>
                </template>
            </GlassModal>
        </template>
    </ActionSection>
</template>
