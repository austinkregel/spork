<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import GlassAuthLayout from '@/Layouts/GlassAuthLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GlassAuthLayout
        title="Log in"
        heading="Welcome back"
        subheading="Sign in to your Spork account."
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

            <GlassField
                v-slot="{ id, describedby, invalid }"
                label="Password"
                :error="form.errors.password"
                required
            >
                <GlassInput
                    :id="id"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800"
                />
                <span>Remember me</span>
            </label>

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-indigo-600 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:text-indigo-400 dark:focus-visible:ring-offset-stone-950 rounded-sm"
                >
                    Forgot your password?
                </Link>

                <GlassButton
                    type="submit"
                    :disabled="form.processing"
                >
                    Log in
                </GlassButton>
            </div>
        </form>
    </GlassAuthLayout>
</template>
