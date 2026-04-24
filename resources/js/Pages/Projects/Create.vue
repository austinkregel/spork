<template>
  <AppLayout title="Create Project">
    <template #header>
      <div class="flex items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-200">
        <Link href="/-/projects/list" class="hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm">
          Projects
        </Link>
        <ChevronRightIcon class="h-4 w-4 text-stone-400" aria-hidden="true" />
        <span class="text-stone-900 dark:text-stone-50">Create</span>
      </div>
    </template>

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <GlassCard
        title="Create a Project"
        subtitle="Pick a starting template and jump into the project workspace."
      />

      <GlassCard v-if="hasAnyErrors" :tone="'strong'" class="border-red-300 dark:border-red-500/40">
        <div class="text-sm font-semibold text-red-700 dark:text-red-300">Fix the highlighted fields</div>
        <pre class="mt-2 overflow-auto text-xs text-red-700 dark:text-red-300">{{ errors }}</pre>
      </GlassCard>

      <GlassCard title="Details">
        <div class="grid grid-cols-1 gap-4">
          <GlassField v-slot="{ id, describedby, invalid }" label="Project name" required>
            <GlassInput
              :id="id"
              v-model="name"
              placeholder="e.g. Automation Ops"
              :invalid="invalid"
              :describedby="describedby"
              required
            />
          </GlassField>

          <GlassField label="Goal" hint="What outcome are you driving toward?">
            <textarea
              v-model="goal"
              rows="3"
              placeholder="What outcome are you driving toward?"
              class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100 dark:placeholder:text-stone-500"
            />
          </GlassField>
        </div>
      </GlassCard>

      <GlassCard title="Template" subtitle="Choose how this project starts.">
        <ProjectTemplatePicker v-model="templateKey" :templates="project_templates" />
      </GlassCard>

      <div class="flex items-center justify-end gap-2">
        <GlassButton variant="secondary" :disabled="saving" @click="router.visit('/-/projects/list')">
          Cancel
        </GlassButton>
        <GlassButton :disabled="saving || !name" @click="createProject">
          {{ saving ? 'Creating…' : 'Create project' }}
        </GlassButton>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/solid';

import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import ProjectTemplatePicker from '@/Components/Projects/ProjectTemplatePicker.vue';

const page = usePage();

const props = defineProps({
  description: Object,
  project_templates: Array,
});

const errors = ref(null);
const saving = ref(false);

const name = ref('');
const templateKey = ref(props.project_templates?.[0]?.key ?? 'custom');
const goal = ref('');

const hasAnyErrors = computed(() => !!errors.value && Object.keys(errors.value).length > 0);

function createProject() {
  saving.value = true;
  errors.value = null;

  router.post(
    '/-/projects',
    {
      name: name.value,
      user_id: page?.props?.auth?.user?.id,
      settings: {
        template: templateKey.value,
        goal: goal.value || null,
      },
    },
    {
      preserveScroll: true,
      onError: (error) => {
        errors.value = Object.keys(error).reduce(
          (acc, key) => ({ ...acc, [key]: [error[key]] }),
          {},
        );
      },
      onFinish: () => {
        saving.value = false;
      },
    },
  );
}
</script>
