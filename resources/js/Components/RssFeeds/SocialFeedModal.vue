<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import TagMultiSelect from '@/Components/Spork/Molecules/Tags/TagMultiSelect.vue';
import ConditionsEditor from '@/Components/Spork/Molecules/ConditionsEditor.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  availableTags: {
    type: Array,
    default: () => [],
  },
  parameterGroups: {
    type: Array,
    default: () => [],
  },
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

watch(
  () => props.show,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    // Reset state on open
    createdFeed.value = null;
    saving.value = false;
    errors.value = {};
    tagIds.value = [];
    form.value = { name: '', description: '', must_all_conditions_pass: false };
  }
);

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
    errors.value = e?.response?.data?.errors ?? { message: e?.response?.data?.message ?? 'Failed to create social feed.' };
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <DialogModal :show="show" max-width="2xl" @close="emit('close')">
    <template #title>
      Create Social Feed
    </template>

    <template #content>
      <div class="space-y-4">
        <div class="text-sm text-stone-600 dark:text-stone-300">
          Social feeds are powered by tags and conditions. Start by naming your feed, choosing tags, then optionally add conditions.
        </div>

        <div v-if="createdFeed" class="rounded-lg border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/10 p-3">
          <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Created</div>
          <div class="text-sm text-emerald-700 dark:text-emerald-300">
            {{ createdFeed.name }}
          </div>
          <div class="mt-2">
            <Link
              :href="`/-/rss-feeds/${createdFeed.id}`"
              class="text-sm font-semibold text-indigo-600 dark:text-indigo-300 hover:underline"
            >
              View feed
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="block text-xs font-medium text-stone-600 dark:text-stone-300">Name</label>
            <TextInput
              v-model="form.name"
              class="mt-1 block w-full"
              placeholder="e.g. AI, Security, Laravel"
              :disabled="saving || createdFeed"
            />
            <InputError class="mt-1" :message="errors?.name?.[0]" />
          </div>

          <div>
            <label class="block text-xs font-medium text-stone-600 dark:text-stone-300">Description</label>
            <TextInput
              v-model="form.description"
              class="mt-1 block w-full"
              placeholder="Optional"
              :disabled="saving || createdFeed"
            />
            <InputError class="mt-1" :message="errors?.description?.[0]" />
          </div>

          <div class="flex items-center gap-2">
            <input
              id="mustAll"
              v-model="form.must_all_conditions_pass"
              type="checkbox"
              class="h-4 w-4 rounded border-stone-300 dark:border-stone-700 text-indigo-600 focus:ring-indigo-500"
              :disabled="saving || createdFeed"
            />
            <label for="mustAll" class="text-sm text-stone-700 dark:text-stone-200">
              Require all conditions to pass (AND). Otherwise any condition passing will match (OR).
            </label>
          </div>
        </div>

        <div>
          <div class="text-xs font-medium text-stone-600 dark:text-stone-300 mb-1">Tags</div>
          <TagMultiSelect v-model="tagIds" :tags="availableTags" />
          <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">
            Articles that share any of these tags will be included.
          </div>
        </div>

        <div v-if="canShowConditions" class="rounded-lg border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900">
          <ConditionsEditor
            :conditions="createdFeed?.conditions ?? []"
            :id="createdFeed.id"
            :type="socialFeedType"
            :parameter-groups="parameterGroups"
          />
        </div>
      </div>
    </template>

    <template #footer>
      <SecondaryButton @click="emit('close')">
        Close
      </SecondaryButton>

      <PrimaryButton
        v-if="!createdFeed"
        class="ml-3"
        :class="{ 'opacity-25': saving }"
        :disabled="saving || !form.name"
        @click="create"
      >
        Create
      </PrimaryButton>
    </template>
  </DialogModal>
</template>

