<script setup>
import { computed, ref, watch } from "vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassModal from "@/Components/Glass/GlassModal.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";

const props = defineProps({
    data: Array,
    dataForAttachment: Array,
    keyAccessor: { type: Function, default: (item) => item.name },
    headerText: String,
    openModalText: String,
    modalTitle: String,
    noDataText: String,
    xl: Boolean,
    lg: Boolean,
    md: Boolean,
    sm: Boolean,
    xs: Boolean,
});

const attachOpen = ref(false);
const allSelected = ref(false);
const resources = ref([]);

const emits = defineEmits(['detach', 'attach', 'open', 'close']);

watch(() => attachOpen.value, (isOpen) => {
    emits(isOpen ? 'open' : 'close');
});

const width = computed(() => {
    switch (true) {
        case props.xl:
            return 'grid-cols-1 md:grid-cols-3 xl:grid-cols-5';
        case props.lg:
            return 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4';
        case props.md:
            return 'grid-cols-1 md:grid-cols-3';
        case props.sm:
            return 'grid-cols-1 md:grid-cols-2';
        default:
            return 'grid-cols-1';
    }
});
</script>

<template>
    <div>
        <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-50">{{ headerText }}</h3>
        <dl class="mt-4 grid gap-4 text-stone-900 dark:text-stone-50" :class="width">
            <GlassSurface
                v-for="item in data"
                :key="keyAccessor(item)"
                class="overflow-hidden"
            >
                <slot name="preview" :item="item" />
            </GlassSurface>
            <div v-if="data?.length === 0" class="col-span-full rounded-md bg-stone-100 p-3 text-sm italic text-stone-600 dark:bg-stone-800/60 dark:text-stone-300">
                {{ noDataText }}
            </div>
        </dl>

        <div class="mt-3 flex justify-between text-sm font-semibold text-stone-700 dark:text-stone-200">
            <button
                type="button"
                class="rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                @click="attachOpen = !attachOpen"
            >
                {{ openModalText }}
            </button>
            <slot name="buttons" />
        </div>
    </div>

    <GlassModal :open="attachOpen" :title="modalTitle" size="md" @close="attachOpen = false">
        <GlassSurface class="max-h-72 overflow-y-auto p-3">
            <label class="flex cursor-pointer items-center gap-2">
                <input
                    v-model="allSelected"
                    type="checkbox"
                    class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600"
                >
                <span class="text-sm text-stone-700 dark:text-stone-200">Select All</span>
            </label>

            <div v-for="item in dataForAttachment" :key="item.id" class="mt-1">
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        v-model="resources"
                        type="checkbox"
                        :value="item.id"
                        class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600"
                    >
                    <span class="text-sm text-stone-700 dark:text-stone-200">
                        {{ item.name ?? item.title ?? item.topic }}
                        <span
                            v-if="item?.tags?.map((i) => i.name?.en)?.join(', ') ?? item?.credential?.name ?? item?.credential_id ?? item.slug ?? item?.type"
                            class="text-xs text-stone-500 dark:text-stone-400"
                        >
                            ({{ item?.tags?.map((i) => i.name?.en)?.join(', ') ?? item?.credential?.name ?? item?.credential_id ?? item.slug ?? item?.type }})
                        </span>
                    </span>
                </label>
            </div>

            <div v-if="dataForAttachment?.length === 0" class="pt-2 text-sm italic text-stone-500 dark:text-stone-400">
                {{ noDataText }}
            </div>
        </GlassSurface>

        <template #footer>
            <GlassButton variant="secondary" size="sm" @click="attachOpen = !attachOpen">Close</GlassButton>
            <GlassButton size="sm" @click="emits('attach', resources); attachOpen = !attachOpen">Attach</GlassButton>
        </template>
    </GlassModal>
</template>
