<script setup>
import { router, Link } from '@inertiajs/vue3';
import Shell from './Shell.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import { CheckCircleIcon } from '@heroicons/vue/24/solid';

defineProps({
  step: { type: String, required: true },
  steps: { type: Array, default: () => [] },
  state: { type: Object, default: () => ({}) },
  project: { type: Object, default: null },
});

function startAnother() {
  router.post('/-/projects/wizard/reset', {});
}
</script>

<template>
  <Shell
    title="You're all set"
    subtitle="Your project is ready. Jump in to attach resources, invite collaborators, and start working."
    :step="step"
    :steps="steps"
  >
    <div class="flex flex-col items-center gap-3 py-4 text-center">
      <CheckCircleIcon class="h-12 w-12 text-emerald-500" aria-hidden="true" />
      <GlassPill tone="success" size="sm">Created</GlassPill>
      <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-50">
        {{ project?.name ?? 'Project ready' }}
      </h2>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
      <GlassButton
        v-if="project?.id"
        :href="`/-/projects/${project.id}`"
      >
        Open project
      </GlassButton>
      <GlassButton variant="secondary" :href="`/-/projects/list`">
        Back to projects
      </GlassButton>
      <GlassButton variant="ghost" @click="startAnother">
        Start another
      </GlassButton>
    </div>
  </Shell>
</template>
