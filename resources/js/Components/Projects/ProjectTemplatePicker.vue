<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button
            v-for="template in templates"
            :key="template.key"
            type="button"
            class="text-left rounded-lg border p-4 shadow-sm hover:shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            :class="template.key === modelValue
                ? 'border-indigo-500 bg-white dark:bg-stone-900'
                : 'border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-900'"
            @click="$emit('update:modelValue', template.key)"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="font-semibold text-stone-800 dark:text-stone-100">
                        {{ template.label }}
                    </div>
                    <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                        {{ template.description }}
                    </div>
                </div>

                <div
                    class="shrink-0 text-xs px-2 py-1 rounded-md"
                    :class="template.key === modelValue
                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200'
                        : 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200'"
                >
                    {{ template.key }}
                </div>
            </div>
        </button>
    </div>
</template>

<script setup>
defineProps({
    templates: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: String,
        required: true,
    },
});

defineEmits(['update:modelValue']);
</script>


