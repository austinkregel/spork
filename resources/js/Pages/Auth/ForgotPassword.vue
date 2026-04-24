<script setup>
import { useForm } from '@inertiajs/vue3';
import GlassAuthLayout from '@/Layouts/GlassAuthLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GlassAuthLayout
        title="Forgot Password"
        heading="Reset your password"
        subheading="Enter your email and we'll send you a reset link."
    >
        <p
            v-if="status"
            class="mb-4 rounded-md bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200"
            role="status"
        >
            {{ status }}
        </p>

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

            <div class="flex justify-end pt-2">
                <GlassButton type="submit" :disabled="form.processing">
                    Email password reset link
                </GlassButton>
            </div>
        </form>
    </GlassAuthLayout>
</template>
