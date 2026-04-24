<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Shell from './Shell.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import { TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  step: { type: String, required: true },
  steps: { type: Array, default: () => [] },
  state: { type: Object, default: () => ({}) },
});

const members = ref([]);
const errors = ref({});
const saving = ref(false);

const roleOptions = [
  { value: 'editor', label: 'Editor' },
  { value: 'viewer', label: 'Viewer' },
];

function addMember() {
  members.value.push({ email: '', role: 'editor' });
}

function removeMember(index) {
  members.value.splice(index, 1);
}

function submit() {
  saving.value = true;
  errors.value = {};
  router.post(
    '/-/projects/wizard/members',
    { members: members.value.filter((m) => m.email) },
    {
      preserveScroll: true,
      onError: (err) => (errors.value = err),
      onFinish: () => (saving.value = false),
    },
  );
}

function back() {
  router.visit('/-/projects/wizard/identity');
}
</script>

<template>
  <Shell
    title="Invite collaborators"
    subtitle="Add teammates by email. They'll get an invitation to join the project. You can also skip and add members later."
    :step="step"
    :steps="steps"
  >
    <div class="space-y-3">
      <div v-if="!members.length" class="text-sm text-stone-500 dark:text-stone-400">
        <GlassPill tone="info" size="sm">Optional</GlassPill>
        <span class="ml-2">No members added yet.</span>
      </div>

      <div
        v-for="(member, idx) in members"
        :key="idx"
        class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr),12rem,auto] sm:items-end"
      >
        <GlassField
          v-slot="{ id, describedby, invalid }"
          :label="`Email`"
          :error="errors[`members.${idx}.email`]"
        >
          <GlassInput
            :id="id"
            v-model="member.email"
            type="email"
            placeholder="teammate@example.com"
            :invalid="invalid"
            :describedby="describedby"
          />
        </GlassField>

        <GlassField label="Role" :error="errors[`members.${idx}.role`]">
          <GlassSelect v-model="member.role">
            <option v-for="option in roleOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </GlassSelect>
        </GlassField>

        <GlassButton
          variant="ghost"
          size="sm"
          :icon-left="TrashIcon"
          aria-label="Remove member"
          @click="removeMember(idx)"
        >
          Remove
        </GlassButton>
      </div>

      <div>
        <GlassButton
          variant="secondary"
          size="sm"
          :icon-left="PlusIcon"
          @click="addMember"
        >
          Add member
        </GlassButton>
      </div>
    </div>

    <div class="mt-6 flex items-center justify-between">
      <GlassButton variant="ghost" :disabled="saving" @click="back">Back</GlassButton>
      <GlassButton :disabled="saving" @click="submit">
        {{ saving ? 'Creating project…' : 'Create project' }}
      </GlassButton>
    </div>
  </Shell>
</template>
