<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus?.();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus?.();
            }
        },
    });
};
</script>

<template>
    <FormSection @submitted="updatePassword">
        <template #title>
            Update Password
        </template>

        <template #description>
            Ensure your account is using a long, random password to stay secure.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <GlassField v-slot="{ id, describedby, invalid }" label="Current Password" :error="form.errors.current_password">
                    <GlassInput
                        :id="id"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        autocomplete="current-password"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </GlassField>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <GlassField v-slot="{ id, describedby, invalid }" label="New Password" :error="form.errors.password">
                    <GlassInput
                        :id="id"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </GlassField>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <GlassField v-slot="{ id, describedby, invalid }" label="Confirm Password" :error="form.errors.password_confirmation">
                    <GlassInput
                        :id="id"
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </GlassField>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </ActionMessage>

            <GlassButton type="submit" :disabled="form.processing">
                Save
            </GlassButton>
        </template>
    </FormSection>
</template>
