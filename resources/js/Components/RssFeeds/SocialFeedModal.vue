<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import TagMultiSelect from '@/Components/Spork/Molecules/Tags/TagMultiSelect.vue';
import ConditionsEditor from '@/Components/Spork/Molecules/ConditionsEditor.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    availableTags: { type: Array, default: () => [] },
    parameterGroups: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'created']);

const createdFeed = ref(null);
const saving = ref(false);
const errors = ref({});
const tagIds = ref([]);

const form = ref({
    name: '',
    description: '',
    must_all_conditions_pass: false,
});

watch(() => props.show, (isOpen) => {
    if (!isOpen) return;

    createdFeed.value = null;
    saving.value = false;
    errors.value = {};
    tagIds.value = [];
    form.value = { name: '', description: '', must_all_conditions_pass: false };
});

const canShowConditions = computed(() => Boolean(createdFeed.value?.id));
const socialFeedType = 'App\\\\Models\\\\Article\\\\SocialFeed';

const create = async () => {
    saving.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/api/crud/social_feeds', {
            name: form.value.name,
            description: form.value.description,
            must_all_conditions_pass: form.value.must_all_conditions_pass,
            is_public: false,
        });

        createdFeed.value = response.data;

        if ((tagIds.value ?? []).length > 0) {
            await axios.post(`/api/crud/social_feeds/${createdFeed.value.id}/tags`, {
                tags: tagIds.value,
            });
        }

        emit('created', createdFeed.value);
    } catch (e) {
        errors.value = e?.response?.data?.errors ?? {
            message: e?.response?.data?.message ?? 'Failed to create social feed.',
        };
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <GlassModal :open="show" title="Create Social Feed" size="lg" @close="emit('close')">
        <div class="space-y-4">
            <p class="text-sm text-stone-600 dark:text-stone-300">
                Social feeds are powered by tags and conditions. Start by naming your feed, choosing tags, then optionally add conditions.
            </p>

            <div v-if="createdFeed" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Created</div>
                <div class="text-sm text-emerald-700 dark:text-emerald-300">{{ createdFeed.name }}</div>
                <div class="mt-2">
                    <Link
                        :href="`/-/feeds/rss-feeds/${createdFeed.id}`"
                        class="text-sm font-semibold text-indigo-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm dark:text-indigo-300"
                    >
                        View feed
                    </Link>
                </div>
            </div>

            <GlassField label="Name" :error="errors?.name?.[0]">
                <GlassInput
                    v-model="form.name"
                    placeholder="e.g. AI, Security, Laravel"
                    :disabled="saving || !!createdFeed"
                    :invalid="!!errors?.name"
                />
            </GlassField>

            <GlassField label="Description" :error="errors?.description?.[0]">
                <GlassInput
                    v-model="form.description"
                    placeholder="Optional"
                    :disabled="saving || !!createdFeed"
                    :invalid="!!errors?.description"
                />
            </GlassField>

            <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                <input
                    v-model="form.must_all_conditions_pass"
                    type="checkbox"
                    class="h-4 w-4 rounded border-stone-300 text-indigo-600 focus:ring-indigo-500 dark:border-stone-600"
                    :disabled="saving || !!createdFeed"
                >
                Require all conditions to pass (AND). Otherwise any condition passing will match (OR).
            </label>

            <div>
                <div class="mb-1 text-xs font-medium text-stone-600 dark:text-stone-300">Tags</div>
                <TagMultiSelect v-model="tagIds" :tags="availableTags" />
                <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    Articles that share any of these tags will be included.
                </div>
            </div>

            <GlassSurface v-if="canShowConditions">
                <ConditionsEditor
                    :conditions="createdFeed?.conditions ?? []"
                    :id="createdFeed.id"
                    :type="socialFeedType"
                    :parameter-groups="parameterGroups"
                />
            </GlassSurface>
        </div>

        <template #footer>
            <GlassButton variant="secondary" size="sm" @click="emit('close')">Close</GlassButton>
            <GlassButton
                v-if="!createdFeed"
                size="sm"
                :disabled="saving || !form.name"
                @click="create"
            >
                Create
            </GlassButton>
        </template>
    </GlassModal>
</template>
