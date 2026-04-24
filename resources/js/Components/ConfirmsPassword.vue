<script setup>
import { ref, reactive, nextTick } from 'vue';
import axios from 'axios';
import InputError from './InputError.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const emit = defineEmits(['confirmed']);

defineProps({
    title: { type: String, default: 'Confirm Password' },
    content: { type: String, default: 'For your security, please confirm your password to continue.' },
    button: { type: String, default: 'Confirm' },
});

const confirmingPassword = ref(false);

const form = reactive({
    password: '',
    error: '',
    processing: false,
});

const passwordInput = ref(null);

const startConfirmingPassword = () => {
    axios.get(route('password.confirmation')).then((response) => {
        if (response.data.confirmed) {
            emit('confirmed');
        } else {
            confirmingPassword.value = true;
            setTimeout(() => passwordInput.value?.focus?.(), 250);
        }
    });
};

const confirmPassword = () => {
    form.processing = true;

    axios.post(route('password.confirm'), { password: form.password })
        .then(() => {
            form.processing = false;
            closeModal();
            nextTick().then(() => emit('confirmed'));
        })
        .catch((error) => {
            form.processing = false;
            form.error = error.response.data.errors.password[0];
            passwordInput.value?.focus?.();
        });
};

const closeModal = () => {
    confirmingPassword.value = false;
    form.password = '';
    form.error = '';
};
</script>

<template>
    <span>
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <GlassModal :open="confirmingPassword" :title="title" size="sm" @close="closeModal">
            <p class="text-sm text-stone-700 dark:text-stone-200">{{ content }}</p>

            <div class="mt-4">
                <GlassInput
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="w-3/4"
                    placeholder="Password"
                    autocomplete="current-password"
                    :invalid="!!form.error"
                    @enter="confirmPassword"
                />
                <InputError :message="form.error" class="mt-2" />
            </div>

            <template #footer>
                <GlassButton variant="secondary" @click="closeModal">Cancel</GlassButton>
                <GlassButton :disabled="form.processing" @click="confirmPassword">{{ button }}</GlassButton>
            </template>
        </GlassModal>
    </span>
</template>
