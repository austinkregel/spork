<template>
    <AppLayout title="Connect existing host">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">
                        Connect existing host
                    </h1>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400 max-w-3xl">
                        Enroll a bare metal machine or existing VM. This registers the host under your SSH credential and binds it using the OS machine-id.
                    </p>
                </div>

                <SporkButton secondary @click="() => router.visit(route('servers.index'))">
                    Back to Infrastructure
                </SporkButton>
            </div>

            <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-4 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                            One-line enrollment
                        </p>
                        <p class="text-sm text-stone-600 dark:text-stone-300 mt-1">
                            Run this on the host as root (or with sudo).
                        </p>
                    </div>

                    <SporkButton primary @click="copy">
                        Copy
                    </SporkButton>
                </div>

                <pre class="text-xs font-mono rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-900 text-stone-700 dark:text-stone-100 p-3 overflow-x-auto">{{ command }}</pre>

                <div class="text-xs text-stone-500 dark:text-stone-400 space-y-1">
                    <p>
                        The host will call back to Spork with <span class="font-mono">machine_id</span>, hostname, and IP. No provider is required.
                    </p>
                    <p>
                        Next step: wire your external command server + websocket listener so heartbeats and inventory update automatically.
                    </p>
                </div>
            </div>

            <div v-if="!canEnroll" class="border border-dashed border-amber-400 rounded-lg p-4 bg-amber-50 dark:bg-amber-900/30">
                <p class="text-sm text-amber-800 dark:text-amber-100">
                    No SSH credential is available for enrollment yet. Create an SSH credential first, then return here.
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
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


