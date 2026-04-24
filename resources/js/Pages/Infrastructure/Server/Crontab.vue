<template>
    <ServerInfrastucture title="Crontab" :server="server" :navigation="navigation">
        <div class="space-y-6">
            <GlassCard
                title="Current crontab entries"
                subtitle="Scheduled tasks"
            >
                <textarea
                    class="block min-h-[320px] w-full rounded-lg border border-stone-300 bg-white/70 p-4 font-mono text-sm text-stone-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100"
                    readonly
                >{{ cronContent }}</textarea>

                <div class="mt-4 flex gap-2">
                    <GlassButton variant="secondary" @click="copyCrontab">Copy crontab</GlassButton>
                    <GlassButton>Open editor</GlassButton>
                </div>
            </GlassCard>

            <GlassCard title="Upcoming runs">
                <ul class="space-y-2 text-sm text-stone-600 dark:text-stone-300">
                    <li v-for="item in nextRuns" :key="item.command">
                        <span class="font-medium text-stone-800 dark:text-stone-100">{{ item.command }}</span>
                        <span class="ml-2 text-xs text-stone-500 dark:text-stone-400">Next: {{ item.next }}</span>
                    </li>
                    <li v-if="!nextRuns.length">Unable to calculate future runs yet.</li>
                </ul>
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
const cronContent = computed(() => props.server.crontab ?? '# No crontab entries pulled from the agent yet.');

const nextRuns = computed(() => {
    if (!props.server.crontab_entries) {
        return [];
    }

    return props.server.crontab_entries.map((entry) => ({
        command: entry.command,
        next: entry.next_run ? dayjs(entry.next_run).format('MMM D, h:mm A') : 'Unknown',
    }));
});

const copyCrontab = () => {
    navigator.clipboard?.writeText(cronContent.value);
};
</script>
