<template>
    <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-800 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-300">
                    Quick actions
                </p>
                <p v-if="!compact" class="text-xs text-stone-500 dark:text-stone-400">
                    Common workflows, one click away.
                </p>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <button
                v-for="action in resolvedActions"
                :key="action.id"
                type="button"
                class="flex flex-col gap-2 rounded-lg border border-stone-200 dark:border-stone-700 p-3 text-left transition hover:border-indigo-400 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900"
                :class="action.variant === 'primary' ? 'bg-indigo-50 dark:bg-indigo-600/10' : 'bg-stone-50 dark:bg-stone-700/30'"
                @click="() => emit('select', action.id)"
            >
                <div class="flex items-center gap-2">
                    <DynamicIcon v-if="action.icon" :icon-name="action.icon" class="w-5 h-5 text-indigo-600 dark:text-indigo-300" />
                    <p class="text-sm font-semibold text-stone-700 dark:text-stone-100">
                        {{ action.label }}
                    </p>
                </div>
                <p v-if="!compact" class="text-xs text-stone-500 dark:text-stone-300">
                    {{ action.description ?? '' }}
                </p>
                <div v-if="action.badge" class="text-xs font-semibold text-indigo-600 dark:text-indigo-300">
                    {{ action.badge }}
                </div>
            </button>
        </div>
    </div>
</template>

<script setup>
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { computed } from 'vue';

const emit = defineEmits(['select']);

const props = defineProps({
    actions: {
        type: Array,
        default: () => [],
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const defaultActions = [
    {
        id: 'connect-host',
        label: 'Connect existing host',
        icon: 'ServerIcon',
        description: 'Enroll a bare-metal box or existing VM using a one-line installer.',
        variant: 'primary',
        badge: 'Agent-ready',
    },
    {
        id: 'link-server',
        label: 'Link server (SSH)',
        icon: 'ServerIcon',
        description: 'Exchange keys and begin pulling server metadata automatically.',
    },
    {
        id: 'import-domains',
        label: 'Import domains',
        icon: 'GlobeAltIcon',
        description: 'Sync Namecheap or Cloudflare domains into the single inventory.',
    },
    {
        id: 'bulk-edit',
        label: 'Bulk edit',
        icon: 'AdjustmentsHorizontalIcon',
        description: 'Change contacts, DNS, or Cloudflare settings across selections.',
        badge: 'Wizard',
    },
];

const resolvedActions = computed(() => (props.actions.length ? props.actions : defaultActions));
</script>







