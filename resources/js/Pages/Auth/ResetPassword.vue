<script setup>
import { useForm } from '@inertiajs/vue3';
import GlassAuthLayout from '@/Layouts/GlassAuthLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GlassAuthLayout
        title="Reset Password"
        heading="Choose a new password"
        subheading="Make it long, unique, and stored in a password manager."
    >
        <form class="space-y-4" @submit.prevent="submit">
            <GlassField
                v-slot="{ id, describedby, invalid }"
                label="Email"
                :error="form.errors.email"
                required
            >
                <GlassInput
                    :id="id"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                    autofocus
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <GlassField
                v-slot="{ id, describedby, invalid }"
                label="New password"
                :error="form.errors.password"
                required
            >
                <GlassInput
                    :id="id"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <GlassField
                v-slot="{ id, describedby, invalid }"
                label="Confirm new password"
                :error="form.errors.password_confirmation"
                required
            >
                <GlassInput
                    :id="id"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <div class="flex justify-end pt-2">
                <GlassButton type="submit" :disabled="form.processing">
                    Reset password
                </GlassButton>
            </div>
        </form>
    </GlassAuthLayout>
</template>
