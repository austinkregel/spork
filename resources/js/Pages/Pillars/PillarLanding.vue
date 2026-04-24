<template>
  <AppLayout :title="pillar.label">
    <template #header>
      <div class="flex items-center gap-3">
        <DynamicIcon :icon-name="pillar.icon" class="h-6 w-6 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
        <h1 class="text-lg font-semibold text-stone-900 dark:text-stone-50">{{ pillar.label }}</h1>
      </div>
    </template>

    <div class="mx-auto max-w-6xl space-y-8 p-4 sm:p-6 lg:p-8">
      <section aria-labelledby="pillar-hero" class="space-y-3">
        <p class="text-xs font-medium uppercase tracking-wide text-stone-500 dark:text-stone-400">Pillar</p>
        <h2 id="pillar-hero" class="text-3xl font-semibold text-stone-900 dark:text-stone-50">
          {{ pillar.label }}
        </h2>
        <p v-if="tagline" class="max-w-2xl text-sm text-stone-600 dark:text-stone-300">
          {{ tagline }}
        </p>
      </section>

      <section v-if="summary_cards.length" aria-label="Summary metrics" class="space-y-3">
        <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-200">At a glance</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <GlassMetricCard
            v-for="(card, idx) in summary_cards"
            :key="idx"
            :label="card.title"
            :value="card.value"
            :description="card.delta ?? null"
            :icon="card.icon"
            :href="card.href !== '#' ? card.href : null"
            :trend-tone="card.delta_direction === 'up' ? 'success' : card.delta_direction === 'down' ? 'danger' : 'neutral'"
          />
        </div>
      </section>

      <section v-if="quickLinks.length" aria-label="Jump to" class="space-y-3">
        <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-200">Jump to</h3>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <Link
            v-for="(item, idx) in quickLinks"
            :key="idx"
            :href="item.href"
            class="group focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950 rounded-lg"
          >
            <GlassSurface class="flex items-center gap-3 p-4 transition-shadow motion-reduce:transition-none group-hover:shadow-xl">
              <DynamicIcon :icon-name="item.icon" class="h-5 w-5 text-indigo-500 dark:text-indigo-400 shrink-0" aria-hidden="true" />
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-stone-900 dark:text-stone-50 truncate">{{ item.label }}</p>
                <p v-if="item.description" class="text-xs text-stone-500 dark:text-stone-400 truncate">{{ item.description }}</p>
              </div>
              <ArrowRightIcon class="h-4 w-4 text-stone-400 group-hover:text-stone-600 dark:group-hover:text-stone-200" aria-hidden="true" />
            </GlassSurface>
          </Link>
        </div>
      </section>

      <section v-if="$slots.activity" aria-label="Recent activity" class="space-y-3">
        <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-200">Recent activity</h3>
        <slot name="activity" />
      </section>

      <slot />
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from '@heroicons/vue/20/solid';
import AppLayout from '@/Layouts/AppLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassMetricCard from '@/Components/Glass/GlassMetricCard.vue';

const props = defineProps({
  pillar: { type: Object, required: true },
  tagline: { type: String, default: null },
  summary_cards: { type: Array, default: () => [] },
  sub_nav: { type: Array, default: () => [] },
});

const quickLinks = computed(() => {
  const flat = [];
  for (const item of props.sub_nav) {
    if (item.children && item.children.length) {
      for (const child of item.children) {
        flat.push({
          label: child.label,
          icon: child.icon,
          href: child.href,
          description: item.label,
        });
      }
    } else {
      flat.push({
        label: item.label,
        icon: item.icon,
        href: item.href,
        description: null,
      });
    }
  }
  return flat;
});
</script>
