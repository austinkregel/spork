<template>
    <draggable
        class="flex flex-wrap px-4 pt-4"
        style="min-width:300px;"
        tag="ul"
        :list="items"
        :group="{ name: 'g1' }"
        item-key="name"
    >
        <template #item="{ element }">
            <li class="flex w-full flex-col p-2">
                <div class="flex w-[400px] justify-between rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass p-2 font-semibold">
                    <div>{{ element.name }}</div>
                    <button
                        type="button"
                        class="text-sm text-stone-700 dark:text-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm"
                        :aria-expanded="open"
                        @click="open = !open"
                    >
                        {{ open ? '> Close' : '< Open' }}
                    </button>
                </div>
                <div v-if="open" class="flex w-full flex-col gap-2 p-4">
                    <GlassField label="Navigation Label">
                        <GlassInput v-model="element.name" />
                    </GlassField>
                    <GlassField label="URL">
                        <GlassInput v-model="element.path" />
                    </GlassField>
                </div>
                <nested-draggable :items="element.items" />
            </li>
        </template>
    </draggable>
</template>

<script>
import draggable from "vuedraggable";
import GlassField from "@/Components/Glass/GlassField.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";

export default {
    name: "nested-draggable",
    components: { GlassField, GlassInput, draggable },
    props: {
        items: { required: true, type: Array },
    },
    data() {
        return { open: false };
    },
};
</script>
