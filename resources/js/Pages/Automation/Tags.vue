<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { TagIcon, ServerIcon, BoltIcon, WalletIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';

const props = defineProps({
  title: { type: String, default: 'Automation tags' },
  tags: { type: Object, required: true },
});

const data = computed(() => props.tags?.data ?? []);

const typeIcon = (type) => {
  switch (type) {
    case 'finance':
      return WalletIcon;
    case 'server':
      return ServerIcon;
    case 'automatic':
      return BoltIcon;
    default:
      return TagIcon;
  }
};

const typeLabel = (type) => (type ? type : 'general');

const isCreateOpen = ref(false);

const form = useForm({
  name: '',
  type: 'automatic',
  must_all_conditions_pass: false,
});

const typeRequiresMatchMode = computed(() => form.type === 'automatic');

watch(
  () => form.type,
  (type) => {
    if (type !== 'automatic') {
      form.must_all_conditions_pass = false;
    }
  },
);

function openCreate() {
  form.reset();
  form.clearErrors();
  form.type = 'automatic';
  form.must_all_conditions_pass = false;
  isCreateOpen.value = true;
}

function closeCreate() {
  isCreateOpen.value = false;
  form.reset();
  form.clearErrors();
}

function submitCreate() {
  form.post(route('automations.tags.store'), {
    preserveScroll: true,
    onSuccess: () => closeCreate(),
  });
}

function formatCurrency(value) {
  const cents = Math.round((Number(value) || 0) * 100) / 100;
  return `$${cents.toFixed(2)}`;
}
</script>

<template>
  <AppLayout :title="title">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <header class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
            Routing logic
          </p>
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
            Automation tags
          </h1>
          <p class="mt-1 max-w-3xl text-sm text-stone-600 dark:text-stone-300">
            Tags control where crawls send data, which credentials they may use, and how often they
            run. Consolidating them here keeps automation behavior transparent and repeatable.
          </p>
        </div>
        <GlassButton :icon-left="PlusIcon" @click="openCreate">Create tag</GlassButton>
      </header>

      <div v-if="data.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <GlassCard v-for="tag in data" :key="tag.id" padding="md">
          <div class="flex items-start gap-3">
            <component
              :is="typeIcon(tag.type)"
              class="h-8 w-8 shrink-0 text-emerald-500 dark:text-emerald-400"
              aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
              <Link
                :href="`/-/automations/tags/${tag.id}`"
                class="block text-base font-semibold text-stone-900 hover:text-indigo-600 dark:text-stone-50 dark:hover:text-indigo-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950 rounded"
              >
                {{ tag.name?.en }}
              </Link>
              <GlassPill tone="info" size="sm" class="mt-1">{{ typeLabel(tag.type) }}</GlassPill>
            </div>
          </div>
          <dl class="mt-4 space-y-1 text-sm">
            <div class="flex items-center justify-between">
              <dt class="text-stone-600 dark:text-stone-300">Attached items</dt>
              <dd class="font-medium text-stone-900 dark:text-stone-50">
                {{ tag.taggables_count }}
              </dd>
            </div>
            <div class="flex items-center justify-between">
              <dt class="text-stone-600 dark:text-stone-300">Transaction total</dt>
              <dd class="font-medium text-stone-900 dark:text-stone-50">
                {{ formatCurrency(tag.transactions_sum_amount) }}
              </dd>
            </div>
          </dl>
        </GlassCard>
      </div>

      <GlassEmptyState
        v-else
        title="No tags yet"
        description="Create your first tag to start routing automation outputs and grouping behavior."
      >
        <GlassButton :icon-left="PlusIcon" @click="openCreate">Create tag</GlassButton>
      </GlassEmptyState>
    </div>
  </AppLayout>

  <GlassModal
    :open="isCreateOpen"
    title="Create tag"
    description="Tags route automation outputs and gate access to credentials."
    @close="closeCreate"
  >
    <form class="space-y-4" @submit.prevent="submitCreate">
      <GlassField
        v-slot="{ id, describedby, invalid }"
        label="Name"
        :error="form.errors.name"
        required
      >
        <GlassInput
          :id="id"
          v-model="form.name"
          placeholder="e.g. subscriptions"
          :invalid="invalid"
          :describedby="describedby"
          required
        />
      </GlassField>

      <GlassField label="Type" :error="form.errors.type">
        <GlassSelect v-model="form.type">
          <option value="automatic">automatic</option>
          <option value="finance">finance</option>
          <option value="server">server</option>
          <option value="">general</option>
        </GlassSelect>
      </GlassField>

      <fieldset
        class="space-y-2"
        :class="typeRequiresMatchMode ? '' : 'opacity-60 pointer-events-none'"
      >
        <legend class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
          Match mode
        </legend>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <label
            class="flex cursor-pointer items-start gap-2 rounded-md border border-stone-200 bg-white/60 p-3 dark:border-stone-700 dark:bg-stone-800/60"
          >
            <input
              v-model="form.must_all_conditions_pass"
              :value="false"
              type="radio"
              class="mt-1"
            />
            <span class="space-y-0.5">
              <span class="block text-sm font-medium text-stone-900 dark:text-stone-50">Any condition</span>
              <span class="block text-xs text-stone-600 dark:text-stone-300">Apply when at least one condition matches.</span>
            </span>
          </label>
          <label
            class="flex cursor-pointer items-start gap-2 rounded-md border border-stone-200 bg-white/60 p-3 dark:border-stone-700 dark:bg-stone-800/60"
          >
            <input
              v-model="form.must_all_conditions_pass"
              :value="true"
              type="radio"
              class="mt-1"
            />
            <span class="space-y-0.5">
              <span class="block text-sm font-medium text-stone-900 dark:text-stone-50">All conditions</span>
              <span class="block text-xs text-stone-600 dark:text-stone-300">Apply only when every condition matches.</span>
            </span>
          </label>
        </div>
        <div v-if="form.errors.must_all_conditions_pass" class="text-xs text-red-500 dark:text-red-400">
          {{ form.errors.must_all_conditions_pass }}
        </div>
      </fieldset>
    </form>

    <template #footer>
      <GlassButton variant="ghost" :disabled="form.processing" @click="closeCreate">
        Cancel
      </GlassButton>
      <GlassButton :disabled="form.processing" @click="submitCreate">
        {{ form.processing ? 'Creating…' : 'Create' }}
      </GlassButton>
    </template>
  </GlassModal>
</template>
