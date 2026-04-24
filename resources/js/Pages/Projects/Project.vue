<template>
  <AppLayout :title="project?.name ?? 'Project'">
    <template #header>
      <div class="flex items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-200">
        <Link href="/-/projects/list" class="hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm">
          Projects
        </Link>
        <ChevronRightIcon class="h-4 w-4 text-stone-400" aria-hidden="true" />
        <span class="text-stone-900 dark:text-stone-50">{{ project?.name }}</span>
      </div>
    </template>

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <GlassCard v-if="goal" subtitle="Goal">
        <p class="text-sm text-stone-800 dark:text-stone-100">{{ goal }}</p>
      </GlassCard>

      <GlassCard title="Automations" subtitle="Run playbooks, monitor behavior, and iterate on steps.">
        <template #actions>
          <GlassButton variant="secondary" size="sm" @click="openAttach('Attach automations', automationType)">Attach</GlassButton>
          <GlassButton :href="route('automations.automations.create')" size="sm">Create</GlassButton>
        </template>

        <ul v-if="project.automations?.length" class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] -mx-1">
          <li v-for="a in project.automations" :key="a.id" class="flex flex-wrap items-center justify-between gap-3 px-1 py-3">
            <div class="min-w-0">
              <div class="truncate text-sm font-medium text-stone-900 dark:text-stone-100">{{ a.name }}</div>
              <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                <GlassPill :tone="a.enabled ? 'success' : 'neutral'" size="sm" dot>
                  {{ a.enabled ? 'Enabled' : 'Disabled' }}
                </GlassPill>
                <span>Cron: {{ a.cron_expression ?? '—' }}</span>
              </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <GlassButton :href="route('automations.automations.show', a.id)" variant="outline" size="sm">View</GlassButton>
              <GlassButton :href="route('automations.automations.edit', a.id)" variant="outline" size="sm">Edit steps</GlassButton>
              <Link
                :href="route('automations.run-now', a.id)"
                method="post"
                as="button"
                class="inline-flex items-center justify-center rounded-md bg-indigo-500 px-2.5 py-1 text-xs font-medium text-white shadow-sm transition-colors motion-reduce:transition-none hover:bg-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
              >
                Run now
              </Link>
              <GlassButton variant="destructive" size="sm" @click="detachResource(automationType, a.id)">Remove</GlassButton>
            </div>
          </li>
        </ul>
        <GlassEmptyState
          v-else
          icon="BoltIcon"
          title="No automations attached"
          description="Attach an existing playbook or create a new one."
        />
      </GlassCard>

      <GlassCard title="Tasks" subtitle="Keep a small queue of concrete next actions.">
        <template #actions>
          <GlassButton variant="secondary" size="sm" @click="openAttach('Attach tasks', taskType)">Attach</GlassButton>
          <GlassButton size="sm" @click="createTaskOpen = true">New task</GlassButton>
        </template>

        <ul v-if="project.tasks?.length" class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] -mx-1">
          <li v-for="t in project.tasks" :key="t.id" class="flex items-center justify-between gap-3 px-1 py-3">
            <div class="min-w-0">
              <div class="truncate text-sm font-medium text-stone-900 dark:text-stone-100">{{ t.name }}</div>
              <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                <GlassPill size="sm" tone="indigo">{{ t.status ?? '—' }}</GlassPill>
                <span>{{ t.type ?? '—' }}</span>
              </div>
            </div>
          </li>
        </ul>
        <GlassEmptyState
          v-else
          icon="ClipboardDocumentListIcon"
          title="No tasks yet"
          description="Add a quick task to get started."
        />
      </GlassCard>

      <GlassCard title="Research" subtitle="Capture sources and questions, then turn them into tasks or automation.">
        <template #actions>
          <GlassButton variant="secondary" size="sm" @click="openAttach('Attach research', researchType)">Attach research</GlassButton>
          <GlassButton variant="secondary" size="sm" @click="openAttach('Attach pages', pageType)">Attach pages</GlassButton>
          <GlassButton variant="secondary" size="sm" @click="openAttach('Attach RSS feeds', feedType)">Attach feeds</GlassButton>
          <GlassButton size="sm" @click="createResearchOpen = true">New research</GlassButton>
        </template>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <GlassSurface class="p-4">
            <h4 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Research</h4>
            <ul v-if="project.research?.length" class="mt-2 space-y-1.5 text-sm text-stone-800 dark:text-stone-100">
              <li v-for="r in project.research" :key="r.id">{{ r.topic }}</li>
            </ul>
            <p v-else class="mt-2 text-sm text-stone-500 dark:text-stone-400">None yet.</p>
          </GlassSurface>
          <GlassSurface class="p-4">
            <h4 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Pages</h4>
            <ul v-if="project.pages?.length" class="mt-2 space-y-1.5 text-sm text-stone-800 dark:text-stone-100">
              <li v-for="p in project.pages" :key="p.id">{{ p.title }}</li>
            </ul>
            <p v-else class="mt-2 text-sm text-stone-500 dark:text-stone-400">None yet.</p>
          </GlassSurface>
        </div>
      </GlassCard>
    </div>

    <GlassModal :open="attachOpen" :title="attachTitle" size="md" @close="attachOpen = false">
      <div class="flex flex-col gap-3">
        <GlassField v-slot="{ id, describedby, invalid }" label="Search">
          <GlassInput :id="id" v-model="attachQuery" placeholder="Search…" :invalid="invalid" :describedby="describedby" />
        </GlassField>
        <p v-if="attachLoading" class="text-sm text-stone-500 dark:text-stone-400">Searching…</p>
        <ul v-else-if="attachResults.length" class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)]">
          <li v-for="item in attachResults" :key="item.id" class="flex items-center justify-between gap-3 px-3 py-2">
            <div class="min-w-0">
              <div class="truncate text-sm text-stone-900 dark:text-stone-100">{{ item.label }}</div>
              <div class="text-xs text-stone-500 dark:text-stone-400">#{{ item.id }}</div>
            </div>
            <GlassButton size="sm" @click="attachResource(item.id)">Attach</GlassButton>
          </li>
        </ul>
        <GlassEmptyState v-else title="No results" description="Try a different search term." />
      </div>
      <template #footer>
        <GlassButton variant="secondary" size="sm" @click="attachOpen = false">Close</GlassButton>
      </template>
    </GlassModal>

    <GlassModal :open="createTaskOpen" title="New task" size="md" @close="createTaskOpen = false">
      <div class="flex flex-col gap-4">
        <GlassField v-slot="{ id, describedby, invalid }" label="Name" required>
          <GlassInput :id="id" v-model="taskForm.name" :invalid="invalid" :describedby="describedby" required />
        </GlassField>
        <GlassField v-slot="{ id, describedby, invalid }" label="Type">
          <GlassInput :id="id" v-model="taskForm.type" :invalid="invalid" :describedby="describedby" />
        </GlassField>
        <GlassField v-slot="{ id, describedby, invalid }" label="Status">
          <GlassInput :id="id" v-model="taskForm.status" :invalid="invalid" :describedby="describedby" />
        </GlassField>
      </div>
      <template #footer>
        <GlassButton variant="secondary" size="sm" @click="createTaskOpen = false">Cancel</GlassButton>
        <GlassButton size="sm" @click="saveTask">Save</GlassButton>
      </template>
    </GlassModal>

    <GlassModal :open="createResearchOpen" title="New research" size="md" @close="createResearchOpen = false">
      <div class="flex flex-col gap-4">
        <GlassField v-slot="{ id, describedby, invalid }" label="Topic" required>
          <GlassInput :id="id" v-model="researchForm.topic" :invalid="invalid" :describedby="describedby" required />
        </GlassField>
        <GlassField label="Notes">
          <textarea
            v-model="researchForm.notes"
            rows="4"
            class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100 dark:placeholder:text-stone-500"
          />
        </GlassField>
      </div>
      <template #footer>
        <GlassButton variant="secondary" size="sm" @click="createResearchOpen = false">Cancel</GlassButton>
        <GlassButton size="sm" @click="saveResearch">Save</GlassButton>
      </template>
    </GlassModal>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/solid';

import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const page = usePage();

const project = computed(() => page.props.project);
const goal = computed(() => project.value?.settings?.goal ?? null);

const automationType = 'App\\Models\\Automation';
const taskType = 'App\\Models\\Task';
const researchType = 'App\\Models\\Research';
const pageType = 'App\\Models\\Page';
const feedType = 'App\\Models\\ExternalRssFeed';

const attachOpen = ref(false);
const attachTitle = ref('Attach');
const attachType = ref(null);
const attachQuery = ref('');
const attachResults = ref([]);
const attachLoading = ref(false);

const createTaskOpen = ref(false);
const taskForm = ref({ name: '', type: 'today', status: 'To Do' });

const createResearchOpen = ref(false);
const researchForm = ref({ topic: '', notes: '' });

function openAttach(title, type) {
  attachTitle.value = title;
  attachType.value = type;
  attachQuery.value = '';
  attachOpen.value = true;
}

let debounce = null;
watch(
  [attachOpen, attachQuery, attachType],
  async () => {
    attachResults.value = [];
    if (!attachOpen.value || !attachType.value) {
      return;
    }

    if (debounce) clearTimeout(debounce);

    debounce = setTimeout(async () => {
      attachLoading.value = true;
      try {
        const { data } = await axios.get('/api/suggest/models', {
          params: {
            type: attachType.value,
            q: attachQuery.value || undefined,
            limit: 20,
          },
        });
        attachResults.value = data?.data ?? [];
      } finally {
        attachLoading.value = false;
      }
    }, 200);
  },
  { immediate: true },
);

async function attachResource(id) {
  await axios.post(route('projects.attach', [project.value.id]), {
    resource_type: attachType.value,
    resource_id: id,
  });
  router.reload({ only: ['project'] });
}

async function detachResource(type, id) {
  await axios.post(route('projects.detach', [project.value.id]), {
    resource_type: type,
    resource_id: id,
  });
  router.reload({ only: ['project'] });
}

async function saveTask() {
  await axios.post(`/api/projects/${project.value.id}/tasks`, { ...taskForm.value });
  createTaskOpen.value = false;
  taskForm.value = { name: '', type: 'today', status: 'To Do' };
  router.reload({ only: ['project'] });
}

async function saveResearch() {
  await axios.post(`/api/projects/${project.value.id}/research`, { ...researchForm.value });
  createResearchOpen.value = false;
  researchForm.value = { topic: '', notes: '' };
  router.reload({ only: ['project'] });
}
</script>
