<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';
import PrevNextPagination from '@/Components/Spork/Molecules/Pagination/PrevNextPagination.vue';
import {
  ArrowPathIcon,
  CommandLineIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline';

dayjs.extend(relativeTime);

const props = defineProps({
  title: { type: String, default: 'Automation operations' },
  operations: { type: Object, required: true },
  automations: { type: Array, default: () => [] },
  filters: {
    type: Object,
    default: () => ({ status: 'all', automation_id: null }),
  },
  counts: {
    type: Object,
    default: () => ({ all: 0, queued: 0, running: 0, finished: 0, errored: 0 }),
  },
});

const status = ref(props.filters?.status ?? 'all');
const automationId = ref(props.filters?.automation_id ?? '');
const expanded = ref(new Set());

const statusFilters = [
  { value: 'all', label: 'All' },
  { value: 'queued', label: 'Queued' },
  { value: 'running', label: 'Running' },
  { value: 'finished', label: 'Finished' },
  { value: 'errored', label: 'Errored' },
];

const statusToneMap = {
  queued: 'neutral',
  running: 'info',
  finished: 'success',
  errored: 'danger',
};

const statusLabelMap = {
  queued: 'Queued',
  running: 'Running',
  finished: 'Finished',
  errored: 'Errored',
};

function statusTone(value) {
  return statusToneMap[value] ?? 'neutral';
}

function statusLabel(value) {
  return statusLabelMap[value] ?? value;
}

function applyFilters() {
  router.get(
    route('automations.operations.index'),
    {
      status: status.value,
      automation_id: automationId.value || undefined,
    },
    { preserveScroll: true, preserveState: true, replace: true },
  );
}

watch(status, applyFilters);
watch(automationId, applyFilters);

function reload() {
  router.reload({ only: ['operations', 'counts'], preserveScroll: true });
}

let pollHandle = null;
const POLL_MS = 10_000;

onMounted(() => {
  pollHandle = window.setInterval(reload, POLL_MS);
});

onBeforeUnmount(() => {
  if (pollHandle !== null) {
    window.clearInterval(pollHandle);
    pollHandle = null;
  }
});

function toggleExpanded(id) {
  const next = new Set(expanded.value);
  if (next.has(id)) {
    next.delete(id);
  } else {
    next.add(id);
  }
  expanded.value = next;
}

function isExpanded(id) {
  return expanded.value.has(id);
}

function formatTimestamp(value) {
  if (!value) return '—';
  return dayjs(value).format('YYYY-MM-DD HH:mm:ss');
}

function relative(value) {
  if (!value) return null;
  return dayjs(value).fromNow();
}

function formatDuration(ms) {
  if (ms === null || ms === undefined) return null;
  if (ms < 1000) return `${ms} ms`;
  const seconds = ms / 1000;
  if (seconds < 60) return `${seconds.toFixed(1)} s`;
  const minutes = Math.floor(seconds / 60);
  const remainder = seconds - minutes * 60;
  return `${minutes}m ${remainder.toFixed(0)}s`;
}

const totalAvailable = computed(() => props.counts?.all ?? 0);
</script>

<template>
  <AppLayout :title="title">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <header class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div class="flex items-start gap-3">
          <CommandLineIcon class="h-8 w-8 shrink-0 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
              Live trace
            </p>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">
              Automation operations
            </h1>
            <p class="mt-1 max-w-3xl text-sm text-stone-600 dark:text-stone-300">
              Each scheduled or on-demand automation run is tracked here with status, duration,
              and output preview. The view refreshes every {{ POLL_MS / 1000 }}s.
            </p>
          </div>
        </div>
        <GlassButton variant="secondary" :icon-left="ArrowPathIcon" @click="reload">
          Refresh
        </GlassButton>
      </header>

      <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
        <button
          v-for="entry in statusFilters"
          :key="entry.value"
          type="button"
          :aria-pressed="status === entry.value"
          :class="[
            'rounded-md border px-3 py-2 text-left transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950',
            status === entry.value
              ? 'border-indigo-400 bg-indigo-500/15 text-indigo-700 dark:border-indigo-400/60 dark:text-indigo-100'
              : 'border-[var(--color-glass-border-light)] bg-[var(--color-glass-surface-light)] text-stone-700 hover:bg-stone-100/60 dark:border-[var(--color-glass-border-dark)] dark:bg-[var(--color-glass-surface-dark)] dark:text-stone-200 dark:hover:bg-stone-800/60',
          ]"
          @click="status = entry.value"
        >
          <div class="text-xs font-medium uppercase tracking-wide opacity-80">{{ entry.label }}</div>
          <div class="mt-0.5 text-lg font-semibold tabular-nums">
            {{ counts?.[entry.value] ?? 0 }}
          </div>
        </button>
      </div>

      <GlassCard padding="md">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr),auto] sm:items-end">
          <GlassField label="Automation">
            <GlassSelect v-model="automationId">
              <option value="">All automations</option>
              <option v-for="a in automations" :key="a.id" :value="a.id">
                {{ a.name }}
              </option>
            </GlassSelect>
          </GlassField>
          <div class="flex items-center justify-end gap-1 text-xs text-stone-500 dark:text-stone-400">
            <ClockIcon class="h-3.5 w-3.5" aria-hidden="true" />
            <span>Auto-refreshing every {{ POLL_MS / 1000 }}s</span>
          </div>
        </div>
      </GlassCard>

      <div v-if="operations.data.length" class="space-y-3">
        <GlassCard
          v-for="op in operations.data"
          :key="op.id"
          padding="md"
        >
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 flex-1 space-y-1.5">
              <div class="flex flex-wrap items-center gap-2">
                <GlassPill :tone="statusTone(op.status)" size="sm" dot>
                  {{ statusLabel(op.status) }}
                </GlassPill>
                <Link
                  v-if="op.automation_id"
                  :href="route('automations.automations.show', op.automation_id)"
                  class="truncate text-sm font-semibold text-stone-900 hover:text-indigo-600 dark:text-stone-50 dark:hover:text-indigo-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950 rounded"
                >
                  {{ op.automation_name ?? `Automation #${op.automation_id}` }}
                </Link>
                <span v-else class="truncate text-sm font-semibold text-stone-900 dark:text-stone-50">
                  Operation #{{ op.id }}
                </span>
                <span v-if="op.duration_ms !== null" class="text-xs text-stone-500 dark:text-stone-400">
                  {{ formatDuration(op.duration_ms) }}
                </span>
              </div>
              <dl class="grid grid-cols-1 gap-x-4 gap-y-0.5 text-xs text-stone-600 dark:text-stone-300 sm:grid-cols-3">
                <div>
                  <dt class="font-medium uppercase tracking-wide opacity-70">Scheduled</dt>
                  <dd class="tabular-nums">
                    {{ formatTimestamp(op.should_run_at) }}
                    <span v-if="relative(op.should_run_at)" class="ml-1 opacity-70">({{ relative(op.should_run_at) }})</span>
                  </dd>
                </div>
                <div>
                  <dt class="font-medium uppercase tracking-wide opacity-70">Started</dt>
                  <dd class="tabular-nums">{{ formatTimestamp(op.started_run_at) }}</dd>
                </div>
                <div>
                  <dt class="font-medium uppercase tracking-wide opacity-70">Finished</dt>
                  <dd class="tabular-nums">{{ formatTimestamp(op.finished_run_at) }}</dd>
                </div>
              </dl>
              <div v-if="op.has_error || op.has_output" class="space-y-2 pt-2">
                <div v-if="op.has_error" class="rounded-md border border-red-200 bg-red-50/60 p-2 text-xs text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                  <div class="mb-1 font-semibold uppercase tracking-wide opacity-80">Error</div>
                  <pre class="whitespace-pre-wrap font-mono leading-snug">{{ op.error_preview }}</pre>
                </div>
                <div v-if="op.has_output && isExpanded(op.id)" class="rounded-md border border-stone-200/70 bg-stone-50/60 p-2 text-xs text-stone-700 dark:border-stone-700/60 dark:bg-stone-800/40 dark:text-stone-200">
                  <div class="mb-1 font-semibold uppercase tracking-wide opacity-80">Output</div>
                  <pre class="whitespace-pre-wrap font-mono leading-snug">{{ op.output_preview }}</pre>
                </div>
              </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <GlassButton
                v-if="op.has_output"
                variant="ghost"
                size="sm"
                :icon-left="isExpanded(op.id) ? ChevronUpIcon : ChevronDownIcon"
                @click="toggleExpanded(op.id)"
              >
                {{ isExpanded(op.id) ? 'Hide output' : 'Show output' }}
              </GlassButton>
            </div>
          </div>
        </GlassCard>

        <PrevNextPagination :paginator="operations" />
      </div>

      <GlassEmptyState
        v-else
        icon="CommandLineIcon"
        :title="totalAvailable === 0 ? 'No operations recorded yet' : 'No operations match this filter'"
        :description="totalAvailable === 0
          ? 'Operations show up here whenever an automation runs — schedule one or trigger Run now to see traces.'
          : 'Try a different status or automation filter.'"
      >
        <GlassButton
          v-if="totalAvailable === 0"
          variant="secondary"
          :href="route('automations.automations.index')"
        >
          Open automations
        </GlassButton>
        <GlassButton
          v-else
          variant="secondary"
          @click="status = 'all'; automationId = ''"
        >
          Clear filters
        </GlassButton>
      </GlassEmptyState>
    </div>
  </AppLayout>
</template>
