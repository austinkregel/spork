<template>
  <AppLayout :title="`Members · ${project.name}`">
    <template #header>
      <div class="flex items-center gap-3">
        <RectangleStackIcon class="h-6 w-6 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
        <h1 class="text-lg font-semibold text-stone-900 dark:text-stone-50">
          {{ project.name }} · members
        </h1>
      </div>
    </template>

    <div class="mx-auto max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
      <p
        v-if="status"
        class="rounded-md bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200"
        role="status"
      >
        {{ status }}
      </p>

      <GlassCard title="Members" subtitle="People with access to this project workspace.">
        <div class="space-y-3">
          <div v-if="project.owner" class="flex items-center justify-between gap-3 rounded-md bg-stone-100/60 px-3 py-2 dark:bg-stone-800/40">
            <div class="flex min-w-0 items-center gap-3">
              <img v-if="project.owner.profile_photo_url" :src="project.owner.profile_photo_url" alt="" class="h-8 w-8 rounded-full" />
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-stone-900 dark:text-stone-50">{{ project.owner.name }}</p>
                <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ project.owner.email }}</p>
              </div>
            </div>
            <GlassPill tone="indigo">Owner</GlassPill>
          </div>

          <GlassEmptyState
            v-if="!members.length"
            title="No collaborators yet"
            description="Invite teammates by email to share this project."
            icon="UsersIcon"
          />

          <ul v-else class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
            <li
              v-for="member in members"
              :key="member.id"
              class="flex items-center justify-between gap-3 py-2"
            >
              <div class="flex min-w-0 items-center gap-3">
                <img v-if="member.user?.profile_photo_url" :src="member.user.profile_photo_url" alt="" class="h-8 w-8 rounded-full" />
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-stone-900 dark:text-stone-50">{{ member.user?.name ?? '—' }}</p>
                  <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ member.user?.email ?? '' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <GlassPill :tone="member.accepted_at ? 'success' : 'warning'" size="sm" dot>
                  {{ member.accepted_at ? member.role_label : 'Invited' }}
                </GlassPill>
                <GlassIconButton
                  v-if="can.invite && member.user"
                  variant="destructive"
                  size="sm"
                  label="Remove member"
                  @click="remove(member)"
                >
                  <TrashIcon class="h-4 w-4" aria-hidden="true" />
                </GlassIconButton>
              </div>
            </li>
          </ul>
        </div>
      </GlassCard>

      <GlassCard v-if="can.invite" title="Invite a member" subtitle="Send an invitation to a Spork user by email.">
        <form class="space-y-4" @submit.prevent="invite">
          <GlassField
            v-slot="{ id, describedby, invalid }"
            label="Email"
            :error="form.errors.email"
            required
          >
            <GlassInput
              :id="id"
              v-model="form.email"
              type="email"
              autocomplete="off"
              required
              :invalid="invalid"
              :describedby="describedby"
            />
          </GlassField>

          <GlassField label="Role" :error="form.errors.role">
            <select
              v-model="form.role"
              class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100"
            >
              <option v-for="role in available_roles" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
          </GlassField>

          <div class="flex justify-end">
            <GlassButton type="submit" :disabled="form.processing">Send invite</GlassButton>
          </div>
        </form>
      </GlassCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { RectangleStackIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassIconButton from '@/Components/Glass/GlassIconButton.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';

const props = defineProps({
  project: { type: Object, required: true },
  members: { type: Array, default: () => [] },
  available_roles: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({ invite: false }) },
});

const page = usePage();
const status = computed(() => page.props.flash?.status ?? null);

const form = useForm({
  email: '',
  role: props.available_roles.find((r) => r.value === 'viewer')?.value ?? 'viewer',
});

function invite() {
  form.post(route('projects.members.invite', props.project.id), {
    preserveScroll: true,
    onSuccess: () => form.reset('email'),
  });
}

function remove(member) {
  if (!member.user) return;
  if (!window.confirm(`Remove ${member.user.name} from this project?`)) return;
  router.delete(route('projects.members.destroy', [props.project.id, member.user.id]), {
    preserveScroll: true,
  });
}
</script>
