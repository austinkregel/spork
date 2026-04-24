<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import GlassAuthLayout from '@/Layouts/GlassAuthLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const page = usePage();
const showsTerms = computed(() => Boolean(page.props.jetstream?.hasTermsAndPrivacyPolicyFeature));

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GlassAuthLayout
        title="Register"
        heading="Create your account"
        subheading="Spin up a personal Spork workspace in seconds."
    >
        <form class="space-y-4" @submit.prevent="submit">
            <GlassField
                v-slot="{ id, describedby, invalid }"
                label="Name"
                :error="form.errors.name"
                required
            >
                <GlassInput
                    :id="id"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    required
                    autofocus
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

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
                    autocomplete="new-password"
                    required
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </GlassField>

            <GlassField
                v-slot="{ id, describedby, invalid }"
                label="Confirm password"
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

            <div v-if="showsTerms" class="space-y-1">
                <label class="flex items-start gap-2 text-sm text-stone-700 dark:text-stone-200">
                    <input
                        v-model="form.terms"
                        type="checkbox"
                        required
                        class="mt-0.5 h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800"
                    />
                    <span>
                        I agree to the
                        <a target="_blank" :href="route('terms.show')" class="text-indigo-600 underline-offset-4 hover:underline dark:text-indigo-400">Terms of Service</a>
                        and
                        <a target="_blank" :href="route('policy.show')" class="text-indigo-600 underline-offset-4 hover:underline dark:text-indigo-400">Privacy Policy</a>.
                    </span>
                </label>
                <p v-if="form.errors.terms" class="text-xs text-red-600 dark:text-red-400">
                    {{ form.errors.terms }}
                </p>
            </div>

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link
                    :href="route('login')"
                    class="text-sm text-indigo-600 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:text-indigo-400 dark:focus-visible:ring-offset-stone-950 rounded-sm"
                >
                    Already registered?
                </Link>

                <GlassButton type="submit" :disabled="form.processing">
                    Register
                </GlassButton>
            </div>
        </form>
    </GlassAuthLayout>
</template>
