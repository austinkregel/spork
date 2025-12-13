<template>
    <ServerInfrastucture title="Crontab" :server="server" :navigation="navigation">
        <div class="space-y-6">
            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm">
                <header class="mb-4">
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                        Scheduled tasks
                    </p>
                    <h2 class="text-xl font-semibold text-stone-900 dark:text-white">
                        Current crontab entries
                    </h2>
                </header>

                <textarea
                    class="w-full min-h-[320px] text-sm font-mono rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-900 text-stone-700 dark:text-stone-100 p-4"
                    readonly
                >{{ cronContent }}</textarea>

                <div class="flex gap-3">
                    <SporkButton secondary @click="copyCrontab">
                        Copy crontab
                    </SporkButton>
                    <SporkButton primary>
                        Open editor
                    </SporkButton>
                </div>
            </section>

            <section class="border border-dashed border-stone-300 dark:border-stone-700 rounded-lg p-6 bg-stone-50 dark:bg-stone-900/40 space-y-3">
                <p class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                    Upcoming runs
                </p>
                <ul class="text-sm text-stone-600 dark:text-stone-300 space-y-2">
                    <li v-for="item in nextRuns" :key="item.command">
                        <span class="font-medium text-stone-800 dark:text-stone-100">{{ item.command }}</span>
                        <span class="text-xs text-stone-500 dark:text-stone-400 ml-2">Next: {{ item.next }}</span>
                    </li>
                    <li v-if="!nextRuns.length">Unable to calculate future runs yet.</li>
                </ul>
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
