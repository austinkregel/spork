<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GlassAuthLayout from '@/Layouts/GlassAuthLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value = !recovery.value;
    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value?.focus?.();
        form.code = '';
    } else {
        codeInput.value?.focus?.();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <GlassAuthLayout
        title="Two-factor confirmation"
        heading="Two-factor authentication"
        :subheading="recovery
            ? 'Enter one of your emergency recovery codes.'
            : 'Enter the code from your authenticator app.'"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <GlassField
                v-if="!recovery"
                v-slot="{ id, describedby, invalid }"
                label="Authentication code"
                :error="form.errors.code"
            >
                <GlassInput
                    :id="id"
                    ref="codeInput"
                    v-model="form.code"
                    type="text"
                    autocomplete="one-time-code"
                    autofocus
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <GlassField
                v-else
                v-slot="{ id, describedby, invalid }"
                label="Recovery code"
                :error="form.errors.recovery_code"
            >
                <GlassInput
                    :id="id"
                    ref="recoveryCodeInput"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <div class="flex items-center justify-between gap-3 pt-2">
                <button
                    type="button"
                    class="text-sm text-indigo-600 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:text-indigo-400 dark:focus-visible:ring-offset-stone-950 rounded-sm"
                    @click="toggleRecovery"
                >
                    {{ recovery ? 'Use an authentication code' : 'Use a recovery code' }}
                </button>

                <GlassButton type="submit" :disabled="form.processing">
                    Log in
                </GlassButton>
            </div>
        </form>
    </GlassAuthLayout>
</template>
