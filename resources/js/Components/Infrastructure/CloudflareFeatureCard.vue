<template>
    <div class="border border-stone-200 dark:border-stone-800 rounded-lg p-4 bg-stone-50 dark:bg-stone-800/40 space-y-2">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-stone-900 dark:text-white">
                    {{ feature.label }}
                </p>
                <p class="text-xs text-stone-500 dark:text-stone-400">
                    {{ feature.description }}
                </p>
            </div>
            <button
                type="button"
                class="px-3 py-1 text-xs font-semibold rounded-full transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900"
                :class="feature.enabled ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-stone-200 text-stone-600 dark:bg-stone-700 dark:text-stone-200'"
                @click="$emit('toggle', !feature.enabled)"
            >
                {{ feature.enabled ? 'Enabled' : 'Disabled' }}
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    feature: {
        type: Object,
        required: true,
    },
});

defineEmits(['toggle']);
</script>



















