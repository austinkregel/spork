<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import GlassAuthLayout from '@/Layouts/GlassAuthLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GlassAuthLayout
        title="Email Verification"
        heading="Verify your email"
        subheading="Check your inbox for a confirmation link."
    >
        <p class="mb-4 text-sm text-stone-600 dark:text-stone-300">
            Before continuing, please verify your email address by clicking on the link we just emailed to you. If you didn't receive it, we'll happily send another.
        </p>

        <p
            v-if="verificationLinkSent"
            class="mb-4 rounded-md bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200"
            role="status"
        >
            A new verification link has been sent to your email address.
        </p>

        <form @submit.prevent="submit">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3 text-sm">
                    <Link
                        :href="route('profile.show')"
                        class="text-indigo-600 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:text-indigo-400 dark:focus-visible:ring-offset-stone-950 rounded-sm"
                    >
                        Edit profile
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-stone-600 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:text-stone-300 dark:focus-visible:ring-offset-stone-950 rounded-sm"
                    >
                        Log out
                    </Link>
                </div>

                <GlassButton type="submit" :disabled="form.processing">
                    Resend verification email
                </GlassButton>
            </div>
        </form>
    </GlassAuthLayout>
</template>
