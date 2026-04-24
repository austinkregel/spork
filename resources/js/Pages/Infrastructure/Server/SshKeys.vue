<template>
    <ServerInfrastucture title="SSH Keys" :server="server" :navigation="navigation">
        <div class="space-y-6">
            <GlassCard
                title="Current automation credential"
                subtitle="Authorized key"
            >
                <textarea
                    class="block h-40 w-full rounded-lg border border-stone-300 bg-white/70 p-3 font-mono text-xs text-stone-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100"
                    readonly
                >{{ publicKey }}</textarea>

                <div class="mt-3 flex flex-wrap gap-3 text-xs text-stone-500 dark:text-stone-400">
                    <span>Fingerprint: {{ fingerprint }}</span>
                    <span v-if="server.credential?.updated_at">Updated {{ formatDate(server.credential.updated_at) }}</span>
                </div>

                <div class="mt-4 flex gap-2">
                    <GlassButton variant="secondary" @click="copyKey">Copy public key</GlassButton>
                    <GlassButton>Rotate key</GlassButton>
                </div>
            </GlassCard>

            <GlassCard title="How rotation works">
                <ol class="list-inside list-decimal space-y-2 text-sm text-stone-600 dark:text-stone-300">
                    <li>Click "Rotate key" and confirm the action.</li>
                    <li>Spork generates a new SSH keypair scoped to this server.</li>
                    <li>Add the new public key to <code class="font-mono text-xs">~/.ssh/authorized_keys</code>.</li>
                    <li>We validate connectivity and retire the previous key automatically.</li>
                </ol>
            </GlassCard>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
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
