<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();

const SECTION_META = {
  activitylog: {
    title: 'Activity Log',
    description: 'Audit trail configuration for model CRUD events and system activity.',
  },
  app: {
    title: 'Application',
    description: 'High-level Laravel app settings that impact every request.',
  },
  broadcasting: {
    title: 'Broadcasting',
    description: 'Realtime event broadcasting defaults.',
  },
  database: {
    title: 'Database',
    description: 'Primary database driver and connection options.',
  },
  filesystems: {
    title: 'Filesystems',
    description: 'Default disk and mounted disks available to the app.',
  },
  'laravel-flight': {
    title: 'Laravel Flight',
    description: 'Preferences for the Flight programming surface.',
  },
  mailer: {
    title: 'Mailer',
    description: 'Transport configuration for outbound notifications.',
  },
  mail: {
    title: 'Mail Driver',
    description: 'Default mailer used by Laravel.',
  },
  pulse: {
    title: 'Pulse',
    description: 'Application performance monitoring status.',
  },
  spork: {
    title: 'Spork',
    description: 'Platform level feature flags and UI preferences.',
  },
};

const titleCase = (value = '') =>
  value
    .replace(/[-_]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/\b\w/g, (char) => char.toUpperCase());

const flattenValue = (value, prefix = '') => {
  if (value === null || value === undefined) {
    return [{ label: prefix || 'value', value: '—', type: 'null' }];
  }

  if (Array.isArray(value)) {
    if (value.length === 0) {
      return [{ label: prefix || 'value', value: '[]', type: 'array' }];
    }

    return value.flatMap((entry, index) =>
      flattenValue(entry, `${prefix}${prefix ? '.' : ''}[${index}]`),
    );
  }

  if (typeof value === 'object') {
    return Object.entries(value).flatMap(([key, nested]) =>
      flattenValue(nested, prefix ? `${prefix}.${key}` : key),
    );
  }

  return [
    {
      label: prefix || 'value',
      value,
      type: typeof value,
      multiline: typeof value === 'string' && value.length > 60,
    },
  ];
};

const sections = computed(() => {
  const configs = page.props.settings?.configs ?? {};

  return Object.entries(configs).map(([key, value]) => {
    const meta = SECTION_META[key] ?? {};

    return {
      key,
      title: meta.title ?? titleCase(key),
      description:
        meta.description ??
        'Configuration derived from your current environment and config files.',
      entries: flattenValue(value),
      raw: JSON.stringify(value, null, 2),
    };
  });
});
</script>

<template>
  <AppLayout :title="page.props.title ?? 'Settings'">
    <div class="px-4 py-8 sm:px-6 lg:px-10">
      <header class="max-w-4xl space-y-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
          System
        </p>
        <div class="space-y-2">
          <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">Platform Settings</h1>
          <p class="text-sm text-stone-600 dark:text-stone-300">
            These settings mirror the values currently loaded in your Laravel configuration files.
            Update them in code or the environment to persist changes across deploys.
          </p>
        </div>
      </header>

      <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section
          v-for="section in sections"
          :key="section.key"
          class="flex flex-col rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900"
        >
          <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-800">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                  {{ section.key }}
                </p>
                <h2 class="text-lg font-medium text-stone-900 dark:text-white">{{ section.title }}</h2>
              </div>
              <span
                class="inline-flex items-center rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 dark:bg-stone-800 dark:text-stone-300"
              >
                {{ section.entries.length }} fields
              </span>
            </div>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">{{ section.description }}</p>
          </div>

          <dl class="divide-y divide-stone-200 text-sm dark:divide-stone-800">
            <div
              v-for="entry in section.entries"
              :key="entry.label + entry.value"
              class="flex flex-col gap-1 px-4 py-3"
            >
              <dt class="text-xs font-medium uppercase tracking-wide text-stone-500 dark:text-stone-400">
                {{ entry.label }}
              </dt>
              <dd class="text-sm font-medium text-stone-900 dark:text-stone-100">
                <template v-if="entry.type === 'boolean'">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                      entry.value
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
                        : 'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300',
                    ]"
                  >
                    {{ entry.value ? 'Enabled' : 'Disabled' }}
                  </span>
                </template>
                <template v-else-if="entry.multiline">
                  <pre class="mt-1 whitespace-pre-wrap rounded-md bg-stone-50 px-3 py-2 font-mono text-xs text-stone-700 dark:bg-stone-800 dark:text-stone-200">
{{ entry.value }}
                  </pre>
                </template>
                <template v-else>
                  <span class="break-words font-semibold">
                    {{ entry.value === '' ? '—' : entry.value }}
                  </span>
                </template>
              </dd>
            </div>
          </dl>

          <details class="border-t border-stone-200 px-4 py-3 text-xs text-stone-500 transition dark:border-stone-800 dark:text-stone-400">
            <summary class="cursor-pointer select-none text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
              Raw JSON
            </summary>
            <pre class="mt-2 overflow-x-auto rounded-md bg-stone-50 p-3 font-mono text-[11px] leading-snug text-stone-700 dark:bg-stone-800 dark:text-stone-200">
{{ section.raw }}
            </pre>
          </details>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

