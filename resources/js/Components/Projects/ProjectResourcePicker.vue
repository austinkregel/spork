<template>
    <div class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-4">
        <div class="flex flex-col gap-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="w-full sm:w-72">
                    <div class="text-xs text-stone-600 dark:text-stone-300 px-1">Resource type</div>
                    <SporkSelect v-model="resourceType" class="mt-1">
                        <template #options>
                            <option v-for="r in allowedTypes" :key="r.type" :value="r.type">
                                {{ groupLabel(r.group) }} — {{ r.label }}
                            </option>
                        </template>
                    </SporkSelect>
                </div>

                <div class="flex-1">
                    <div class="text-xs text-stone-600 dark:text-stone-300 px-1">Search</div>
                    <SporkInput v-model="query" class="mt-1" type="text" />
                </div>
            </div>

            <div v-if="loading" class="text-sm text-stone-500 dark:text-stone-400">
                Searching…
            </div>

            <div v-else class="flex flex-col gap-2">
                <div
                    v-for="result in results"
                    :key="result.id"
                    class="flex items-center justify-between gap-3 rounded-md border border-stone-200 dark:border-stone-800 px-3 py-2"
                >
                    <div class="min-w-0">
                        <div class="text-sm text-stone-800 dark:text-stone-100 truncate">
                            {{ result.label }}
                        </div>
                        <div class="text-xs text-stone-500 dark:text-stone-400">
                            {{ simpleTypeLabel(resourceType) }} #{{ result.id }}
                        </div>
                    </div>

                    <SporkButton
                        xsmall
                        primary
                        :disabled="isSelected(resourceType, result.id)"
                        @click="add(result)"
                    >
                        {{ isSelected(resourceType, result.id) ? 'Added' : 'Add' }}
                    </SporkButton>
                </div>

                <div v-if="results.length === 0" class="text-sm text-stone-500 dark:text-stone-400">
                    No matches.
                </div>
            </div>

            <div v-if="selected.length > 0" class="mt-2">
                <div class="text-xs text-stone-600 dark:text-stone-300 uppercase tracking-wide">
                    Selected
                </div>
                <div class="mt-2 flex flex-col gap-2">
                    <div
                        v-for="item in selected"
                        :key="item.resource_type + ':' + item.resource_id"
                        class="flex items-center justify-between gap-3 rounded-md bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 px-3 py-2"
                    >
                        <div class="min-w-0">
                            <div class="text-sm text-stone-800 dark:text-stone-100 truncate">
                                {{ item.label ?? item.resource_id }}
                            </div>
                            <div class="text-xs text-stone-500 dark:text-stone-400">
                                {{ simpleTypeLabel(item.resource_type) }} #{{ item.resource_id }}
                            </div>
                        </div>

                        <SporkButton xsmall secondary @click="$emit('remove', item)">
                            Remove
                        </SporkButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import SporkInput from '@/Components/Spork/SporkInput.vue';
import SporkSelect from '@/Components/Spork/SporkSelect.vue';
import SporkButton from '@/Components/Spork/SporkButton.vue';

const props = defineProps({
    registry: {
        type: Object,
        required: true,
    },
    allowedTypes: {
        type: Array,
        default: () => [],
    },
    allowedGroups: {
        type: Array,
        default: () => [],
    },
    selected: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['add', 'remove']);

const resourceType = ref(null);
const query = ref('');
const loading = ref(false);
const results = ref([]);

const allowedTypes = computed(() => {
    const resources = props.registry?.resources ?? [];
    const types = props.allowedTypes ?? [];
    const groups = props.allowedGroups ?? [];

    if (types.length) {
        return resources.filter((r) => types.includes(r.type));
    }

    if (!groups.length) {
        return resources;
    }

    return resources.filter((r) => groups.includes(r.group));
});

watch(
    allowedTypes,
    (types) => {
        if (!types.length) {
            resourceType.value = null;
            return;
        }

        if (!resourceType.value || !types.some((t) => t.type === resourceType.value)) {
            resourceType.value = types[0].type;
        }
    },
    { immediate: true },
);

let debounceTimer = null;
watch([resourceType, query], async () => {
    results.value = [];
    if (!resourceType.value) {
        return;
    }

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(async () => {
        loading.value = true;
        try {
            const { data } = await axios.get('/api/suggest/models', {
                params: {
                    type: resourceType.value,
                    q: query.value || undefined,
                    limit: 20,
                },
            });

            results.value = data?.data ?? [];
        } finally {
            loading.value = false;
        }
    }, 200);
}, { immediate: true });

function isSelected(type, id) {
    return props.selected.some((i) => i.resource_type === type && i.resource_id === id);
}

function add(result) {
    if (!resourceType.value) {
        return;
    }

    if (isSelected(resourceType.value, result.id)) {
        return;
    }

    emit('add', {
        resource_type: resourceType.value,
        resource_id: result.id,
        label: result.label,
    });
}

function groupLabel(groupKey) {
    const groups = props.registry?.groups ?? {};
    return groups[groupKey] ?? groupKey;
}

function simpleTypeLabel(type) {
    const resources = props.registry?.resources ?? [];
    const meta = resources.find((r) => r.type === type);
    return meta?.label ?? type;
}
</script>


