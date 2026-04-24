<template>
  <AppLayout :title="title">
    <div class="px-4 py-6 sm:px-6 lg:px-8">
      <div class="lg:flex lg:items-start lg:gap-6">
        <aside class="mb-8 w-full space-y-4 lg:mb-0 lg:sticky lg:top-24 lg:w-80 lg:self-start xl:w-96">
          <GlassCard>
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400">
                  Server
                </p>
                <h2 class="truncate text-xl font-semibold text-stone-900 dark:text-stone-50">{{ server.name }}</h2>
                <p class="text-xs text-stone-500 dark:text-stone-400">{{ server.ip_address ?? 'IP unknown' }}</p>
              </div>
              <div class="flex shrink-0 flex-col items-end gap-2">
                <Status :status="server.status" />
                <ClientStatusBadge :timestamp="server.last_ping_at" />
              </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
              <div v-for="item in summaryItems" :key="item.label">
                <dt class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ item.label }}</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{{ item.value }}</dd>
              </div>
            </dl>

            <div v-if="(server.tags ?? []).length" class="mt-4 flex flex-wrap gap-2">
              <GlassPill v-for="tag in server.tags" :key="tag.id ?? tag.name" size="sm">
                {{ tag.name ?? tag }}
              </GlassPill>
            </div>
          </GlassCard>

          <GlassSurface>
            <nav class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
              <Link
                v-for="item in navItems"
                :key="item.id"
                :href="item.href"
                :class="[
                  'flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-colors motion-reduce:transition-none focus:outline-none focus-visible:bg-indigo-500/10',
                  item.href === currentPath
                    ? 'border-l-2 border-indigo-500 bg-indigo-500/10 text-indigo-700 dark:text-indigo-200'
                    : 'text-stone-700 dark:text-stone-200 hover:bg-stone-100/60 dark:hover:bg-stone-800/40',
                ]"
              >
                <DynamicIcon v-if="item.icon" :icon-name="item.icon" class="h-5 w-5" />
                <span>{{ item.name }}</span>
              </Link>
            </nav>
          </GlassSurface>

          <BridgeStatusIndicator />
        </aside>

        <main class="flex-1">
          <slot />
        </main>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';

import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Status from '@/Components/Spork/Atoms/Status.vue';
import BridgeStatusIndicator from '@/Components/Infrastructure/BridgeStatusIndicator.vue';
import ClientStatusBadge from '@/Components/Infrastructure/ClientStatusBadge.vue';
import { buildServerNavigation } from '@/Pages/Infrastructure/serverNavigation';

const props = defineProps({
  server: { type: Object, required: true },
  title: { type: String, default: 'Server' },
  navigation: { type: Array, default: () => [] },
});

const currentPath = ref(window.location.pathname);
const navItems = computed(() => (props.navigation.length ? props.navigation : buildServerNavigation(props.server)));

const formatTimestamp = (value) => (value ? dayjs(value).format('MMM D, YYYY h:mm A') : 'Never');

const summaryItems = computed(() => [
  { label: 'Provider', value: props.server.provider_label ?? props.server.provider ?? props.server.credential?.provider ?? 'Unspecified' },
  { label: 'vCPU', value: props.server.vcpu ?? '—' },
  { label: 'Memory', value: props.server.memory ? `${props.server.memory} GB` : '—' },
  { label: 'Storage', value: props.server.disk ? `${props.server.disk} GB` : '—' },
  { label: 'Last ping', value: formatTimestamp(props.server.last_ping_at) },
  { label: 'Cost/hr', value: props.server.cost_per_hour ? `$${Number(props.server.cost_per_hour).toFixed(2)}` : '—' },
]);
</script>
