<template>
    <AppLayout title="Research">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard
                title="Research"
                subtitle="Recent topics and notes."
            >
                <template #actions>
                    <GlassButton :icon-left="PlusIcon" @click="show = true">Start Research</GlassButton>
                </template>
            </GlassCard>

            <div v-if="(research ?? []).length" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <GlassSurface
                    v-for="(topic, i) in research"
                    :key="'research-'+i"
                    class="p-3"
                >
                    <ContextMenu>
                        <div class="flex flex-col">
                            <Link :href="'/-/projects/research/'+ topic.id" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm">
                                <div class="truncate text-base font-semibold text-stone-900 dark:text-stone-50">{{ topic.name }}</div>
                                <pre class="my-2 h-48 overflow-hidden border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] py-2 text-xs text-stone-600 dark:text-stone-300">{{ topic.notes }}</pre>
                            </Link>
                            <div class="mt-2 flex items-center justify-between border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] pt-2 text-xs text-stone-500 dark:text-stone-400">
                                <span>{{ date(topic.updated_at) }}</span>
                                <GlassIconButton variant="ghost" size="sm" label="Delete" @click="deleteFeature(topic)">
                                    <TrashIcon class="h-4 w-4 text-red-500" aria-hidden="true" />
                                </GlassIconButton>
                            </div>
                        </div>

                        <template #items>
                            <Link :href="'/-/projects/research/'+topic.id" class="flex items-center gap-2 px-4 py-2 text-stone-700 dark:text-stone-200" role="menuitem" tabindex="-1">
                                <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
                                Open
                            </Link>
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-stone-700 dark:text-stone-200" role="menuitem" tabindex="-1" @click="duplicateFeature">
                                <DocumentDuplicateIcon class="h-4 w-4" aria-hidden="true" />
                                Duplicate
                            </button>
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-stone-700 dark:text-stone-200" role="menuitem" tabindex="-1" @click="renameFeature">
                                <PencilIcon class="h-4 w-4" aria-hidden="true" />
                                Rename
                            </button>
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-stone-700 dark:text-stone-200" role="menuitem" tabindex="-1" @click="shareFeature">
                                <UserPlusIcon class="h-4 w-4" aria-hidden="true" />
                                Share
                            </button>
                            <hr class="border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]">
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left" role="menuitem" tabindex="-1" @click="deleteFeature">
                                <TrashIcon class="h-4 w-4 text-red-500" aria-hidden="true" />
                                Delete
                            </button>
                        </template>
                    </ContextMenu>
                </GlassSurface>
            </div>

            <GlassEmptyState
                v-else
                icon="MagnifyingGlassIcon"
                title="No research started yet"
                description="Start a topic to gather notes, sources, and questions."
            />
        </div>

        <GlassModal :open="show" title="Start Research" size="md" @close="show = false">
            <div class="space-y-3">
                <div v-for="(field, i) in form" :key="i+'.form-value'">
                    <SporkDynamicInput
                        :autofocus="i === 1"
                        v-model="form[i]"
                        :type="form[i].type"
                        :disabled-input="false"
                        :editable-label="false"
                        :errors="errors?.[field.name]"
                    />
                </div>
            </div>
            <template #footer>
                <GlassButton variant="secondary" size="sm" @click="show = false">Cancel</GlassButton>
                <GlassButton size="sm" @click="onSave">Apply</GlassButton>
            </template>
        </GlassModal>
    </AppLayout>
</template>

<script setup>
import {
    TrashIcon,
    ArrowTopRightOnSquareIcon,
    DocumentDuplicateIcon,
    PencilIcon,
    UserPlusIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';
import AppLayout from "@/Layouts/AppLayout.vue";
import ContextMenu from "@/Components/ContextMenus/ContextMenu.vue";
import { Link, router } from '@inertiajs/vue3';
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassIconButton from "@/Components/Glass/GlassIconButton.vue";
import GlassModal from "@/Components/Glass/GlassModal.vue";
import GlassEmptyState from "@/Components/Glass/GlassEmptyState.vue";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import axios from 'axios';
import dayjs from 'dayjs';
import { ref } from "vue";

defineProps({
    research: Array,
});

const shareFeature = () => { /* TODO */ };
const deleteFeature = () => { /* TODO */ };
const renameFeature = () => { /* TODO */ };
const duplicateFeature = () => { /* TODO */ };
const date = (d) => (d ? dayjs(d).format('MMM D, YYYY') : '');

const show = ref(false);
const errors = ref({});

const form = ref([
    { name: 'topic', value: '', type: 'text' },
    { name: 'notes', value: '', type: 'text' },
    { name: 'sources', value: [], type: 'array' },
]);

const onSave = async () => {
    const body = form.value.reduce((a, b) => ({ ...a, [b.name]: b.value }), {});
    await axios.post('/api/crud/research', body);
    show.value = false;
    router.reload({ only: ['research'] });
};
</script>
