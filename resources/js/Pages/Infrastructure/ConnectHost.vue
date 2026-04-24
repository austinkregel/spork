<template>
    <AppLayout title="Connect existing host">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard
                title="Connect existing host"
                subtitle="Bare metal &amp; existing VMs"
            >
                <template #actions>
                    <GlassButton variant="secondary" @click="router.visit(route('infrastructure.servers.index'))">
                        Back to Infrastructure
                    </GlassButton>
                </template>
                <p class="max-w-3xl text-sm text-stone-600 dark:text-stone-300">
                    Enroll a bare metal machine or existing VM. This registers the host under your SSH credential and binds it using the OS machine-id.
                </p>
            </GlassCard>

            <GlassCard
                title="One-line enrollment"
                subtitle="Run this on the host as root (or with sudo)."
            >
                <template #actions>
                    <GlassButton @click="copy">Copy</GlassButton>
                </template>

                <pre class="overflow-x-auto rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-50/70 dark:bg-stone-900/70 p-3 font-mono text-xs text-stone-700 dark:text-stone-100">{{ command }}</pre>

                <div class="mt-3 space-y-1 text-xs text-stone-500 dark:text-stone-400">
                    <p>
                        The host will call back to Spork with <span class="font-mono">machine_id</span>, hostname, and IP. No provider is required.
                    </p>
                    <p>
                        Next step: wire your external command server + websocket listener so heartbeats and inventory update automatically.
                    </p>
                </div>
            </GlassCard>

            <GlassCard
                v-if="!canEnroll"
                class="border-amber-400/50 dark:border-amber-500/40"
            >
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    No SSH credential is available for enrollment yet. Create an SSH credential first, then return here.
                </p>
            </GlassCard>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    command: {
        type: String,
        default: '',
    },
});

const canEnroll = computed(() => Boolean(props.command));

const copy = () => {
    if (!props.command) {
        return;
    }

    navigator.clipboard?.writeText(props.command);
};
</script>
