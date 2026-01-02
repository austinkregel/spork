<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center gap-3">
            <p class="text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-300">
                Providers
            </p>
            <span class="text-xs text-stone-400 dark:text-stone-500">
                Filter inventory across registrars, DNS, and compute vendors
            </span>

            <button
                v-if="hasActiveFilters"
                type="button"
                class="ml-auto text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                @click="resetFilters"
            >
                Reset
            </button>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                v-for="provider in providers"
                :key="provider.slug ?? provider.name"
                type="button"
                class="px-3 py-2 rounded-full border text-sm flex items-center gap-2 transition focus:outline-none focus:ring-2 focus:ring-stone-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900"
                :class="[
                    isSelected(provider.slug)
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-stone-100 dark:bg-stone-800/50 hover:bg-stone-50 dark:hover:bg-stone-700'
                ]"
                @click="toggleProvider(provider.slug)"
            >
                <DynamicIcon v-if="provider.icon" :icon-name="provider.icon" class="w-4 h-4" />
                <span>{{ provider.name }}</span>
                <span v-if="provider.count !== undefined" class="text-xs opacity-80">{{ provider.count }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { computed } from 'vue';
import { useInfrastructureStore } from '@/composables/useInfrastructureStore';

const props = defineProps({
    providers: {
        type: Array,
        default: () => [],
    },
});

const store = useInfrastructureStore();

const toggleProvider = (slug) => {
    store.toggleProvider(slug);
};

const isSelected = (slug) => store.isProviderSelected(slug);

const hasActiveFilters = computed(() => store.filters.selectedProviders.length > 0);

const resetFilters = () => store.resetProviderFilters();
</script>



















