<template>
    <ServerInfrastucture title="SSH Keys" :server="server" :navigation="navigation">
        <div class="space-y-6">
            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-4">
                <header>
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                        Authorized key
                    </p>
                    <h2 class="text-xl font-semibold text-stone-900 dark:text-white">
                        Current automation credential
                    </h2>
                </header>

                <textarea
                    class="w-full h-40 text-xs font-mono rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-900 text-stone-700 dark:text-stone-100 p-3"
                    readonly
                >{{ publicKey }}</textarea>

                <div class="flex flex-wrap gap-3 text-xs text-stone-500 dark:text-stone-400">
                    <span>Fingerprint: {{ fingerprint }}</span>
                    <span v-if="server.credential?.updated_at">Updated {{ formatDate(server.credential.updated_at) }}</span>
                </div>

                <div class="flex gap-3">
                    <SporkButton secondary @click="copyKey">
                        Copy public key
                    </SporkButton>
                    <SporkButton primary>
                        Rotate key
                    </SporkButton>
                </div>
            </section>

            <section class="border border-dashed border-stone-300 dark:border-stone-700 rounded-lg p-6 bg-stone-50 dark:bg-stone-900/40 space-y-3">
                <p class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                    How rotation works
                </p>
                <ol class="list-decimal list-inside text-sm text-stone-600 dark:text-stone-300 space-y-2">
                    <li>Click “Rotate key” and confirm the action.</li>
                    <li>Spork generates a new SSH keypair scoped to this server.</li>
                    <li>Add the new public key to <code class="font-mono text-xs">~/.ssh/authorized_keys</code>.</li>
                    <li>We validate connectivity and retire the previous key automatically.</li>
                </ol>
            </section>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import { computed } from "vue";
import dayjs from "dayjs";
import { buildServerNavigation } from '@/Pages/Infrastructure/serverNavigation';

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

const navigation = computed(() => buildServerNavigation(props.server));
const publicKey = computed(() => props.server.credential?.settings?.pub_key ?? 'No public key stored yet.');
const fingerprint = computed(() => props.server.credential?.settings?.fingerprint ?? 'Unknown fingerprint');

const formatDate = (value) => dayjs(value).format('MMM D, YYYY h:mm A');

const copyKey = () => {
    navigator.clipboard?.writeText(publicKey.value);
};
</script>
