<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GlassAuthLayout from '@/Layouts/GlassAuthLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
            passwordInput.value?.focus?.();
        },
    });
};
</script>

<template>
    <GlassAuthLayout
        title="Secure Area"
        heading="Confirm your password"
        subheading="This area is protected — please verify it's you."
    >
        <form class="space-y-4" @submit.prevent="submit">
            <GlassField
                v-slot="{ id, describedby, invalid }"
                label="Password"
                :error="form.errors.password"
                required
            >
                <GlassInput
                    :id="id"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required
                    autofocus
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <div class="flex justify-end pt-2">
                <GlassButton type="submit" :disabled="form.processing">
                    Confirm
                </GlassButton>
            </div>
        </form>
    </GlassAuthLayout>
</template>
